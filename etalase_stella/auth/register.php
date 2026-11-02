<?php
require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $namaInput = $_POST['nama'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $nama = is_string($namaInput) ? trim($namaInput) : '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $passwordInput = is_string($passwordInput) ? $passwordInput : '';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($passwordInput) < 8) {
        $error = 'Nama wajib diisi, email harus valid, dan password minimal 8 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'siswa')");
            $stmt->execute([
                ':nama' => $nama,
                ':email' => $email,
                ':password' => password_hash($passwordInput, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php?daftar=berhasil');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } else {
                error_log('Registration failed: ' . $e->getMessage());
                $error = 'Pendaftaran gagal karena terjadi kesalahan. Silakan coba kembali.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Siswa - Etalase Digital Stella</title>
    <link rel="icon" type="image/png" href="../asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="../asset/css/auth.css">
</head>
<body class="auth-wrapper">
<main class="auth-card">
    <a class="auth-brand" href="../index.php" aria-label="Etalase Digital Stella">
        <img class="brand-logo" src="../asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
    </a>
    <h2>Registrasi Siswa</h2>
    <?php if ($error): ?><p class="alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-control">
            <label for="nama">Nama Lengkap</label>
            <input id="nama" type="text" name="nama" maxlength="100" autocomplete="name" required>
        </div>
        <div class="form-control">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="100" autocomplete="email" required>
        </div>
        <div class="form-control">
            <label for="password">Password (minimal 8 karakter)</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Daftar</button>
    </form>
    <p class="auth-footer">Sudah punya akun? <a href="login.php">Login</a></p>
</main>
</body>
</html>
