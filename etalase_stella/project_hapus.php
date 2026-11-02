<?php
require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';
require_once __DIR__ . '/middleware/project_upload.php';

check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

verify_csrf_token();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('ID karya tidak valid.');
}

$stmt = $pdo->prepare('SELECT user_id, gambar FROM projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();
if (!$project) {
    header('Location: index.php?status=karya-tidak-ditemukan');
    exit();
}

if ($_SESSION['role'] === 'siswa' && (int) $project['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    exit('Akses ditolak: Anda tidak dapat menghapus karya milik siswa lain.');
}

$delete = $pdo->prepare('DELETE FROM projects WHERE id = :id');
$delete->execute([':id' => $id]);
delete_project_image($project['gambar']);
header('Location: index.php');
exit();
?>
