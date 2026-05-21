<?php
@ini_set('pcre.jit', 0);
error_log('image.php: start id=' . ($_GET['id'] ?? 'none'));
require_once __DIR__ . '/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  error_log('image.php: missing id');
  http_response_code(400);
  die('missing id');
}

$fmt = 'png';
$content_types = [
  'png'  => 'image/png',
  'webp' => 'image/webp',
  'avif' => 'image/avif',
];

if (preg_match('/^(.+)\.(png|webp|avif)$/i', $id, $m)) {
  $id = $m[1];
  $fmt = strtolower($m[2]);
  error_log("image.php: fmt from extension id=$id fmt=$fmt");
} elseif (isset($_GET['fmt'])) {
  $fmt = strtolower($_GET['fmt']);
  error_log("image.php: fmt from query id=$id fmt=$fmt");
}
if (!isset($content_types[$fmt])) {
  error_log("image.php: unsupported fmt=$fmt");
  http_response_code(415);
  die('unsupported format');
}

$cacheFile = __DIR__ . '/cache/' . $id . '.' . $fmt;

// Serve cached if fresh
if (file_exists($cacheFile)) {
  $cacheTime = filemtime($cacheFile);
  $pdo = get_pdo();
  $stmt = $pdo->prepare('SELECT MAX(created_at) FROM comments WHERE thread_id = ?');
  $stmt->execute([$id]);
  $latestComment = $stmt->fetchColumn();
  $latest = $latestComment ? strtotime($latestComment) : 0;

  $stmt = $pdo->prepare('SELECT UNIX_TIMESTAMP(created_at) FROM image_chats WHERE id = ?');
  $stmt->execute([$id]);
  $created = (int)$stmt->fetchColumn();

  if ($cacheTime >= max($created, $latest)) {
    header('Content-Type: ' . $content_types[$fmt]);
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($cacheFile);
    exit;
  }
}

// No valid cache — build from scratch
error_log('image.php: cache miss, building');
$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM image_chats WHERE id = ?');
$stmt->execute([$id]);
$thread = $stmt->fetch();
if (!$thread) {
  error_log("image.php: thread id=$id not found");
  http_response_code(404);
  die('not found');
}
error_log('image.php: found thread style=' . ($thread['style'] ?? 'default') . ' title=' . $thread['title']);

$limit = $thread['style'] === 'group-text' ? 10 : 25;
error_log("image.php: limit=$limit");
$stmt = $pdo->prepare("SELECT * FROM comments WHERE thread_id = ? ORDER BY created_at DESC LIMIT $limit");
$stmt->execute([$id]);
$comments = array_reverse($stmt->fetchAll());
error_log('image.php: comments count=' . count($comments));

// Build markdown — each comment is one line
$md = '';
if ($thread['style'] === 'group-text') {
  foreach ($comments as $c) {
    $date = date('Y-m-d H:i', strtotime($c['created_at']));
    $author = htmlspecialchars($c['author']);
    $body   = htmlspecialchars($c['body']);
    $md .=
      '<div class="msg">' . "\n" .
      '  <div class="bubble">' . $body . '</div>' . "\n" .
      '  <div class="meta">' . $author . ' ' . $date . '</div>' . "\n" .
      '</div>' . "\n\n";
  }
} else {
  foreach ($comments as $c) {
    $date = date('Y-m-d H:i', strtotime($c['created_at']));
    $md .= htmlspecialchars($date . ' <' . $c['author'] . '> ' . $c['body']) . "\n\n";
  }
}

// Temp files
$tmpdir = sys_get_temp_dir();
$mdFile = tempnam($tmpdir, 'ichat_');
file_put_contents($mdFile, $md);

$pdfFile = $tmpdir . '/ichat_' . uniqid() . '.pdf';
$pngPrefix = $tmpdir . '/ichat_' . uniqid();

// Load style preset
$styleName = $thread['style'] ?? 'default';
$styleFile = __DIR__ . '/styles/' . basename($styleName) . '.css';
$styleSheet = null;
if (file_exists($styleFile)) {
  $styleSheet = tempnam($tmpdir, 'ichat_css_');
  file_put_contents($styleSheet, file_get_contents($styleFile));
}

