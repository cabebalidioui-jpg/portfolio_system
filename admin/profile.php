<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../db.php";
require_once "../upload.php";

$admin_id = (int) $_SESSION["admin_id"];
$error = "";
$success = "";

// Load the current profile
$stmt = $conn->prepare(
    "SELECT display_name, headline, bio, profile_picture
     FROM admins WHERE id = ?"
);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current = $row["profile_picture"] ?? null;

// Same defaults the portfolio homepage uses until you save your own
$display_name = ($row["display_name"] ?? "") ?: "Carmilla";
$headline     = ($row["headline"] ?? "") ?: "Student";
$bio          = ($row["bio"] ?? "") ?: "Welcome to my portfolio. Here you can view my projects, skills, and contact information.";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "save";

    if ($action === "remove") {

        if ($current) {

            $stmt = $conn->prepare(
                "UPDATE admins SET profile_picture = NULL WHERE id = ?"
            );
            $stmt->bind_param("i", $admin_id);

            if ($stmt->execute()) {
                delete_uploaded_image($current, "profile");
                $current = null;
                $success = "Profile picture removed.";
            } else {
                $error = "Unable to remove the profile picture.";
            }

            $stmt->close();
        }

    } else {

        $display_name = trim($_POST["display_name"] ?? "");
        $headline     = trim($_POST["headline"] ?? "");
        $bio          = trim($_POST["bio"] ?? "");

        if ($display_name === "") {

            $error = "Please enter your name.";

        } elseif (
            mb_strlen($display_name) > 100 ||
            mb_strlen($headline) > 100
        ) {

            $error = "Name and title must be 100 characters or less.";

        } elseif (mb_strlen($bio) > 1000) {

            $error = "Your bio must be 1000 characters or less.";

        } else {

            $new = save_uploaded_image(
                $_FILES["profile_picture"] ?? [],
                "profile",
                $error
            );

            if ($error === "") {

                $picture = $new ?? $current;

                $stmt = $conn->prepare(
                    "UPDATE admins
                     SET display_name = ?,
                         headline = ?,
                         bio = ?,
                         profile_picture = ?
                     WHERE id = ?"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "ssssi",
                        $display_name,
                        $headline,
                        $bio,
                        $picture,
                        $admin_id
                    );

                    if ($stmt->execute()) {

                        if ($new !== null) {
                            delete_uploaded_image($current, "profile");
                            $current = $new;
                        }

                        $success = "Profile saved.";

                    } else {

                        delete_uploaded_image($new, "profile");
                        $error = "Unable to save your profile.";
                    }

                    $stmt->close();

                } else {

                    delete_uploaded_image($new, "profile");
                    $error = "Database error. Please try again.";
                }
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

    <title>Edit Profile</title>

    <link rel="stylesheet" href="../style.css">

</head>

<body>

<header class="admin-navbar">

    <h2>
        Edit Profile
    </h2>

    <div>

        <a href="../index.php">
            View Portfolio
        </a>

        <a href="dashboard.php">
            Dashboard
        </a>

    </div>

</header>


<main class="form-container">

    <h1>
        Your Profile
    </h1>


    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>


<form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="action" value="save">

        <label>
            Name
        </label>

        <input
            type="text"
            name="display_name"
            value="<?= htmlspecialchars($display_name) ?>"
            maxlength="100"
            required
        >


        <label>
            Title / Role
        </label>

        <input
            type="text"
            name="headline"
            value="<?= htmlspecialchars($headline) ?>"
            placeholder="Student, Web Developer, ..."
            maxlength="100"
        >


        <label>
            About Me
        </label>

        <textarea
            name="bio"
            rows="6"
            maxlength="1000"
            placeholder="Write a short introduction about yourself"
        ><?= htmlspecialchars($bio) ?></textarea>


        <label>
            Profile Picture
        </label>

        <input
            type="file"
            name="profile_picture"
            accept="image/png, image/jpeg, image/webp, image/gif"
        >

        <small class="form-hint">
            Optional. JPG, PNG, WEBP or GIF, max 5MB.
            Leave empty to keep your current picture.
            A square photo works best.
        </small>


        <button type="submit" class="btn">
            Save Profile
        </button>

    </form>


    <?php if ($current): ?>

        <form method="POST">

            <input type="hidden" name="action" value="remove">

            <button type="submit" class="btn btn-outline">
                Remove Picture
            </button>

        </form>

    <?php endif; ?>

</main>

</body>
</html>