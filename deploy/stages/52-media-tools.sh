#!/usr/bin/env bash
# Stage 52 — media tooling the app shells out to:
#   - ffmpeg/ffprobe: video thumbnails + conversions (spatie/medialibrary;
#     config/media-library.php expects /usr/bin/ffmpeg + /usr/bin/ffprobe).
#   - yt-dlp: YouTube ingestion/metadata (App\Domain\Media\Support\YouTubeClient;
#     config/youtube.php defaults to `yt-dlp` on $PATH).

# --- ffmpeg (provides /usr/bin/ffmpeg and /usr/bin/ffprobe) ---
is_installed ffmpeg || apt_quiet install ffmpeg
ok "ffmpeg: $(ffmpeg -version 2>/dev/null | head -n1 | cut -c1-60)"

# --- yt-dlp: install the self-contained release binary (kept current via
#     `yt-dlp -U`) instead of the usually-stale apt package. It is a Python
#     zipapp, so python3 must be present (default on Ubuntu). ---
is_installed python3 || apt_quiet install python3
YTDLP_BIN=/usr/local/bin/yt-dlp
if [[ -x "${YTDLP_BIN}" ]]; then
  log "yt-dlp present — self-updating..."
  "${YTDLP_BIN}" -U >/dev/null 2>&1 || warn "yt-dlp self-update failed (keeping existing)."
else
  log "Installing yt-dlp release binary..."
  curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o "${YTDLP_BIN}"
  chmod a+rx "${YTDLP_BIN}"
fi
ok "yt-dlp: $(${YTDLP_BIN} --version 2>/dev/null)"

# YouTube changes often break older yt-dlp; keep it fresh with a weekly self-update.
cat > /etc/cron.weekly/yt-dlp-update <<EOF
#!/usr/bin/env bash
${YTDLP_BIN} -U >/dev/null 2>&1 || true
EOF
chmod +x /etc/cron.weekly/yt-dlp-update

ok "Media tools ready (ffmpeg + yt-dlp; yt-dlp auto-updates weekly)."
