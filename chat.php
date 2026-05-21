<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

$id = $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  die('missing id');
}

$stmt = $pdo->prepare('SELECT * FROM image_chats WHERE id = ?');
$stmt->execute([$id]);
$thread = $stmt->fetch();

if (!$thread) {
  http_response_code(404);
  die('not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $author = trim($_POST['author'] ?? '');
  $body   = trim($_POST['body'] ?? '');
  if ($author !== '' && $body !== '') {
    $stmt = $pdo->prepare('INSERT INTO comments (thread_id, author, body) VALUES (?, ?, ?)');
    $stmt->execute([$id, $author, $body]);
    $base = dirname($_SERVER['SCRIPT_NAME']);
    header('Location: ' . $base . '/chat.php?id=' . $id);
    exit;
  }
}

$comments = $pdo->prepare('SELECT * FROM comments WHERE thread_id = ? ORDER BY created_at ASC');
$comments->execute([$id]);
$comments = $comments->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($thread['title']) ?> — image-chat</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;
    max-width: 640px; margin: 40px auto; padding: 0 20px;
    color: #1a1a1a;
  }
  h1 { font-size: 22px; margin-bottom: 4px; }
  .back { font-size: 13px; margin-bottom: 20px; display: block; color: #888; }
  .back a { color: #555; }
  .embed { font-size: 13px; margin-bottom: 24px; }
  .embed code {
    background: #f4f4f6; padding: 6px 10px; border-radius: 6px;
    font-size: 12px; display: inline-block;
  }

  .comment { padding: 12px 0; border-bottom: 1px solid #eee; }
  .comment:last-child { border-bottom: none; }
  .comment-head { font-size: 13px; margin-bottom: 2px; }
  .comment-author { font-weight: 600; color: #333; }
  .comment-time { color: #aaa; margin-left: 6px; }
  .comment-body { font-size: 14px; line-height: 1.5; color: #444; }

  form { margin-top: 24px; padding-top: 20px; border-top: 1px solid #ddd; }
  form input, form textarea {
    width: 100%; padding: 10px 14px; font-size: 14px;
    border: 1px solid #ccc; border-radius: 8px; margin-bottom: 10px;
    font-family: inherit;
  }
  form textarea { resize: vertical; min-height: 60px; }
  form button {
    padding: 10px 20px; font-size: 14px; border: none;
    border-radius: 8px; background: #333; color: #fff; cursor: pointer;
  }
  form button:hover { background: #555; }
</style>
</head>
<body>
  <div class="back"><a href="<?= dirname($_SERVER['SCRIPT_NAME']) ?>/">&larr; image-chat</a></div>
  <h1><?= htmlspecialchars($thread['title']) ?></h1>

  <div class="embed">
    embed: <code>&lt;img src="<?= $base ?>/image/<?= $id ?>.png" alt="<?= htmlspecialchars($thread['title']) ?>"&gt;</code>
  </div>

  <?php foreach ($comments as $c): ?>
  <div class="comment">
    <div class="comment-head">
      <span class="comment-author"><?= htmlspecialchars($c['author']) ?></span>
      <span class="comment-time"><?= $c['created_at'] ?></span>
    </div>
    <div class="comment-body"><?= htmlspecialchars($c['body']) ?></div>
  </div>
  <?php endforeach; ?>

  <?php if (!$comments): ?>
  <p style="color:#888;">no comments yet</p>
  <?php endif; ?>

  <form method="post">
    <input type="text" name="author" placeholder="your name" maxlength="20" required>
    <textarea name="body" placeholder="write something..." maxlength="250" required></textarea>
    <button type="submit">comment</button>
  </form>
</body>
</html>
