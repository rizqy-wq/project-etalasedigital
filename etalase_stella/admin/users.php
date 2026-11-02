<?php
require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';
require_once __DIR__ . '/../middleware/project_upload.php';

// Memastikan hanya Super Admin yang bisa mengakses halaman ini
check_role(['super_admin']);

// Tambah Admin Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_admin'])) {
    verify_csrf_token();
    $namaInput = $_POST['nama'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $nama = is_string($namaInput) ? trim($namaInput) : '';
    $email = is_string($emailInput) ? trim($emailInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $error = 'Nama wajib diisi, email harus valid, dan password minimal 8 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'admin')");
            $stmt->execute([
                ':nama' => $nama,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: users.php?admin=ditambahkan');
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                error_log('Admin creation failed: ' . $e->getMessage());
                $error = 'Admin tidak dapat ditambahkan karena terjadi kesalahan.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_user'])) {
    verify_csrf_token();
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId === null) {
        http_response_code(400);
        exit('ID pengguna tidak valid.');
    }

    $projectQuery = $pdo->prepare('SELECT gambar FROM projects WHERE user_id = :id');
    $projectQuery->execute([':id' => $userId]);
    $imageNames = $projectQuery->fetchAll(PDO::FETCH_COLUMN);

    $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = :id AND role <> 'super_admin' AND id <> :current_user");
    $deleteUser->execute([':id' => $userId, ':current_user' => $_SESSION['user_id']]);
    if ($deleteUser->rowCount() > 0) {
        foreach ($imageNames as $imageName) {
            delete_project_image($imageName);
        }
    }
    header('Location: users.php');
    exit();
}

$users = $pdo->query("SELECT id, nama, email, role FROM users ORDER BY role ASC, nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola User - Super Admin</title>
    <link rel="icon" type="image/png" href="../asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="../asset/css/form.css">
    <link rel="stylesheet" href="../asset/css/user.css">
</head>
<body>
<main class="form-container">
<a class="form-page-brand" href="../index.php" aria-label="Etalase Digital Stella">
    <img class="brand-logo" src="../asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
</a>
<a href="../index.php" class="btn btn-secondary">&larr; Kembali ke Etalase</a>
<h2>Manajemen User (Super Admin Only)</h2>

<section class="user-form-card">
    <h3>Tambah Admin Baru</h3>
    <?php if (!empty($error)): ?><p class="alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="nama">Nama Admin</label>
            <input id="nama" type="text" name="nama" maxlength="100" required>
        </div>
        <div class="form-group">
            <label for="email">Email Admin</label>
            <input id="email" type="email" name="email" maxlength="100" required>
        </div>
        <div class="form-group">
            <label for="password">Password (minimal 8 karakter)</label>
            <input id="password" type="password" name="password" minlength="8" required>
        </div>
        <button type="submit" name="tambah_admin" class="btn btn-primary">Tambah Admin</button>
    </form>
</section>

<div class="table-container">
<table class="custom-table">
    <tr>
        <th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th>
    </tr>
    <?php foreach($users as $u): ?>
    <tr>
        <td><?= (int) $u['id'] ?></td>
        <td><?= htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><b class="badge-role badge-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></b></td>
        <td>
            <?php if ($u['role'] !== 'super_admin' && (int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                <form method="POST" action="" onsubmit="return confirm('Hapus user ini?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" name="hapus_user" class="btn btn-danger">Hapus</button>
                </form>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
</main>
</body>
</html>
