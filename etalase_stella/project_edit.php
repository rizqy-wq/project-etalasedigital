<?php
require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';
require_once __DIR__ . '/middleware/project_upload.php';

check_login();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('ID karya tidak valid.');
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(404);
    exit('Data karya tidak ditemukan.');
}

if ($_SESSION['role'] === 'siswa' && (int) $project['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    exit('Akses ditolak: Anda tidak dapat mengedit karya milik siswa lain.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $namaInput = $_POST['nama_project'] ?? '';
    $deskripsiInput = $_POST['deskripsi'] ?? '';
    $linkInput = $_POST['link_project'] ?? '';
    $namaProject = is_string($namaInput) ? trim($namaInput) : '';
    $deskripsi = is_string($deskripsiInput) ? trim($deskripsiInput) : '';
    $linkProject = is_string($linkInput) ? trim($linkInput) : '';
    $newImageName = null;

    if ($namaProject === '' || strlen($namaProject) > 450 || $deskripsi === '' ||
        !filter_var($linkProject, FILTER_VALIDATE_URL) ||
        !in_array(parse_url($linkProject, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $error = 'Nama karya (maksimal 150 karakter), deskripsi, dan tautan HTTP/HTTPS yang valid wajib diisi.';
    } else {
        try {
            $imageName = $project['gambar'];
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newImageName = save_project_image($_FILES['gambar']);
                $imageName = $newImageName;
            }

            $stmt = $pdo->prepare(
                'UPDATE projects
                 SET nama_project = :nama, deskripsi = :deskripsi, gambar = :gambar, link_project = :link
                 WHERE id = :id'
            );
            $stmt->execute([
                ':nama' => $namaProject,
                ':deskripsi' => $deskripsi,
                ':gambar' => $imageName,
                ':link' => $linkProject,
                ':id' => $id,
            ]);

            if ($newImageName !== null) {
                delete_project_image($project['gambar']);
            }
            header('Location: index.php?status=karya-diperbarui');
            exit();
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($newImageName !== null) {
                delete_project_image($newImageName);
            }
            error_log('Project update failed: ' . $e->getMessage());
            $error = 'Perubahan karya gagal disimpan karena terjadi kesalahan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Karya - Etalase Digital Stella</title>
    <link rel="icon" type="image/png" href="asset/images/logo-smk-telkom-lampung.png">
    <link rel="stylesheet" href="asset/css/style.css">
    <link rel="stylesheet" href="asset/css/form.css">
</head>
<body>
<main class="form-container">
    <a class="form-page-brand" href="index.php" aria-label="Etalase Digital Stella">
        <img class="brand-logo" src="asset/images/logo-smk-telkom-lampung.png" alt="Logo SMK Telkom Lampung">
    </a>
    <a href="index.php" class="btn btn-secondary">Kembali ke Etalase</a>
    <h2>Edit Karya Digital</h2>
    <?php if ($error !== ''): ?><p class="alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="nama_project">Nama Karya</label>
            <input id="nama_project" type="text" name="nama_project" maxlength="150" value="<?= htmlspecialchars(is_string($_POST['nama_project'] ?? null) ? $_POST['nama_project'] : $project['nama_project'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" required><?= htmlspecialchars(is_string($_POST['deskripsi'] ?? null) ? $_POST['deskripsi'] : $project['deskripsi'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="form-group">
            <label for="link_project">Link Project</label>
            <input id="link_project" type="url" name="link_project" value="<?= htmlspecialchars(is_string($_POST['link_project'] ?? null) ? $_POST['link_project'] : $project['link_project'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="gambar">Ganti Gambar (JPG, PNG, WEBP; maksimal 2MB)</label>
            <input id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
        </div>
        <button type="submit" class="btn btn-warning" style="width:100%;">Simpan Perubahan</button>
    </form>
</main>
</body>
</html>
