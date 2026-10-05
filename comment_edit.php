<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

$commentId = int_param($_GET, 'id');
if ($commentId === null) {
    abort(404, 'Comment not found.');
}

$stmt = $pdo->prepare(
    'SELECT c.id, c.user_id, c.post_id, c.body, p.title AS post_title
       FROM comments c
       JOIN posts p ON p.id = c.post_id
      WHERE c.id = ?'
);
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if (!$comment) {
    abort(404, 'Comment not found.');
}
if ((int) $comment['user_id'] !== $userId) {
    abort(403, 'You can only edit your own comments.');
}

$postUrl = 'post.php?id=' . (int) $comment['post_id'];
$errors = [];
$oldBody = $comment['body'];

if (is_post_request()) {
    verify_csrf();

    $body = input_string($_POST, 'body');
    $oldBody = $body;
    $errors = validate_comment($body);

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT user_id, body FROM comments WHERE id = ? FOR UPDATE');
            $stmt->execute([$commentId]);
            $current = $stmt->fetch();

            if (!$current) {
                $pdo->rollBack();
                abort(404, 'This comment no longer exists.');
            }
            if ((int) $current['user_id'] !== $userId) {
                $pdo->rollBack();
                abort(403, 'You can only edit your own comments.');
            }

            $changed = $current['body'] !== $body;
            if ($changed) {
                $stmt = $pdo->prepare(
                    'UPDATE comments SET body = ?, updated_at = CURRENT_TIMESTAMP
                      WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([$body, $commentId, $userId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        flash($changed ? 'success' : 'info', $changed ? 'Comment updated.' : 'No changes to save.');
        redirect($postUrl . '#comment-' . $commentId);
    }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h1>Edit comment</h1>
    <p class="muted">On: <a href="<?= e($postUrl) ?>"><?= e($comment['post_title']) ?></a></p>
    <form method="post" action="comment_edit.php?id=<?= $commentId ?>" class="js-validate" novalidate>
        <?= csrf_field() ?>
        <label for="body">Comment</label>
        <textarea id="body" name="body" rows="4"
                  required maxlength="<?= COMMENT_MAX ?>" data-counter><?= e($oldBody) ?></textarea>
        <?= field_error($errors, 'body') ?>
        <div class="actions">
            <button type="submit" class="btn">Save changes</button>
            <a href="<?= e($postUrl . '#comment-' . $commentId) ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
