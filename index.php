<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

const PER_PAGE = 10;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM posts');
$stmt->execute();
$totalPosts = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalPosts / PER_PAGE));
$page = min(int_param($_GET, 'page') ?? 1, $totalPages);

// News feed: every user's posts, newest first (id breaks ties within the same second).
$stmt = $pdo->prepare(
    'SELECT p.id, p.user_id, p.title, p.body, p.created_at, p.updated_at,
            u.name AS author,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
       FROM posts p
       JOIN users u ON u.id = p.user_id
      ORDER BY p.created_at DESC, p.id DESC
      LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * PER_PAGE, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = 'News feed';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1>News feed</h1>
    <a href="post_create.php" class="btn">Write a post</a>
</div>

<?php if (!$posts): ?>
    <div class="card empty">
        <p>No posts yet. <a href="post_create.php">Be the first to write one.</a></p>
    </div>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <article class="card post">
        <h2><a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h2>
        <?php require __DIR__ . '/includes/post_meta.php'; ?>
        <p class="excerpt"><?= nl2br(e(mb_strimwidth($post['body'], 0, 300, '…'))) ?></p>
        <div class="post-footer">
            <a href="post.php?id=<?= (int) $post['id'] ?>">
                <?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?>
            </a>
            <?php if ((int) $post['user_id'] === $userId): ?>
                <a href="post_edit.php?id=<?= (int) $post['id'] ?>">Edit</a>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>

<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="btn btn-ghost">&larr; Newer</a>
        <?php endif; ?>
        <span class="muted">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="btn btn-ghost">Older &rarr;</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
