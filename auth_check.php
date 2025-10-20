<?php
// Start the session if it's not already started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireLogin($redirect_url = 'login.php') {
    // Check if user is logged in
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        // Store the current page to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: ' . $redirect_url);
        exit();
    }
    
    // Optional: Check session timeout (e.g., 2 hours)
    $session_timeout = 2 * 60 * 60; // 2 hours in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout)) {
        // Session expired
        session_destroy();
        header('Location: ' . $redirect_url . '?expired=true');
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

function getCurrentUser() {
    if (isset($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireAdmin($redirect_url = 'login.php') {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ' . $redirect_url);
        exit();
    }
}

function getSessionInfo() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'email' => $_SESSION['user_email'] ?? 'Unknown',
        'login_time' => $_SESSION['login_time'] ?? time(),
        'last_activity' => $_SESSION['last_activity'] ?? time()
    ];
}
?>
