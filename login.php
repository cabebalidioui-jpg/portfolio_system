<?php

session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $hashed_password = hash("sha256", $password);

        $stmt = $conn->prepare(
            "SELECT id, username
             FROM admins
             WHERE username = ?
             AND password = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ss",
                $username,
                $hashed_password
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $admin = $result->fetch_assoc();

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] =
                    $admin["username"];

                header("Location: admin/dashboard.php");
                exit;

            } else {

                $error = "Invalid username or password.";

            }

            $stmt->close();

        } else {

            $error = "Unable to process your request.";
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

    <title>Admin Logins</title>

    <link rel="stylesheet" href="style.css">

</head>

<body class="login-page">

<div class="login-box">

    <h1>Admin Login</h1>

    <p>
        Login to manage your portfolio.
    </p>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            placeholder="Username"
            required
        >


        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >


        <button
            type="submit"
            class="btn"
        >
            Login
        </button>

    </form>


    <a href="index.php" class="back-link">
        ← Back to Portfolio
    </a>

</div>

</body>
</html>
