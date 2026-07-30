<?php

declare(strict_types=1);

/**
 * Local extraction sink for the sunnah.com pipeline.
 *
 * sunnah.com is behind Cloudflare and 403s non-browser clients, so extraction
 * must run in a real browser session. The browser cannot write to disk, and
 * reading payloads back through the automation tool truncates. This bridges the
 * two: the page POSTs its JSON here and this writes it under hadith_source.
 *
 * Loopback only. Writes only inside the configured root, under a name built
 * from a strict [a-z0-9_-] whitelist, so a hostile payload cannot traverse out.
 *
 * Run:  php -S 127.0.0.1:8899 extraction-sink.php
 */
const ROOT = '/Volumes/Data/projects/the-last-massenger/the-last-messenger-be/storage/app/hadith_source';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
// Chrome's Private Network Access: an HTTPS page reaching a loopback address
// preflights regardless of content type, and hangs without this header.
header('Access-Control-Allow-Private-Network: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// The relay page. Chrome forbids an https://sunnah.com page from reaching
// loopback (Private Network Access), and a loopback page cannot read sunnah.com
// (CORS). `window.name` survives cross-origin navigation, so the extractor
// stashes its JSON there and this page — same origin as the sink — posts it.
if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/relay')) {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
        <!doctype html><meta charset="utf-8"><title>relay</title><body><pre id="out">working…</pre>
        <script>
        (async () => {
          const p = new URLSearchParams(location.search);
          const out = document.getElementById('out');
          const payload = window.name;
          if (!payload) { out.textContent = 'RELAY_ERROR: window.name empty'; return; }
          try {
            const res = await fetch('/?collection=' + encodeURIComponent(p.get('collection'))
                                  + '&book=' + encodeURIComponent(p.get('book')),
                                  { method: 'POST', body: payload });
            out.textContent = 'RELAY_RESULT ' + (await res.text());
            window.name = '';
          } catch (e) { out.textContent = 'RELAY_ERROR: ' + e; }
        })();
        </script>
        HTML;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['ok' => true, 'sink' => 'ready']);
    exit;
}

$collection = (string) ($_GET['collection'] ?? '');
$book = (string) ($_GET['book'] ?? '');

// Whitelist, not sanitisation: anything outside this set is refused rather than
// rewritten, so a surprising book number surfaces instead of being mangled.
if (! preg_match('/^[a-z0-9_]{1,40}$/', $collection) || ! preg_match('/^[a-z0-9_-]{1,40}$/', $book)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad collection or book']);
    exit;
}

$body = file_get_contents('php://input');

if ($body === false || $body === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty body']);
    exit;
}

// Refuse malformed JSON here rather than letting the importer discover it later.
$decoded = json_decode($body, true);

if (! is_array($decoded)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'not valid json: '.json_last_error_msg()]);
    exit;
}

$dir = ROOT.'/'.$collection;

if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'cannot create '.$dir]);
    exit;
}

$path = $dir.'/book_'.$book.'.json';

// Write to a temporary name and rename on success: a half-written file that
// still parses is the worst possible outcome for a resumable extraction run.
$tmp = $path.'.part';
file_put_contents($tmp, $body);
rename($tmp, $path);

$records = is_array($decoded['records'] ?? null) ? count($decoded['records']) : 0;
$chapters = is_array($decoded['chapters'] ?? null) ? count($decoded['chapters']) : 0;

$manifest = [
    'collection' => $collection,
    'book_number' => $book,
    'source_url' => $decoded['source']['url'] ?? null,
    'extractor_version' => $decoded['source']['extractor_version'] ?? null,
    'expected_records' => $decoded['counts']['records'] ?? null,
    'actual_records' => $records,
    'expected_chapters' => $decoded['counts']['chapters'] ?? null,
    'actual_chapters' => $chapters,
    'bytes' => strlen($body),
    'sha256' => hash('sha256', $body),
    'extracted_at' => date('c'),
];

file_put_contents($dir.'/book_'.$book.'.manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode(['ok' => true, 'path' => $path, 'records' => $records, 'chapters' => $chapters, 'sha256' => $manifest['sha256']]);
