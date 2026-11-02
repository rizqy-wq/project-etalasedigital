<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function check_login(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if (in_array(basename($scriptDirectory), ['auth', 'admin'], true)) {
            $scriptDirectory = dirname($scriptDirectory);
        }
        $baseUrl = rtrim($scriptDirectory, '/');
        header('Location: ' . $baseUrl . '/auth/login.php');
        exit();
    }
}

function check_role(array $roles): void
{
    check_login();

    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak: Anda tidak memiliki hak akses ke halaman ini.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}