// Pandoc → PDF via WeasyPrint
$template = __DIR__ . '/templates/chat-default.html';
$styleOpts = '';
if ($styleSheet) {
  $styleOpts = '--pdf-engine-opt=-s --pdf-engine-opt=' . escapeshellarg($styleSheet) . ' ';
}
$cmd = sprintf(
  "pandoc %s --template=%s -V title=%s -o %s -f markdown -t html --pdf-engine=weasyprint %s 2>&1",
  escapeshellarg($mdFile),
  escapeshellarg($template),
  escapeshellarg($thread['title']),
  escapeshellarg($pdfFile),
  $styleOpts
);
exec($cmd, $output, $ret);
error_log("image.php: pandoc ret=$ret output=" . implode('; ', $output));
if ($ret !== 0) {
  unlink($mdFile);
  if ($styleSheet) @unlink($styleSheet);
  http_response_code(500);
  die('pandoc failed: ' . implode("\n", $output));
}

// PDF → PNG (first page, 200 DPI)
exec(
  sprintf("pdftoppm -png -r 200 -f 1 -l 1 %s %s 2>&1",
    escapeshellarg($pdfFile),
    escapeshellarg($pngPrefix)
  ),
  $output, $ret
);
$pngFile = $pngPrefix . '-1.png';
error_log("image.php: pdftoppm ret=$ret pngFile=$pngFile exists=" . (file_exists($pngFile) ? 'yes' : 'no'));
if ($ret !== 0 || !file_exists($pngFile)) {
  unlink($mdFile); unlink($pdfFile);
  if ($styleSheet) @unlink($styleSheet);
  http_response_code(500);
  die('pdftoppm failed: ' . implode("\n", $output));
}

// QR code
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
  . '://' . $_SERVER['HTTP_HOST'] . $basePath;
$qrUrl = $baseUrl . '/' . $id;
$qrFile = $tmpdir . '/ichat_' . uniqid() . '.png';

exec(
  sprintf("qrencode -o %s %s 2>&1",
    escapeshellarg($qrFile),
    escapeshellarg($qrUrl)
  ),
  $output, $ret
);
if ($ret !== 0) {
  // QR not critical — continue without it
  $qrFile = null;
}

// Composite QR onto image (bottom-right, 20px padding)
if ($qrFile && file_exists($qrFile)) {
  $finalFile = $tmpdir . '/ichat_' . uniqid() . '.png';
  exec(
    sprintf("composite -gravity southeast -geometry +20+20 %s %s %s 2>&1",
      escapeshellarg($qrFile),
      escapeshellarg($pngFile),
      escapeshellarg($finalFile)
    ),
  $output, $ret);
  error_log('image.php: composite OK');
  $outFile = ($ret === 0) ? $finalFile : $pngFile;
} else {
  error_log('image.php: no QR, using pngFile');
  $outFile = $pngFile;
}

// Format conversion
if ($fmt !== 'png') {
  $fmtFile = $tmpdir . '/ichat_' . uniqid() . '.' . $fmt;
  exec(
    sprintf("convert %s %s 2>&1",
      escapeshellarg($outFile),
      escapeshellarg($fmtFile)
    ),
  $output, $ret);
  if ($ret === 0) $outFile = $fmtFile;
}

// Save to cache and serve
if (!is_dir(__DIR__ . '/cache')) {
  error_log('image.php: creating cache dir');
  mkdir(__DIR__ . '/cache', 0755, true);
}
$copied = copy($outFile, $cacheFile);
error_log("image.php: cache copy from=$outFile to=$cacheFile result=" . ($copied ? 'ok' : 'FAIL'));
header('Content-Type: ' . $content_types[$fmt]);
header('Cache-Control: public, max-age=31536000, immutable');
http_response_code(200);
$sent = readfile($cacheFile);
error_log("image.php: readfile sent=$sent bytes");

// Cleanup
unlink($mdFile);
unlink($pdfFile);
unlink($pngFile);
if ($styleSheet) @unlink($styleSheet);
if (isset($qrFile) && $qrFile) @unlink($qrFile);
if (isset($finalFile)) @unlink($finalFile);
if (isset($fmtFile)) @unlink($fmtFile);
