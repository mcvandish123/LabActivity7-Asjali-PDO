<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Logging out changes state, so it's POST + CSRF only (a GET link could be triggered by any site).
if (!is_post_request()) {
    redirect('index.php');
}
verify_csrf();

logout_user();
session_start();
flash('success', 'You have been logged out.');
redirect('login.php');
