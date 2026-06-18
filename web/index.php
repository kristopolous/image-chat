<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
  $title = trim($_POST['title']);
  if ($title !== '') {
    $hash = null;
    $pass = trim($_POST['password'] ?? '');
    if ($pass !== '') $hash = password_hash($pass, PASSWORD_DEFAULT);
    $style = trim($_POST['style'] ?? '');
    if ($style === '') $style = 'default';
    $stmt = $pdo->prepare('INSERT INTO image_chats (title, password_hash, style) VALUES (?, ?, ?)');
    $stmt->execute([$title, $hash, $style]);
    header('Location: ' . $base . '/' . $pdo->lastInsertId());
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
<link rel="stylesheet" href="<?= $base ?>/style.css">
</head>
<body>
  <h1>image-chat</h1>
  <p class="subhead">a comment thread that lives inside an image</p>

  <p class="intro">
    image-chat is a new concept. You create a chat that exists as an image
    that you can include and embed in any site.
  </p>

  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <div class="step-body">
        <strong>Create a chat</strong> — give it a title, get an <code>&lt;img&gt;</code> tag
      </div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-body">
        <strong>Embed the image</strong> anywhere — GitHub README, forum post, email signature, wherever images work
      </div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-body">
        <strong>People scan the QR</strong> in the corner, land on a comment page, write something
      </div>
    </div>
    <div class="step">
      <div class="step-num">4</div>
      <div class="step-body">
        <strong>Refresh the image</strong> — their comment is now rendered directly onto it. The image <em>is</em> the thread.
      </div>
    </div>
  </div>

  <hr class="divider">

  <div class="create-box">
    <label for="title">start one</label>
    <form method="post" class="create-form">
      <input type="text" name="title" id="title" placeholder="name your chat" required autofocus class="input-full input-full-wrap">
      <div class="flex-row">
        <input type="text" name="password" placeholder="password (optional, for restricted posting)" class="input-flex">
        <button type="submit" class="btn">create</button>
      </div>
      <div>
        <select name="style" class="input-full">
          <option value="default">default</option>
          <option value="vintage">vintage</option>
          <option value="candy">candy</option>
          <option value="group-text">group-text</option>
        </select>
      </div>
    </form>
  </div>

  <h2>recent image-chats</h2>
  <ul class="thread-list">
    <?php foreach ($threads as $t): ?>
    <li>
      <a href="<?= $base ?>/<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
      <div class="meta"><?= $t['created_at'] ?></div>
    </li>
    <?php endforeach; ?>
    <?php if (!$threads): ?>
    <li class="empty">none yet</li>
    <?php endif; ?>
  </ul>
  <p class="footer">
    <a href="https://github.com/kristopolous/image-chat">source code</a>
  </p>
</body>
</html>
