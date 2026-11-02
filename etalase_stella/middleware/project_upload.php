<?php
function save_project_image(array $file): string
{
    if ($file === []) {
        throw new RuntimeException('Pilih gambar terlebih dahulu.');
    }

    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Data gambar yang dikirim tidak valid.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran gambar melebihi batas upload server.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar melebihi batas yang diizinkan.',
            UPLOAD_ERR_PARTIAL => 'Gambar hanya terunggah sebagian. Silakan coba kembali.',
            UPLOAD_ERR_NO_FILE => 'Pilih gambar terlebih dahulu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menyimpan gambar.',
            UPLOAD_ERR_EXTENSION => 'Upload gambar dihentikan oleh konfigurasi server.',
        ];
        throw new RuntimeException($messages[$file['error']] ?? 'Upload gambar gagal.');
    }

    if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Berkas gambar tidak valid.');
    }

    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar harus lebih dari 0 dan maksimal 2MB.');
    }

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);
    $imageInfo = getimagesize($file['tmp_name']);

    if (!is_string($mimeType) || !isset($mimeToExtension[$mimeType]) ||
        $imageInfo === false || $imageInfo['mime'] !== $mimeType) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP yang valid.');
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }

    if (!is_writable($uploadDirectory)) {
        throw new RuntimeException('Folder upload tidak dapat ditulisi oleh server.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $mimeToExtension[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gagal menyimpan gambar. Periksa izin folder uploads.');
    }

    return $fileName;
}

function delete_project_image(string $fileName): void
{
    if (basename($fileName) !== $fileName ||
        !preg_match('/\A[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp)\z/', $fileName)) {
        return;
    }

    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to delete project image: ' . $fileName);
    }
}
