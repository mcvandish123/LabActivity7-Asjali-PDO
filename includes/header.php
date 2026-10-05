<?php
/** @var string|null $pageTitle */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Home') . ' · Blog Site') ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="index.php">Blog Site</a>
        <nav>
            <?php if (is_authenticated()): ?>
                <span class="muted">Hi, <?= e(current_user_name()) ?></span>
                <a href="post_create.php" class="btn btn-small">New post</a>
                <form action="logout.php" method="post" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-small btn-ghost">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php" class="btn btn-small">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php foreach (take_flashes() as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
