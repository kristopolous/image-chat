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

$base = dirname($_SERVER['SCRIPT_NAME']);
$restricted = $thread['password_hash'] !== null;
$unlocked = $restricted && !empty($_SESSION['unlocked_' . $id]);
$error = null;

if ($restricted && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_pass'])) {
  if (password_verify($_POST['unlock_pass'], $thread['password_hash'])) {
    $_SESSION['unlocked_' . $id] = true;
    $unlocked = true;
  } else {
    $error = 'wrong password';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['author']) && (!$restricted || $unlocked)) {
  $author = trim($_POST['author']);
  $body   = trim($_POST['body'] ?? '');
  if ($author !== '' && $body !== '') {
    $stmt = $pdo->prepare('INSERT INTO comments (thread_id, author, body) VALUES (?, ?, ?)');
    $stmt->execute([$id, $author, $body]);
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
  .error { color: #c33; font-size: 13px; margin-bottom: 8px; }
  .restricted-note { font-size: 12px; color: #888; margin-top: 4px; }
</style>
</head>
<body>
  <div class="back"><a href="<?= $base ?>/">&larr; image-chat</a></div>
  <h1><?= htmlspecialchars($thread['title']) ?></h1>
  <div class="meta-info" style="font-size:12px; color:#888;">
    style: <?= htmlspecialchars($thread['style'] ?? 'default') ?>
    <?php if ($restricted): ?>· 🔒 restricted<?php endif; ?>
  </div>

  <div style="margin: 16px 0;">
    <img src="<?= $base ?>/image/<?= $id ?>.png" alt="<?= htmlspecialchars($thread['title']) ?>"
         width="827" height="1166"
         style="max-width:100%; height:auto; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.12);">
  </div>

  <div class="embed">
    embed: <code>&lt;img src="<?= $base ?>/image/<?= $id ?>.png" alt="<?= htmlspecialchars($thread['title']) ?>"&gt;</code>
  </div>

  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

  <?php if ($restricted && !$unlocked): ?>
  <form method="post">
    <input type="text" name="unlock_pass" placeholder="password required to comment" required autofocus>
    <button type="submit">unlock</button>
  </form>
  <?php else: ?>
  <form method="post">
    <input type="text" name="author" id="author" placeholder="your name" maxlength="20" required>
    <textarea name="body" placeholder="write something..." maxlength="250" required></textarea>
    <button type="submit">comment</button>
  </form>
  <?php endif; ?>

  <script>
    const author = document.getElementById('author');
    if (author) {
      const saved = localStorage.getItem('ichat_author');
      if (saved) author.value = saved;
      author.addEventListener('input', () => localStorage.setItem('ichat_author', author.value));
    }
  </script>
</body>
</html>
