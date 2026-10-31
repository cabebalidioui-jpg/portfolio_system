<?php

require_once "db.php";

$projects = [];

$sql = "SELECT * FROM projects ORDER BY created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// default picture: neow.png if it exists, otherwise a plain built-in placeholder
$profileSrc = is_file(__DIR__ . "/neow.png")
    ? "neow.png"
    : "data:image/svg+xml;utf8," . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
        . '<rect width="200" height="200" fill="#e0f2fe"/>'
        . '<circle cx="100" cy="78" r="38" fill="#7dd3fc"/>'
        . '<path d="M30 190c0-42 31-68 70-68s70 26 70 68z" fill="#7dd3fc"/>'
        . '</svg>'
    );
$profileClass = "";

// defaults, used until you save your own profile in the admin area
$profileName = "Carmilla";
$profileHeadline = "Student";
$profileBio = "Welcome to my portfolio. Here you can view my projects, skills, and contact information.";

// Prefer the admin row that actually has a picture, otherwise the first admin
try {
    $profileResult = $conn->query(
        "SELECT profile_picture, display_name, headline, bio
         FROM admins
         ORDER BY (profile_picture IS NULL OR profile_picture = '') ASC, id ASC
         LIMIT 1"
    );
} catch (Throwable $e) {
    $profileResult = false;
}

if ($profileResult && $profileRow = $profileResult->fetch_assoc()) {

    if (!empty($profileRow["display_name"])) {
        $profileName = $profileRow["display_name"];
    }

    if (!empty($profileRow["headline"])) {
        $profileHeadline = $profileRow["headline"];
    }

    if (!empty($profileRow["bio"])) {
        $profileBio = $profileRow["bio"];
    }

    $profileFile = basename($profileRow["profile_picture"] ?? "");

    $profilePath = __DIR__ . "/uploads/profile/" . $profileFile;

    if ($profileFile !== "" && is_file($profilePath)) {
        // ?v=... makes the browser reload the picture when it is replaced
        $profileSrc = "uploads/profile/" . rawurlencode($profileFile)
                    . "?v=" . filemtime($profilePath);
        $profileClass = "uploaded";
    }
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $user_message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $user_message === "") {

        $message = "Please complete all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO messages (name, email, message)
             VALUES (?, ?, ?)"
        );

        if ($stmt) {

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $user_message
            );

            if ($stmt->execute()) {
                $message = "Your message has been sent successfully.";
            } else {
                $message = "Unable to send your message. Please try again.";
            }

            $stmt->close();

        } else {
            $message = "Something went wrong. Please try again later.";
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

    <title>My Portfolio</title>

    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">

</head>

<body>

<header class="navbar">

    <div class="logo">
        Portfolio
    </div>

    <nav>
        <a href="#home">Home</a>
        <a href="#projects">Projects</a>
        <a href="#contact">Contact</a>
        <a href="login.php" class="admin-link">
            Admin
        </a>
    </nav>

</header>


<!-- PROFILE -->

<section class="hero" id="home">

    <div class="hero-content">

        <div class="profile-picture">

            <img
                src="<?= htmlspecialchars($profileSrc) ?>"
                class="<?= $profileClass ?>"
                style="width:260px;max-width:100%;height:auto;aspect-ratio:1/1;display:block;position:relative;z-index:2;border-radius:50%;border:5px solid #ffffff;box-shadow:0 10px 40px rgba(56,189,248,.35);object-fit:<?= $profileClass === 'uploaded' ? 'cover' : 'contain' ?>;background:#ffffff;"
                alt="Profile Picture"
            >

        </div>


        <div class="profile-info">

            <p class="small-title">
                WELCOME TO MY PORTFOLIO
            </p>

            <h1>
                Hi, I'm <span><?= htmlspecialchars($profileName) ?></span>
            </h1>

            <h2>
                <?= htmlspecialchars($profileHeadline) ?>
            </h2>

            <p>
                <?= nl2br(htmlspecialchars($profileBio)) ?>
            </p>

            <a href="#projects" class="btn">
                View Projects
            </a>

        </div>

    </div>

</section>


<!-- PROJECTS -->

<section class="projects" id="projects">

    <div class="section-heading">

        <p>MY WORK</p>

        <h2>My Projects</h2>

    </div>


    <div class="project-grid">

        <?php if (count($projects) > 0): ?>

            <?php foreach ($projects as $project): ?>

                <div class="project-card">

                    <div class="project-content">

                        <?php if (!empty($project["image"])): ?>

                            <div class="project-preview">
                                <img
                                    src="uploads/projects/<?= htmlspecialchars($project["image"]) ?>"
                                    alt="<?= htmlspecialchars($project["title"]) ?> preview"
                                >
                            </div>

                        <?php elseif (!empty($project["project_link"])): ?>

                            <div class="project-preview project-preview-live">
                                <iframe
                                    src="<?= htmlspecialchars($project["project_link"]) ?>"
                                    title="Live preview of <?= htmlspecialchars($project["title"]) ?>"
                                    loading="lazy"
                                    tabindex="-1"
                                    scrolling="no"
                                ></iframe>
                            </div>

                        <?php endif; ?>


                        <h3>
                            <?= htmlspecialchars($project["title"]) ?>
                        </h3>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $project["description"]
                                )
                            ) ?>
                        </p>

                        <span class="tag">
                            <?= htmlspecialchars(
                                $project["technologies"]
                            ) ?>
                        </span>

                        <?php if (!empty($project["project_link"])): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    $project["project_link"]
                                ) ?>"
                                target="_blank"
                                class="project-btn"
                            >
                                View Project
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No projects available yet.
            </p>

        <?php endif; ?>

    </div>

</section>


<!-- CONTACT -->

<section class="contact" id="contact">

    <div class="section-heading">

        <p>GET IN TOUCH</p>

        <h2>Contact Me</h2>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        class="contact-form"
    >

        <label>Name</label>

        <input
            type="text"
            name="name"
            placeholder="Enter your name"
            required
        >


        <label>Email</label>

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >


        <label>Message</label>

        <textarea
            name="message"
            rows="6"
            placeholder="Write your message..."
            required
        ></textarea>


        <button
            type="submit"
            class="btn"
        >
            Send Message
        </button>

    </form>

</section>


<footer>

    <h3>My Portfolio</h3>

    <p>
        Thank you for visiting my portfolio.
    </p>

</footer>

</body>
</html>