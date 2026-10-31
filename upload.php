<?php

/*
 * Saves an uploaded image into /uploads/<folder>/ and returns the new file name.
 * Returns null if no file was chosen, or if something went wrong
 * (in that case $error is filled in).
 */
function save_uploaded_image(array $file, string $folder, string &$error): ?string
{
    if (!isset($file["error"]) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "The image could not be uploaded. It may be too large.";
        return null;
    }

    if ($file["size"] > 5 * 1024 * 1024) {
        $error = "The image must be 5MB or smaller.";
        return null;
    }

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
        "image/gif"  => "gif",
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file["tmp_name"]);

    if (!isset($allowed[$mime])) {
        $error = "Only JPG, PNG, WEBP, or GIF images are allowed.";
        return null;
    }

    $dir = __DIR__ . "/uploads/" . $folder;

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        $error = "Unable to create the upload folder.";
        return null;
    }

    $name = bin2hex(random_bytes(8)) . "." . $allowed[$mime];

    if (!move_uploaded_file($file["tmp_name"], $dir . "/" . $name)) {
        $error = "Unable to save the uploaded image.";
        return null;
    }

    return $name;
}

function delete_uploaded_image(?string $name, string $folder): void
{
    if (!$name) {
        return;
    }

    $path = __DIR__ . "/uploads/" . $folder . "/" . basename($name);

    if (is_file($path)) {
        unlink($path);
    }
}

/* "localhost/myapp" -> "http://localhost/myapp" */
function normalize_link(string $link): string
{
    $link = trim($link);

    if ($link === "") {
        return "";
    }

    if (!preg_match('#^https?://#i', $link)) {
        $link = "http://" . $link;
    }

    return $link;
}
