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

<<<<<<< Updated upstream
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
=======
$base = dirname(dirname($_SERVER['SCRIPT_NAME']));
>>>>>>> Stashed changes
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
  . '://' . $_SERVER['HTTP_HOST'] . $base;
$restricted = $thread['password_hash'] !== null;
$unlocked = $restricted && !empty($_SESSION['unlocked_' . $id]);
$error = null;

$cacheDir = __DIR__ . '/cache';
function clear_cache($id) {
  global $cacheDir;
  foreach (['png', 'webp', 'avif'] as $ext) {
    $f = "$cacheDir/$id.$ext";
    if (file_exists($f)) unlink($f);
  }
}

if ($restricted && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_pass'])) {
  if (password_verify($_POST['unlock_pass'], $thread['password_hash'])) {
    $_SESSION['unlocked_' . $id] = true;
    header('Location: ' . $base . '/' . $id);
    exit;
  } else {
    $error = 'wrong password';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_title'])) {
  $newTitle = trim($_POST['edit_title']);
  $newStyle = trim($_POST['edit_style'] ?? '');
  if ($newTitle !== '') {
    $stmt = $pdo->prepare('UPDATE image_chats SET title = ?, style = ? WHERE id = ?');
    $stmt->execute([$newTitle, $newStyle ?: 'default', $id]);
    clear_cache($id);
    header('Location: ' . $base . '/' . $id);
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['author']) && (!$restricted || $unlocked)) {
  $author = trim($_POST['author']);
  $body   = trim($_POST['body'] ?? '');
  if ($author !== '' && $body !== '') {
    $stmt = $pdo->prepare('INSERT INTO comments (thread_id, author, body) VALUES (?, ?, ?)');
    $stmt->execute([$id, $author, $body]);
    clear_cache($id);
    header('Location: ' . $base . '/' . $id);
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
<link rel="stylesheet" href="<?= $base ?>/style.css">
</head>
<body>
  <aside class="banner">
  <p>
    <b>Image-chat</b> is a new concept: A comment thread that lives inside an image.
  </p>
  <p>
    This means you can have comment threads in places you normally wouldn't, such as in your GitHub README. It will work on any site where you can include an image with an external URL.</p>
  <center>  <a href="/image-chat/">Create one!</a></center>
  </p>
</aside>
  <div class="embed embed-wrap">
    URL to include for this chat:
    <strong><a href="<?= $baseUrl ?>/image/<?= $id ?>.webp"><?= $baseUrl ?>/image/<?= $id ?>.webp</a></strong>
  </div>
  <div class="title-row">
    <h1 class="chat-h1" id="title-display"><?= htmlspecialchars($thread['title']) ?></h1>
    <span class="pencil" id="pencil">✏️</span>
  </div>
  <form class="title-edit" id="title-form" method="post">
    <input type="text" name="edit_title" id="title-input" value="<?= htmlspecialchars($thread['title']) ?>" required>
    <select name="edit_style">
      <option value="default" <?= $thread['style'] === 'default' ? 'selected' : '' ?>>default</option>
      <option value="vintage" <?= $thread['style'] === 'vintage' ? 'selected' : '' ?>>vintage</option>
      <option value="candy" <?= $thread['style'] === 'candy' ? 'selected' : '' ?>>candy</option>
      <option value="group-text" <?= $thread['style'] === 'group-text' ? 'selected' : '' ?>>group-text</option>
    </select>
    <button type="submit">update</button>
  </form>
  <div class="meta-info">
    style: <?= htmlspecialchars($thread['style'] ?? 'default') ?>
    <?php if ($restricted): ?>· restricted<?php endif; ?>
  </div>

  <div class="img-wrap">
    <img src="<?= $base ?>/image/<?= $id ?>.png?v=<?= time() ?>" alt="<?= htmlspecialchars($thread['title']) ?>"
         width="827" height="1166" class="thread-img">
  </div>

  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

  <?php if ($restricted && !$unlocked): ?>
  <form method="post" class="chat-form">
    <input type="text" name="unlock_pass" placeholder="password required to comment" required autofocus>
    <button type="submit">unlock</button>
  </form>
  <?php else: ?>
  <form method="post" class="chat-form">
    <input type="text" name="author" id="author" placeholder="your name" maxlength="20" required>
    <textarea name="body" placeholder="write something..." maxlength="250" required></textarea>
    <button type="submit">comment</button>
  </form>
  <?php endif; ?>

  <script>
    const pencil = document.getElementById('pencil');
    const titleForm = document.getElementById('title-form');
    const titleDisplay = document.getElementById('title-display');
    const titleInput = document.getElementById('title-input');
    pencil.addEventListener('click', () => {
      titleForm.style.display = 'block';
      pencil.style.display = 'none';
      titleDisplay.style.display = 'none';
      titleInput.focus();
      titleInput.select();
    });
    const author = document.getElementById('author');
    if (author) {
      const saved = localStorage.getItem('ichat_author');
      if (saved) author.value = saved;
      author.addEventListener('input', () => localStorage.setItem('ichat_author', author.value));
    }
  </script>
  <p class="footer">
    <a href="https://github.com/kristopolous/image-chat">source code</a>
  </p>
</body>
</html>
