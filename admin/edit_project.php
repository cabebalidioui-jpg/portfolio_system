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

if (!$id) {
    header("Location: dashboard.php");
    exit;
}


$stmt = $conn->prepare(
    "SELECT * FROM projects WHERE id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$project = $result->fetch_assoc();

$stmt->close();


if (!$project) {
    header("Location: dashboard.php");
    exit;
}


$error = "";
$old_image = $project["image"] ?? null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $technologies = trim($_POST["technologies"] ?? "");
    $project_link = normalize_link($_POST["project_link"] ?? "");


    if ($title === "" || $description === "") {

        $error = "Title and description are required.";

    } elseif (
        $project_link !== "" &&
        !filter_var($project_link, FILTER_VALIDATE_URL)
    ) {

        $error = "Please enter a valid project link.";

    } else {

        $uploaded = save_uploaded_image(
            $_FILES["image"] ?? [],
            "projects",
            $error
        );

        if ($error === "") {

            // Keep the old image unless a new one was uploaded
            // or the "remove" box was ticked
            $image = $old_image;

            if ($uploaded !== null) {
                $image = $uploaded;
            } elseif (!empty($_POST["remove_image"])) {
                $image = null;
            }

            $stmt = $conn->prepare(
                "UPDATE projects
                 SET title = ?,
                     description = ?,
                     technologies = ?,
                     project_link = ?,
                     image = ?
                 WHERE id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "sssssi",
                    $title,
                    $description,
                    $technologies,
                    $project_link,
                    $image,
                    $id
                );

                if ($stmt->execute()) {

                    if ($image !== $old_image) {
                        delete_uploaded_image($old_image, "projects");
                    }

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Unable to update the project.";
                    delete_uploaded_image($uploaded, "projects");
                }

                $stmt->close();

            } else {

                $error = "Database error. Please try again.";
                delete_uploaded_image($uploaded, "projects");
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Project</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header class="admin-navbar">

    <h2>
        Edit Project
    </h2>

    <a href="dashboard.php">
        Dashboard
    </a>

</header>


<main class="form-container">

    <h1>
        Edit Project
    </h1>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST" enctype="multipart/form-data">

        <label>
            Project Title
        </label>

        <input
            type="text"
            name="title"
            value="<?= htmlspecialchars(
                $project["title"]
            ) ?>"
            required
        >


        <label>
            Description
        </label>

        <textarea
            name="description"
            rows="6"
            required
        ><?= htmlspecialchars(
            $project["description"]
        ) ?></textarea>


        <label>
            Technologies
        </label>

        <input
            type="text"
            name="technologies"
            value="<?= htmlspecialchars(
                $project["technologies"]
            ) ?>"
        >


        <label>
            Project Link
        </label>

        <input
            type="text"
            name="project_link"
            placeholder="http://localhost/my-project or https://example.com"
            value="<?= htmlspecialchars(
                $project["project_link"] ?? ""
            ) ?>"
        >


        <label>
            Project Image / Screenshot
        </label>

        <?php if (!empty($old_image)): ?>

            <div class="current-image">
                <img
                    src="../uploads/projects/<?= htmlspecialchars($old_image) ?>"
                    alt="Current project image"
                >
            </div>

            <label class="checkbox-label">
                <input type="checkbox" name="remove_image" value="1">
                Remove current image
            </label>

        <?php endif; ?>

        <input
            type="file"
            name="image"
            accept="image/png, image/jpeg, image/webp, image/gif"
        >

        <small class="form-hint">
            Optional. Upload a new image to replace the current one.
            JPG, PNG, WEBP or GIF, max 5MB.
        </small>


        <button
            type="submit"
            class="btn"
        >
            Update Project
        </button>

    </form>

</main>

</body>
</html>