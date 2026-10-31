<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";
require_once "../upload.php";

$error = "";

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

        $image = save_uploaded_image(
            $_FILES["image"] ?? [],
            "projects",
            $error
        );

        if ($error === "") {

            $stmt = $conn->prepare(
                "INSERT INTO projects
                (title, description, technologies, project_link, image)
                VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "sssss",
                    $title,
                    $description,
                    $technologies,
                    $project_link,
                    $image
                );

                if ($stmt->execute()) {

                    header("Location: dashboard.php");
                    exit;

                } else {

                    $error = "Unable to add the project.";
                    delete_uploaded_image($image, "projects");
                }

                $stmt->close();

            } else {

                $error = "Database error. Please try again.";
                delete_uploaded_image($image, "projects");
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

    <title>Add Project</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header class="admin-navbar">

    <h2>
        Add Project
    </h2>

    <a href="dashboard.php">
        Dashboard
    </a>

</header>


<main class="form-container">

    <h1>
        Add New Project
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
            placeholder="Project title"
            required
        >


        <label>
            Description
        </label>

        <textarea
            name="description"
            rows="6"
            placeholder="Project description"
            required
        ></textarea>


        <label>
            Technologies
        </label>

        <input
            type="text"
            name="technologies"
            placeholder="HTML, CSS, PHP"
        >


        <label>
            Project Link
        </label>

        <input
            type="text"
            name="project_link"
            placeholder="http://localhost/my-project or https://example.com"
        >


        <label>
            Project Image / Screenshot
        </label>

        <input
            type="file"
            name="image"
            accept="image/png, image/jpeg, image/webp, image/gif"
        >

        <small class="form-hint">
            Optional. JPG, PNG, WEBP or GIF, max 5MB.
            If you skip it, a live preview of the project link is shown instead.
        </small>


        <button
            type="submit"
            class="btn"
        >
            Add Project
        </button>

    </form>

</main>

</body>
</html>