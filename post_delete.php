<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

if (!is_post_request()) {
    redirect('index.php');
}
verify_csrf();

$postId = int_param($_POST, 'id');
if ($postId === null) {
    abort(404, 'Post not found.');
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ? FOR UPDATE');
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        $pdo->rollBack();
        abort(404, 'Post not found.');
    }
    if ((int) $post['user_id'] !== $userId) {
        $pdo->rollBack();
        abort(403, 'You can only delete your own posts.');
    }

    // Comments are removed by the ON DELETE CASCADE foreign key.
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

flash('success', 'Post deleted.');
redirect('index.php');
