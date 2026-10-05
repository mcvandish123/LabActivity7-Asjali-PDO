<?php
/**
 * Server-side validation. These limits are mirrored in the HTML attributes
 * (required / minlength / maxlength) so client and server agree.
 *
 * Each validator returns an array of field => error message (empty = valid).
 */
declare(strict_types=1);

const NAME_MIN = 2;
const NAME_MAX = 100;
const EMAIL_MAX = 255;
const PASSWORD_MIN = 8;
const PASSWORD_MAX = 72;   // bcrypt only uses the first 72 bytes
const TITLE_MIN = 3;
const TITLE_MAX = 255;
const POST_BODY_MIN = 10;
const POST_BODY_MAX = 10000;
const COMMENT_MAX = 2000;

/**
 * Trimmed string from input, or '' if missing / not a string.
 * Line endings are normalized so server length checks match the browser's maxlength.
 */
function input_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
}

function check_length(array &$errors, string $field, string $label, string $value, int $min, int $max): void
{
    $len = mb_strlen($value);
    if ($len === 0) {
        $errors[$field] = "$label is required.";
    } elseif ($len < $min) {
        $errors[$field] = "$label must be at least $min characters.";
    } elseif ($len > $max) {
        $errors[$field] = "$label must be at most $max characters.";
    }
}

function validate_registration(string $name, string $email, string $password, string $confirm): array
{
    $errors = [];
    check_length($errors, 'name', 'Name', $name, NAME_MIN, NAME_MAX);

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (mb_strlen($email) > EMAIL_MAX || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < PASSWORD_MIN) {
        $errors['password'] = 'Password must be at least ' . PASSWORD_MIN . ' characters.';
    } elseif (strlen($password) > PASSWORD_MAX) {
        $errors['password'] = 'Password must be at most ' . PASSWORD_MAX . ' characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }

    if ($confirm !== $password) {
        $errors['password_confirmation'] = 'Passwords do not match.';
    }
    return $errors;
}

function validate_login(string $email, string $password): array
{
    $errors = [];
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }
    return $errors;
}

function validate_post(string $title, string $body): array
{
    $errors = [];
    check_length($errors, 'title', 'Title', $title, TITLE_MIN, TITLE_MAX);
    check_length($errors, 'body', 'Body', $body, POST_BODY_MIN, POST_BODY_MAX);
    return $errors;
}

function validate_comment(string $body): array
{
    $errors = [];
    check_length($errors, 'body', 'Comment', $body, 1, COMMENT_MAX);
    return $errors;
}

/** Render the error for a field, if any. */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<p class="field-error" role="alert">' . e($errors[$field]) . '</p>'
        : '';
}
