export function videoPlayer() {
    return {
        playing: false,
        currentTime: 0,
        duration: 0,
        volume: 1,
        muted: false,
        fullscreen: false,

        init() {
            const video = this.$refs.video;
            if (!video) return;

            // Video event listeners
            video.addEventListener('loadedmetadata', () => {
                this.duration = video.duration;
            });

            video.addEventListener('timeupdate', () => {
                this.currentTime = video.currentTime;
            });

            video.addEventListener('play', () => {
                this.playing = true;
            });

            video.addEventListener('pause', () => {
                this.playing = false;
            });

            video.addEventListener('ended', () => {
                this.playing = false;
            });

            video.addEventListener('volumechange', () => {
                this.volume = video.volume;
                this.muted = video.muted;
            });
        },

        togglePlay() {
            const video = this.$refs.video;
            if (!video) return;

            if (this.playing) {
                video.pause();
            } else {
                video.play();
            }
        },

        seek(event) {
            const video = this.$refs.video;
            if (!video) return;

            const rect = event.currentTarget.getBoundingClientRect();
            const pos = (event.clientX - rect.left) / rect.width;
            video.currentTime = pos * this.duration;
        },

        setVolume(value) {
            const video = this.$refs.video;
            if (!video) return;

            video.volume = value;
            this.volume = value;
        },

        toggleMute() {
            const video = this.$refs.video;
            if (!video) return;

            video.muted = !video.muted;
            this.muted = video.muted;
        },

        toggleFullscreen() {
            const container = this.$refs.container;
            if (!container) return;

            if (!document.fullscreenElement) {
                container.requestFullscreen();
                this.fullscreen = true;
            } else {
                document.exitFullscreen();
                this.fullscreen = false;
            }
        },

        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);

            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m}:${s.toString().padStart(2, '0')}`;
        }
    };
}
