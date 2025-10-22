<?php
// --- auth_check.php (CORRECTED) ---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in.
 * Use this for regular pages (e.g., user profile, settings)
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url);
        exit();
    }
    
    $session_timeout = 2 * 60 * 60; 
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout)) {
        session_destroy(); // This is correct
        header('Location: ' . $redirect_url . '?expired=true');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Checks if a user is logged in (returns true/false).
 */
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Secures admin pages.
 * Checks if user is logged in AND is an 'admin'.
 */
function requireAdmin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url);
        exit();
    }

    if (!isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'admin') {
        header('Location: index.php'); 
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        // --- THIS IS THE FIX ---
        // The session is corrupt. Destroy it *before* redirecting.
        session_destroy(); 
        // ---------------------
        
        header('Location: ' . $redirect_url . '?error=session_data_missing');
        exit();
    }

    // All checks passed. Return admin's ID for convenience.
    return $_SESSION['user_id'];
}

function getCurrentUser() {
    if (isset($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    return null;
}

function getSessionInfo() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'email' => $_SESSION['user_email'] ?? 'Unknown',
        'login_time' => $_SESSION['login_time'] ?? time(),
        'last_activity' => $_SESSION['last_activity'] ?? time(),
        'user_id' => $_SESSION['user_id'] ?? 'Unknown',
        'username' => $_SESSION['username'] ?? 'Unknown',
        'user_type' => $_SESSION['user_type'] ?? 'Unknown'
    ];
}
?>