/*
 * Stream engine for the wrapped live player.
 *
 * One uniform handle over four very different sources, so the Blade chrome
 * never branches on stream type:
 *
 *   youtube -> YouTube IFrame SDK          (CDN script, same as the episode page)
 *   hls     -> <video> + hls.js            (native on Safari/iOS, which ships HLS)
 *   dash    -> <video> + dash.js
 *   media   -> <video>, straight           (progressive mp4/webm, and audio-only
 *                                           radio: a <video> plays an audio track
 *                                           fine, it just paints nothing)
 *
 * hls.js and dash.js are literal dynamic import()s so Vite code-splits each into
 * its own chunk — a YouTube channel never downloads either, and a channel that
 * needs one fetches only that one. Same trick as loadGenealogyTree in app.js.
 *
 * Every handle exposes: play, pause, mute, unmute, setVolume, getVolume,
 * isMuted, destroy — and reports back through onState/onError.
 */

/* ------------------------------------------------------------------ utils --- */

/** Load the YouTube IFrame SDK once; resolves when window.YT is usable. */
function loadYouTubeApi() {
    return new Promise((resolve, reject) => {
        if (window.YT && window.YT.Player) {
            resolve();
            return;
        }
        const timeout = setTimeout(() => reject(new Error('yt_api_timeout')), 8000);
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            if (previous) previous();
            clearTimeout(timeout);
            resolve();
        };
        if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    });
}

/* ----------------------------------------------------------------- engines --- */

async function createYouTube({ container, source, onState, onError }) {
    await loadYouTubeApi();

    let yt = null;

    await new Promise((resolve, reject) => {
        const failed = setTimeout(() => reject(new Error('yt_ready_timeout')), 15000);

        yt = new window.YT.Player(container, {
            videoId: source.video_id,
            playerVars: {
                autoplay: 1,
                controls: 0, // our control bar only
                rel: 0,
                fs: 0,
                disablekb: 1,
                playsinline: 1,
                iv_load_policy: 3,
                modestbranding: 1,
                origin: window.location.origin,
            },
            events: {
                onReady: () => {
                    clearTimeout(failed);
                    resolve();
                },
                onStateChange: (e) => {
                    const S = window.YT.PlayerState;
                    if (e.data === S.PLAYING) onState('playing');
                    else if (e.data === S.PAUSED) onState('paused');
                    else if (e.data === S.BUFFERING) onState('buffering');
                    else if (e.data === S.ENDED) onState('ended');
                },
                // Codes worth recognising: 100/101/150 mean the video is gone
                // or its owner forbids embedding — no retry will ever fix that.
                onError: (e) => {
                    console.warn('[stream-player] YouTube player error', e.data);
                    onError(new Error(`yt_player_error:${e.data}`));
                },
            },
        });
    });

    return {
        play: () => yt.playVideo(),
        pause: () => yt.pauseVideo(),
        mute: () => yt.mute(),
        unmute: () => yt.unMute(),
        isMuted: () => yt.isMuted(),
        // YouTube's scale is 0-100, same as our slider — no conversion.
        getVolume: () => yt.getVolume(),
        setVolume: (v) => yt.setVolume(v),
        destroy: () => { try { yt.destroy(); } catch (e) { /* already gone */ } },
    };
}

/**
 * Wire the shared <video> lifecycle events onto our uniform state callback.
 * `waiting`/`stalled` both mean "buffering" as far as the spinner cares.
 */
function bindMediaEvents(video, onState, onError) {
    video.addEventListener('playing', () => onState('playing'));
    video.addEventListener('play', () => onState('buffering'));
    video.addEventListener('pause', () => onState('paused'));
    video.addEventListener('waiting', () => onState('buffering'));
    video.addEventListener('stalled', () => onState('buffering'));
    video.addEventListener('ended', () => onState('ended'));
    video.addEventListener('error', () => onError(new Error('media_element_error')));
}

