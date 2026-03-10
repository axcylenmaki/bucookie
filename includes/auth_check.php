<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 * Redirect ke login jika belum
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'user/login.php');
        exit;
    }
}

/**
 * Cek apakah yang login adalah admin
 * Redirect ke beranda jika bukan admin
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Cek apakah yang login adalah user biasa
 * Redirect ke beranda jika bukan user
 */
function requireUser() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'user') {
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
}

/**
 * Cek apakah sudah login (tanpa redirect)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah yang login adalah admin (tanpa redirect)
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
