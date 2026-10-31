<?php

$host = "127.0.0.1"; // Changed from "localhost"
$username = "root";
$password = "";
$database = "portfolio_system";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");

// Older databases may not have the profile columns yet. Add any that are
// missing so the profile page and the homepage never fail on them.
try {

    $needed = [
        "display_name"    => "VARCHAR(100) NULL",
        "headline"        => "VARCHAR(100) NULL",
        "bio"             => "TEXT NULL",
        "profile_picture" => "VARCHAR(255) NULL",
    ];

    $existing = [];
    $cols = $conn->query("SHOW COLUMNS FROM admins");

    if ($cols) {
        while ($c = $cols->fetch_assoc()) {
            $existing[$c["Field"]] = true;
        }

        foreach ($needed as $name => $definition) {
            if (!isset($existing[$name])) {
                $conn->query("ALTER TABLE admins ADD COLUMN `$name` $definition");
            }
        }
    }

} catch (Throwable $e) {
    // Ignore: the site still works with the defaults.
}

?>