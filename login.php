<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$oldEmail = '';

if (is_post_request()) {
    verify_csrf();

    $email    = mb_strtolower(input_string($_POST, 'email'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $oldEmail = $email;

    $errors = validate_login($email, $password);

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify against a dummy hash when the user doesn't exist so response
        // time doesn't reveal which emails are registered.
        $hash = $user['password'] ?? '$2y$10$CNtpI08h.puMqk0PsmzPE.Glgu9OhhS6THJMRqgoqqnJndW2Lu26.';
        $valid = password_verify($password, $hash) && $user !== false;

        if (!$valid) {
            $errors['form'] = 'Invalid email or password.';
        } else {
            // Transparently upgrade the hash if PHP's default algorithm/cost changed.
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    error_log('Password rehash failed: ' . $e->getMessage()); // non-fatal
                }
            }

            login_user((int) $user['id'], $user['name']);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
    <h1>Log in</h1>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" class="js-validate" novalidate>
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>"
               required maxlength="<?= EMAIL_MAX ?>" autocomplete="email" autofocus>
        <?= field_error($errors, 'email') ?>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <?= field_error($errors, 'password') ?>

        <button type="submit" class="btn btn-block">Log in</button>
    </form>
    <p class="muted center">No account yet? <a href="register.php">Register</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
