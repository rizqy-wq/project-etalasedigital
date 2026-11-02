<?php
require_once 'config/kuis_db.php';
require_once 'middleware/auth_check.php';

check_login();

// Ambil semua karya beserta nama pembuat
$stmt = $pdo->query("SELECT projects.*, users.nama AS pembuat 
                     FROM projects 
                     JOIN users ON projects.user_id = users.id 
                     ORDER BY projects.created_at DESC");
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etalase Digital Stella</title>
    <link rel="icon" type="image/png" href="asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="asset/css/main.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="index.php" aria-label="Etalase Digital Stella">
        <img class="brand-logo" src="asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
        <h2>Etalase Digital Stella</h2>
    </a>
    <nav class="navbar-user" aria-label="Navigasi akun">
        <span>Halo, <b><?= htmlspecialchars($_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?></b> (<?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?>)</span>
        <a href="project_tambah.php" class="btn btn-primary">+ Tambah Karya</a>
        <?php if ($_SESSION['role'] === 'super_admin'): ?>
            <a href="admin/users.php" class="btn btn-warning">Kelola Users</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin'): ?>
            <a href="admin/index.php" class="btn btn-secondary">Dashboard</a>
        <?php endif; ?>
        <form method="POST" action="auth/logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>
    </nav>
</header>

<main class="container">
    <h1>Karya Digital Siswa</h1>
    <?php if (!$projects): ?>
        <p class="empty-state">Belum ada karya yang ditambahkan. Jadilah yang pertama membagikan karya!</p>
    <?php else: ?>
        <div class="grid-projects">
            <?php foreach ($projects as $p): ?>
                <article class="card-project">
                    <img class="card-img-top" src="uploads/<?= rawurlencode($p['gambar']) ?>" alt="Pratinjau <?= htmlspecialchars($p['nama_project'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-body">
                        <h2 class="card-title"><?= htmlspecialchars($p['nama_project'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="card-author">Oleh: <b><?= htmlspecialchars($p['pembuat'], ENT_QUOTES, 'UTF-8') ?></b></p>
                        <p class="card-text"><?= nl2br(htmlspecialchars($p['deskripsi'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <div class="card-actions">
                            <a href="<?= htmlspecialchars($p['link_project'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Lihat Project</a>
                            <?php if ($_SESSION['role'] !== 'siswa' || (int) $_SESSION['user_id'] === (int) $p['user_id']): ?>
                                <a href="project_edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-warning">Edit</a>
                                <form method="POST" action="project_hapus.php" onsubmit="return confirm('Hapus karya ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
