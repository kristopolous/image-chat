<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();
$base = dirname($_SERVER['SCRIPT_NAME']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
  $title = trim($_POST['title']);
  if ($title !== '') {
    $stmt = $pdo->prepare('INSERT INTO image_chats (title) VALUES (?)');
    $stmt->execute([$title]);
    header('Location: ' . $base . '/chat.php?id=' . $pdo->lastInsertId());
    exit;
  }
}

$threads = $pdo->query('SELECT id, title, created_at FROM image_chats ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>image-chat</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;
    max-width: 600px; margin: 40px auto; padding: 0 20px;
    color: #1a1a1a;
  }
  h1 { font-size: 24px; margin-bottom: 24px; }
  h2 { font-size: 18px; margin-bottom: 12px; }
  form { margin-bottom: 32px; }
  input[type="text"] {
    width: 100%; padding: 10px 14px; font-size: 15px;
    border: 1px solid #ccc; border-radius: 8px; margin-bottom: 10px;
  }
  button {
    padding: 10px 20px; font-size: 14px; border: none;
    border-radius: 8px; background: #333; color: #fff;
    cursor: pointer;
  }
  button:hover { background: #555; }
  ul { list-style: none; }
  li {
    padding: 12px 16px; background: #f4f4f6; border-radius: 8px;
    margin-bottom: 8px;
  }
  li a { color: #333; text-decoration: none; font-weight: 500; }
  li a:hover { text-decoration: underline; }
  .meta { font-size: 12px; color: #888; margin-top: 2px; }
</style>
</head>
<body>
  <h1>image-chat</h1>

  <form method="post">
    <input type="text" name="title" placeholder="chat title" required autofocus>
    <button type="submit">create</button>
  </form>

  <h2>recent image-chats</h2>
  <ul>
    <?php foreach ($threads as $t): ?>
    <li>
      <a href="<?= $base ?>/chat.php?id=<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
      <div class="meta"><?= $t['created_at'] ?></div>
    </li>
    <?php endforeach; ?>
    <?php if (!$threads): ?>
    <li style="color:#888;">none yet</li>
    <?php endif; ?>
  </ul>
</body>
</html>
