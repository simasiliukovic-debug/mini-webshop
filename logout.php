<?php
/**
 * ModStore — Logout
 */
session_start();
require_once __DIR__ . '/includes/functions.php';

// Destroy session fully
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Start new session for flash message
session_start();
setFlash('info', 'You have been signed out.');
header('Location: index.php');
exit;
