<?php
require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

check_role(['admin', 'super_admin']);

$projectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$studentCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Etalase Digital Stella</title>
    <link rel="icon" type="image/png" href="../asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="../asset/css/user.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="../index.php" aria-label="Etalase Digital Stella">
        <img class="brand-logo" src="../asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
        <h2>Dashboard <?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></h2>
    </a>
    <nav class="navbar-user">
        <a href="../index.php" class="btn btn-primary">Lihat Etalase</a>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <a href="users.php" class="btn btn-warning">Kelola Users</a>
        <?php endif; ?>
        <form method="POST" action="../auth/logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>
    </nav>
</header>
<main class="form-container">
    <h2>Ringkasan Etalase Digital</h2>
    <div class="table-container">
        <table class="custom-table">
            <tr><th>Data</th><th>Jumlah</th></tr>
            <tr><td>Total karya</td><td><?= $projectCount ?></td></tr>
            <tr><td>Total siswa</td><td><?= $studentCount ?></td></tr>
            <tr><td>Total admin</td><td><?= $adminCount ?></td></tr>
        </table>
    </div>
</main>
</body>
</html>
