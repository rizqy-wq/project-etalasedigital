<?php
require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    $passwordIsValid = $user && password_verify($password, $user['password']);
    $legacyPasswordIsValid = $user && !$passwordIsValid &&
        is_string($user['password']) && hash_equals($user['password'], $password);

    if ($passwordIsValid || $legacyPasswordIsValid) {
        if ($legacyPasswordIsValid) {
            $upgrade = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $upgrade->execute([
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => $user['id'],
            ]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        header('Location: ../index.php');
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Etalase Digital Stella</title>
    <link rel="icon" type="image/png" href="../asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="../asset/css/auth.css">
</head>
<body class="auth-wrapper">
<main class="auth-card">
    <a class="auth-brand" href="../index.php" aria-label="Etalase Digital Stella">
        <img class="brand-logo" src="../asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
    </a>
    <h2>Login Etalase</h2>
    <?php if (isset($_GET['daftar'])): ?><p class="success-message">Pendaftaran berhasil. Silakan login.</p><?php endif; ?>
    <?php if (isset($_GET['logout'])): ?><p class="success-message">Anda berhasil logout.</p><?php endif; ?>
    <?php if ($error): ?><p class="alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-control">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" autocomplete="email" required>
        </div>
        <div class="form-control">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>
    <p class="auth-footer">Siswa baru? <a href="register.php">Daftar Akun</a></p>
</main>
</body>
</html>
