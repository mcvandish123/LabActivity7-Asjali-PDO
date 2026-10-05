<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

$postId = int_param($_GET, 'id');
if ($postId === null) {
    abort(404, 'Post not found.');
}

$stmt = $pdo->prepare('SELECT id, user_id, title, body FROM posts WHERE id = ?');
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    abort(404, 'Post not found.');
}
if ((int) $post['user_id'] !== $userId) {
    abort(403, 'You can only edit your own posts.');
}

$errors = [];
$old = ['title' => $post['title'], 'body' => $post['body']];

if (is_post_request()) {
    verify_csrf();

    $title = input_string($_POST, 'title');
    $body  = input_string($_POST, 'body');
    $old = ['title' => $title, 'body' => $body];

    $errors = validate_post($title, $body);

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // Re-read under a row lock: ownership is enforced at write time, not just page load.
            $stmt = $pdo->prepare('SELECT user_id, title, body FROM posts WHERE id = ? FOR UPDATE');
            $stmt->execute([$postId]);
            $current = $stmt->fetch();

            if (!$current) {
                $pdo->rollBack();
                abort(404, 'This post no longer exists.');
            }
            if ((int) $current['user_id'] !== $userId) {
                $pdo->rollBack();
                abort(403, 'You can only edit your own posts.');
            }

            // Only mark as edited when something actually changed.
            $changed = $current['title'] !== $title || $current['body'] !== $body;
            if ($changed) {
                $stmt = $pdo->prepare(
                    'UPDATE posts SET title = ?, body = ?, updated_at = CURRENT_TIMESTAMP
                      WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([$title, $body, $postId, $userId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        flash($changed ? 'success' : 'info', $changed ? 'Post updated.' : 'No changes to save.');
        redirect('post.php?id=' . $postId);
    }
}

$pageTitle = 'Edit post';
$formAction = 'post_edit.php?id=' . $postId;
$submitLabel = 'Save changes';
$cancelUrl = 'post.php?id=' . $postId;
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h1>Edit post</h1>
    <?php require __DIR__ . '/includes/post_form.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
