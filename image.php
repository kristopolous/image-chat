<?php
require_once __DIR__ . '/db.php';

// image.php?id=<thread_id>[&fmt=png|webp|avif]
// Renders a chat thread as an image with a QR code in the corner.

$id = $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  die('missing id');
}

// Parse format — strip extension from id if present (e.g. /image/123.webp)
$fmt = $_GET['fmt'] ?? 'png';
if (preg_match('/^(.+)\.(png|webp|avif)$/i', $id, $m)) {
  $id = $m[1];
  $fmt = strtolower($m[2]);
}
$fmt = strtolower($fmt);

$content_types = [
  'png'  => 'image/png',
  'webp' => 'image/webp',
  'avif' => 'image/avif',
];
if (!isset($content_types[$fmt])) {
  http_response_code(415);
  die('unsupported format');
}

// 1. Fetch thread metadata & comments
$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM image_chats WHERE id = ?');
$stmt->execute([$id]);
$thread = $stmt->fetch();

if (!$thread) {
  http_response_code(404);
  die('not found');
}

$stmt = $pdo->prepare('SELECT * FROM comments WHERE thread_id = ? ORDER BY created_at ASC');
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

// 2. Build comment text in markdown
$md = "";
foreach ($comments as $c) {
  $md .= "**" . htmlspecialchars($c['author']) . "** — ";
  $md .= $c['created_at'] . "\n\n";
  $md .= htmlspecialchars($c['body']) . "\n\n---\n\n";
}

// 3. Render via pandoc → HTML → wkhtmltopdf → PNG
//    Uses a custom template for styling.
// $tmpdir = sys_get_temp_dir();
// $mdFile = tempnam($tmpdir, 'ichat_') . '.md';
// file_put_contents($mdFile, $md);
//
// $pdf = tempnam($tmpdir, 'ichat_') . '.pdf';
// passthru(
//   "pandoc $mdFile " .
//   "--template=templates/chat-default.html " .
//   "-V title=" . escapeshellarg($thread['title']) . " " .
//   "-o $pdf " .
//   "-f markdown -t html " .
//   "--pdf-engine=wkhtmltopdf " .
//   "--pdf-engine-opt=--page-size=A6 " .
//   "--pdf-engine-opt=--margin-top=0 " .
//   "--pdf-engine-opt=--margin-bottom=0 " .
//   "--pdf-engine-opt=--margin-left=0 " .
//   "--pdf-engine-opt=--margin-right=0"
// );
//
// $png = tempnam($tmpdir, 'ichat_') . '.png';
// passthru("pdftoppm -png -r 200 $pdf $png");

// 4. Composite QR code into the bottom-right corner
//    QR encodes the share/comment URL for this thread
//    TODO: use phpqrcode / chillerlan/php-qrcode
// $qrUrl = "https://example.com/chat.php?id=$id";
// generate QR, composite onto $png

// 5. Convert to requested format
// $final = tempnam($tmpdir, 'ichat_') . '.' . ($fmt === 'png' ? 'png' : $fmt);
// if ($fmt === 'png') {
//   rename($png, $final);
// } else {
//   passthru("magick $png $final");              // ImageMagick 7
//   // or: passthru("convert $png $final");      // ImageMagick 6
//   // or per-format tools:
//   //   passthru("cwebp $png -o $final");       // webp
//   //   passthru("avifenc $png $final");        // avif
// }

// 6. Cache & output
//    TODO: add ETag / Last-Modified based on latest comment timestamp
// header('Content-Type: ' . $content_types[$fmt]);
// header('Cache-Control: public, max-age=31536000, immutable');
// readfile($final);

// 7. Cleanup temp files
// unlink($pdf); unlink($mdFile); unlink($png); unlink($final);

// --- placeholder until pipeline is wired ---
header('Content-Type: text/plain');
echo "image-chat #{$id}\n";
echo "comments: " . count($comments) . "\n";
echo "format: {$fmt}\n";
echo "---\n";
echo $md;
