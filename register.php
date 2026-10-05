<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$old = ['name' => '', 'email' => ''];

if (is_post_request()) {
    verify_csrf();

    $name     = input_string($_POST, 'name');
    $email    = mb_strtolower(input_string($_POST, 'email'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
    $old = ['name' => $name, 'email' => $email];

    $errors = validate_registration($name, $email, $password, $confirm);

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock the email "slot" so two simultaneous sign-ups can't both pass the check.
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? FOR UPDATE');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $pdo->rollBack();
                $errors['email'] = 'That email is already registered.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int) $pdo->lastInsertId();
                $pdo->commit();

                login_user($userId, $name);
                flash('success', 'Welcome, ' . $name . '! Your account has been created.');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000 = integrity violation: the UNIQUE(email) constraint is the final safeguard.
            if ($e->getCode() === '23000') {
                $errors['email'] = 'That email is already registered.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
    <h1>Create an account</h1>
    <form method="post" action="register.php" class="js-validate" novalidate>
        <?= csrf_field() ?>

        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
               required minlength="<?= NAME_MIN ?>" maxlength="<?= NAME_MAX ?>" autocomplete="name">
        <?= field_error($errors, 'name') ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
               required maxlength="<?= EMAIL_MAX ?>" autocomplete="email">
        <?= field_error($errors, 'email') ?>

        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               required minlength="<?= PASSWORD_MIN ?>" maxlength="<?= PASSWORD_MAX ?>"
               pattern="(?=.*[A-Za-z])(?=.*\d).+"
               data-pattern-message="Password must contain at least one letter and one number."
               autocomplete="new-password">
        <p class="hint">At least <?= PASSWORD_MIN ?> characters, with a letter and a number.</p>
        <?= field_error($errors, 'password') ?>

        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               required data-match="password" autocomplete="new-password">
        <?= field_error($errors, 'password_confirmation') ?>

        <button type="submit" class="btn btn-block">Register</button>
    </form>
    <p class="muted center">Already have an account? <a href="login.php">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
