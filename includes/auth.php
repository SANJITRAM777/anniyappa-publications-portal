<?php
// Session management and authentication helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is currently authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the currently logged in user ID
 */
function get_logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the role name of the logged in user
 */
function get_logged_in_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role(s)
 * @param array|string $roles
 */
function has_role($roles) {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = get_logged_in_role();
    if (is_array($roles)) {
        return in_array($user_role, $roles);
    }
    return $user_role === $roles;
}

/**
 * Protect pages by requiring certain role(s)
 * @param array|string $roles
 */
function require_role($roles) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }
    if (!has_role($roles)) {
        http_response_code(403);
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light d-flex align-items-center justify-content-center vh-100'>
            <div class='card text-center p-5 shadow' style='max-width: 500px; border-radius: 15px;'>
                <div class='text-danger fs-1 mb-3'><i class='bi bi-shield-slash-fill'></i></div>
                <h1 class='h3 text-dark mb-3'>403 - Access Denied</h1>
                <p class='text-muted'>You do not have the required permissions to access this page.</p>
                <a href='/index.php' class='btn btn-primary rounded-pill mt-3'>Return to Home Page</a>
            </div>
        </body>
        </html>";
        exit;
    }
}

/**
 * Log in a user
 * @param string $email
 * @param string $password
 * @param PDO $pdo
 */
function attempt_login($email, $password, $pdo) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.password, r.name AS role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Fetch user profile details
        $profileStmt = $pdo->prepare("SELECT full_name, profile_pic FROM user_profiles WHERE user_id = ?");
        $profileStmt->execute([$user['id']]);
        $profile = $profileStmt->fetch();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_name'] = $profile ? $profile['full_name'] : 'User';
        $_SESSION['user_pic'] = $profile ? $profile['profile_pic'] : 'default_avatar.png';
        return true;
    }
    return false;
}
?>
