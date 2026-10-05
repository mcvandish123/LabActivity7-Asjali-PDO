<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

$postId = int_param($_GET, 'id');
if ($postId === null) {
    abort(404, 'Post not found.');
}

$errors = [];
$oldComment = '';

// Add a comment to this post.
if (is_post_request()) {
    verify_csrf();

    $body = input_string($_POST, 'body');
    $oldComment = $body;
    $errors = validate_comment($body);

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // Share-lock the post so it can't be deleted between the check and the insert.
            $stmt = $pdo->prepare('SELECT id FROM posts WHERE id = ? LOCK IN SHARE MODE');
            $stmt->execute([$postId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                abort(404, 'This post no longer exists.');
            }

            $stmt = $pdo->prepare('INSERT INTO comments (user_id, post_id, body) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $postId, $body]);
            $commentId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        flash('success', 'Comment added.');
        redirect('post.php?id=' . $postId . '#comment-' . $commentId);
    }
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.user_id, p.title, p.body, p.created_at, p.updated_at, u.name AS author
       FROM posts p
       JOIN users u ON u.id = p.user_id
      WHERE p.id = ?'
);
$stmt->execute([$postId]);
$post = $stmt->fetch();
if (!$post) {
    abort(404, 'Post not found.');
}

$stmt = $pdo->prepare(
    'SELECT c.id, c.user_id, c.body, c.created_at, c.updated_at, u.name AS author
       FROM comments c
       JOIN users u ON u.id = c.user_id
      WHERE c.post_id = ?
      ORDER BY c.created_at ASC, c.id ASC'
);
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();

$isOwner = (int) $post['user_id'] === $userId;

$pageTitle = $post['title'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="index.php">&larr; Back to feed</a></p>

<article class="card post">
    <h1><?= e($post['title']) ?></h1>
    <?php require __DIR__ . '/includes/post_meta.php'; ?>
    <div class="post-body"><?= nl2br(e($post['body'])) ?></div>

    <?php if ($isOwner): ?>
        <div class="post-footer">
            <a href="post_edit.php?id=<?= (int) $post['id'] ?>" class="btn btn-small btn-ghost">Edit</a>
            <form action="post_delete.php" method="post" class="inline"
                  data-confirm="Delete this post and all its comments? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <button type="submit" class="btn btn-small btn-danger">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</article>

<section class="card">
    <h2>Comments (<?= count($comments) ?>)</h2>

    <?php if (!$comments): ?>
        <p class="muted">No comments yet. Start the conversation.</p>
    <?php endif; ?>

    <ul class="comments">
        <?php foreach ($comments as $comment): ?>
            <li class="comment" id="comment-<?= (int) $comment['id'] ?>">
                <p class="meta">
                    <strong><?= e($comment['author']) ?></strong>
                    · <time datetime="<?= e($comment['created_at']) ?>"><?= e(format_datetime($comment['created_at'])) ?></time>
                    <?php if ($comment['updated_at'] !== null): ?>
                        <span class="badge" title="Edited <?= e(format_datetime($comment['updated_at'])) ?>">Edited</span>
                    <?php endif; ?>
                </p>
                <p><?= nl2br(e($comment['body'])) ?></p>

                <?php if ((int) $comment['user_id'] === $userId): ?>
                    <div class="comment-actions">
                        <a href="comment_edit.php?id=<?= (int) $comment['id'] ?>">Edit</a>
                        <form action="comment_delete.php" method="post" class="inline"
                              data-confirm="Delete this comment?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                            <button type="submit" class="link-button danger">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <form method="post" action="post.php?id=<?= (int) $post['id'] ?>#comment-form" id="comment-form"
          class="js-validate" novalidate>
        <?= csrf_field() ?>
        <label for="comment-body">Add a comment</label>
        <textarea id="comment-body" name="body" rows="3"
                  required maxlength="<?= COMMENT_MAX ?>" data-counter><?= e($oldComment) ?></textarea>
        <?= field_error($errors, 'body') ?>
        <button type="submit" class="btn">Comment</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
