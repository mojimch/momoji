<?php

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_rupiah(float|int|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function asset_url(string $path): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validate(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function redirect(string $path): never
{
    $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
        ? $path
        : rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    header('Location: ' . $target);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function with_old(array $values): void
{
    $_SESSION['old'] = $values;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'item';
}

function safe_upload(array $file, string $directory, array $allowed = ['image/jpeg', 'image/png', 'image/webp']): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file gagal.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file maksimal 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Tipe file tidak diizinkan.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File bukan gambar yang valid.');
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $extension = $extensionMap[$mime];
    $targetDirectory = UPLOAD_PATH . '/' . trim($directory, '/');
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = $targetDirectory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('File tidak dapat disimpan.');
    }

    return '/storage/uploads/' . trim($directory, '/') . '/' . $filename;
}
