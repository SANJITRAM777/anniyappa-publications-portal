<?php
// Session logout script
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    // Reject forged logout attempts (e.g. <img> tags embedded by third parties)
    header("Location: /index.php");
    exit;
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookies if active
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to home page
header("Location: /index.php");
exit;
?>
