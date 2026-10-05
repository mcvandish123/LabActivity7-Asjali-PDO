<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

if (!is_post_request()) {
    redirect('index.php');
}
verify_csrf();

$commentId = int_param($_POST, 'id');
if ($commentId === null) {
    abort(404, 'Comment not found.');
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT user_id, post_id FROM comments WHERE id = ? FOR UPDATE');
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        $pdo->rollBack();
        abort(404, 'Comment not found.');
    }
    if ((int) $comment['user_id'] !== $userId) {
        $pdo->rollBack();
        abort(403, 'You can only delete your own comments.');
    }

    $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
    $stmt->execute([$commentId, $userId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

flash('success', 'Comment deleted.');
redirect('post.php?id=' . (int) $comment['post_id'] . '#comment-form');
