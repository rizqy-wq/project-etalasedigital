<?php
require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';
require_once __DIR__ . '/middleware/project_upload.php';

check_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $namaInput = $_POST['nama_project'] ?? '';
    $deskripsiInput = $_POST['deskripsi'] ?? '';
    $linkInput = $_POST['link_project'] ?? '';
    $namaProject = is_string($namaInput) ? trim($namaInput) : '';
    $deskripsi = is_string($deskripsiInput) ? trim($deskripsiInput) : '';
    $linkProject = is_string($linkInput) ? trim($linkInput) : '';
    $imageName = null;

    if ($namaProject === '' || strlen($namaProject) > 450 || $deskripsi === '' ||
        !filter_var($linkProject, FILTER_VALIDATE_URL) ||
        !in_array(parse_url($linkProject, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $error = 'Nama karya (maksimal 150 karakter), deskripsi, dan tautan HTTP/HTTPS yang valid wajib diisi.';
    } else {
        try {
            $imageName = save_project_image($_FILES['gambar'] ?? []);
            $stmt = $pdo->prepare(
                'INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project)
                 VALUES (:user_id, :nama_project, :deskripsi, :gambar, :link_project)'
            );
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':nama_project' => $namaProject,
                ':deskripsi' => $deskripsi,
                ':gambar' => $imageName,
                ':link_project' => $linkProject,
            ]);
            header('Location: index.php?status=karya-ditambahkan');
            exit();
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($imageName !== null) {
                delete_project_image($imageName);
            }
            error_log('Project creation failed: ' . $e->getMessage());
            $error = 'Karya gagal disimpan karena terjadi kesalahan. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Karya - Etalase Digital Stella</title>
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
    <h2>Tambah Karya Digital Baru</h2>
    <?php if ($error !== ''): ?><p class="alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="nama_project">Nama Karya Digital</label>
            <input type="text" id="nama_project" name="nama_project" maxlength="150" value="<?= htmlspecialchars(is_string($_POST['nama_project'] ?? null) ? $_POST['nama_project'] : '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="deskripsi">Deskripsi Karya</label>
            <textarea id="deskripsi" name="deskripsi" required><?= htmlspecialchars(is_string($_POST['deskripsi'] ?? null) ? $_POST['deskripsi'] : '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="form-group">
            <label for="link_project">Link Tautan Project (GitHub / Website Live)</label>
            <input type="url" id="link_project" name="link_project" value="<?= htmlspecialchars(is_string($_POST['link_project'] ?? null) ? $_POST['link_project'] : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." required>
        </div>
        <div class="form-group">
            <label for="gambar">Unggah Tangkapan Layar Karya (JPG, PNG, WEBP; maks. 2MB)</label>
            <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp" required>
            <div class="preview-box" id="previewBox">
                <p>Pratinjau Gambar:</p>
                <img id="imagePreview" src="" alt="Pratinjau gambar karya">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Karya Digital</button>
    </form>
</main>
<script>
document.getElementById('gambar').addEventListener('change', function () {
    const previewBox = document.getElementById('previewBox');
    const imagePreview = document.getElementById('imagePreview');
    const file = this.files[0];

    if (!file) {
        imagePreview.removeAttribute('src');
        previewBox.style.display = 'none';
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        this.value = '';
        imagePreview.removeAttribute('src');
        previewBox.style.display = 'none';
        alert('Pilih gambar JPG, PNG, atau WEBP maksimal 2MB.');
        return;
    }

    imagePreview.src = URL.createObjectURL(file);
    previewBox.style.display = 'block';
});
</script>
</body>
</html>
