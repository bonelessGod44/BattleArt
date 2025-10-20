<?php
session_start();
require_once "config.php";

function requireAdmin() {
    // Check if session is active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and is an admin
    if (!isset($_SESSION['user_id']) || 
        !isset($_SESSION['user_type']) || 
        $_SESSION['user_type'] !== 'admin' || 
        !isset($_SESSION['loggedin']) || 
        $_SESSION['loggedin'] !== true) {
        
        // Clear any existing session data
        session_unset();
        session_destroy();
        
        header("Location: login.php");
        exit;
    }
}
?>