/** The handle shape shared by every <video>-backed engine. */
function mediaHandle(video, teardown) {
    return {
        play: () => video.play(),
        pause: () => video.pause(),
        mute: () => { video.muted = true; },
        unmute: () => { video.muted = false; },
        isMuted: () => video.muted,
        getVolume: () => Math.round(video.volume * 100),
        setVolume: (v) => { video.volume = Math.min(100, Math.max(0, v)) / 100; },
        destroy: () => {
            try { teardown && teardown(); } catch (e) { /* already gone */ }
            try { video.pause(); video.removeAttribute('src'); video.load(); } catch (e) { /* detached */ }
        },
    };
}

async function createHls({ video, source, onState, onError }) {
    bindMediaEvents(video, onState, onError);

    // hls.js first, native second — the order hls.js itself prescribes, and NOT
    // the other way round: Chrome answers "maybe" to canPlayType() for the HLS
    // MIME type while being completely unable to play it, so trusting that check
    // first leaves the stream stuck at readyState 0 forever. isSupported() tests
    // for Media Source Extensions, which is the honest question. Where MSE is
    // missing (iOS/iPadOS Safari) it is false and we fall through to the native
    // player, which is what plays HLS there anyway.
    const { default: Hls } = await import('hls.js');

    if (!Hls.isSupported()) {
        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = source.url;
            return mediaHandle(video);
        }
        throw new Error('hls_unsupported');
    }

    const hls = new Hls({ enableWorker: true, lowLatencyMode: true });

    // hls.js can walk back from a transient network or media fault, but the
    // recovery must be bounded: a stream that is down, geo-blocked or missing
    // its CORS headers fails the same way on every attempt, and an uncapped
    // retry leaves the visitor watching the spinner forever with nothing said.
    let recoveries = 0;
    hls.on(Hls.Events.ERROR, (_event, data) => {
        if (!data.fatal) return;

        if (recoveries >= 3) {
            console.warn('[stream-player] giving up on HLS stream', data.type, data.details);
            onError(new Error(`hls_fatal:${data.details}`));
            return;
        }

        recoveries += 1;
        console.warn('[stream-player] recovering HLS stream', data.type, data.details, `attempt ${recoveries}`);

        if (data.type === Hls.ErrorTypes.NETWORK_ERROR) hls.startLoad();
        else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) hls.recoverMediaError();
        else onError(new Error(`hls_fatal:${data.details}`));
    });
    hls.loadSource(source.url);
    hls.attachMedia(video);

    return mediaHandle(video, () => hls.destroy());
}

async function createDash({ video, source, onState, onError }) {
    bindMediaEvents(video, onState, onError);

    const dashjs = await import('dashjs');
    const lib = dashjs.default ?? dashjs;
    const player = lib.MediaPlayer().create();
    // We call play() ourselves once the chrome is ready, so the autoplay
    // fallback ladder stays in one place.
    player.initialize(video, source.url, false);

    return mediaHandle(video, () => player.reset());
}

async function createMedia({ video, source, onState, onError }) {
    bindMediaEvents(video, onState, onError);
    video.src = source.url;
    return mediaHandle(video);
}

/* -------------------------------------------------------------------- api --- */

/**
 * Open a stream.
 *
 * @param {object}      opts
 * @param {HTMLElement} opts.container Node the YouTube SDK replaces with its iframe.
 * @param {HTMLVideoElement} opts.video <video> used by the hls/dash/media engines.
 * @param {object}      opts.source    { engine, url, video_id } from the server.
 * @param {Function}    opts.onState   Called with 'playing'|'paused'|'buffering'|'ended'.
 * @param {Function}    opts.onError   Called with an Error on unrecoverable failure.
 */
export async function createStream({ container, video, source, onState, onError }) {
    const args = { container, video, source, onState, onError };

    switch (source.engine) {
        case 'youtube': return createYouTube(args);
        case 'hls': return createHls(args);
        case 'dash': return createDash(args);
        case 'media': return createMedia(args);
        default: throw new Error(`unsupported_engine:${source.engine}`);
    }
}
