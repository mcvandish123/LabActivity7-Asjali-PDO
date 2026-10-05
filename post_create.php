<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$userId = require_auth();

$errors = [];
$old = ['title' => '', 'body' => ''];

if (is_post_request()) {
    verify_csrf();

    $title = input_string($_POST, 'title');
    $body  = input_string($_POST, 'body');
    $old = ['title' => $title, 'body' => $body];

    $errors = validate_post($title, $body);

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $title, $body]);
            $postId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        flash('success', 'Your post has been published.');
        redirect('post.php?id=' . $postId);
    }
}

$pageTitle = 'New post';
$formAction = 'post_create.php';
$submitLabel = 'Publish';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h1>Write a post</h1>
    <?php require __DIR__ . '/includes/post_form.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
