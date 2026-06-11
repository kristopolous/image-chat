<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();
$base = dirname($_SERVER['SCRIPT_NAME']);

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
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;
    max-width: 640px; margin: 40px auto; padding: 0 20px;
    color: #1a1a1a;
    line-height: 1.6;
  }
  h1 { font-size: 28px; font-weight: 700; margin-bottom: 4px; }
  .subhead { font-size: 15px; color: #666; margin-bottom: 32px; }

  .steps { margin-bottom: 32px; }
  .step { display: flex; gap: 14px; margin-bottom: 14px; padding: 14px 16px; background: #f4f4f6; border-radius: 10px; }
  .step-num { font-weight: 700; font-size: 14px; color: #888; min-width: 24px; }
  .step-body { font-size: 14px; color: #333; }
  .step-body strong { color: #111; }
  .step-body code { background: #e4e4e6; padding: 2px 6px; border-radius: 4px; font-size: 12px; }

  .divider { border: none; border-top: 1px solid #ddd; margin: 28px 0; }

  .create-box { margin-bottom: 28px; }
  .create-box label { font-size: 14px; font-weight: 600; display: block; margin-bottom: 8px; }
  .create-row { display: flex; gap: 8px; }
  .create-row input[type="text"] {
    flex: 1; padding: 10px 14px; font-size: 15px;
    border: 1px solid #ccc; border-radius: 8px;
  }
  .create-row button {
    padding: 10px 20px; font-size: 14px; border: none;
    border-radius: 8px; background: #333; color: #fff; cursor: pointer;
    white-space: nowrap;
  }
  .create-row button:hover { background: #555; }

  h2 { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
  ul { list-style: none; }
  li a {
    display: block; padding: 12px 16px; background: #f4f4f6; border-radius: 8px;
    margin-bottom: 6px; color: #333; text-decoration: none; font-weight: 500;
  }
  li a:hover { background: #e4e4e6; }
  .meta { font-size: 12px; color: #888; margin-top: 2px; font-weight: 400; }
</style>
</head>
<body>
  <h1>image-chat</h1>
  <p class="subhead">a comment thread that lives inside an image</p>

  <p style="margin-bottom:24px; font-size:14px; line-height:1.6; color:#444;">
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
    <form method="post" style="margin-bottom: 28px;">
      <input type="text" name="title" id="title" placeholder="name your chat" required autofocus style="width:100%; padding:10px 14px; font-size:15px; border:1px solid #ccc; border-radius:8px; margin-bottom:8px;">
      <div style="display:flex; gap:8px; margin-bottom:8px;">
        <input type="text" name="password" placeholder="password (optional, for restricted posting)" style="flex:1; padding:10px 14px; font-size:15px; border:1px solid #ccc; border-radius:8px;">
        <button type="submit" style="padding:10px 20px; font-size:14px; border:none; border-radius:8px; background:#333; color:#fff; cursor:pointer; white-space:nowrap;">create</button>
      </div>
      <div>
        <select name="style" style="width:100%; padding:10px 14px; font-size:14px; border:1px solid #ccc; border-radius:8px;">
          <option value="default">default</option>
          <option value="vintage">vintage</option>
          <option value="candy">candy</option>
          <option value="group-text">group-text</option>
        </select>
      </div>
    </form>
  </div>

  <h2>recent image-chats</h2>
  <ul>
    <?php foreach ($threads as $t): ?>
    <li>
      <a href="<?= $base ?>/<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
      <div class="meta"><?= $t['created_at'] ?></div>
    </li>
    <?php endforeach; ?>
    <?php if (!$threads): ?>
    <li style="color:#888;">none yet</li>
    <?php endif; ?>
  </ul>
  <p style="margin-top:32px; font-size:12px; color:#aaa; text-align:center;">
    <a href="https://github.com/kristopolous/image-chat" style="color:#aaa;">source code</a>
  </p>
</body>
</html>
