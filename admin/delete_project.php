<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";
require_once "../upload.php";

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if ($id) {

    // Get the image name first so the file can be removed too
    $image = null;

    $stmt = $conn->prepare(
        "SELECT image FROM projects WHERE id = ?"
    );

    if ($stmt) {

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        $image = $row["image"] ?? null;

        $stmt->close();
    }

    $stmt = $conn->prepare(
        "DELETE FROM projects WHERE id = ?"
    );

    if ($stmt) {

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            delete_uploaded_image($image, "projects");
        }

        $stmt->close();
    }
}

header("Location: dashboard.php");

exit;

?>