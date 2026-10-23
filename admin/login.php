<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (
    !empty($_SESSION['admin_logged_in']) &&
    $_SESSION['admin_logged_in'] === true
) {
    header(
        'Location: ' . BASE_URL . '/admin/index.php'
    );

    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username aur password required hain.';
    } else {
        $statement = $pdo->prepare(
            'SELECT
                id,
                username,
                password
             FROM admin_users
             WHERE username = :username
             LIMIT 1'
        );

        $statement->execute([
            'username' => $username,
        ]);

        $admin = $statement->fetch();

        if (
            $admin &&
            password_verify($password, $admin['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header(
                'Location: ' . BASE_URL . '/admin/index.php'
            );

            exit;
        }

        $error = 'Invalid username ya password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Login | <?= escape(ORGANIZATION_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/style.css"
    >

</head>

<body>

<header class="site-header">

    <div class="page-wrapper header-inner">

        <a
            class="brand"
            href="<?= escape(BASE_URL) ?>/index.php"
        >

            <div class="brand-logo">
                DS
            </div>

            <div class="brand-text">

                <strong>
                    <?= escape(ORGANIZATION_NAME) ?>
                </strong>

                <span>
                    Secure Admin Login
                </span>

            </div>

        </a>

        <nav class="navigation">

            <a href="<?= escape(BASE_URL) ?>/index.php">
                Generate
            </a>

            <a href="<?= escape(BASE_URL) ?>/verify.php">
                Verify
            </a>

        </nav>

    </div>

</header>

<main class="main-content">

    <div class="page-wrapper">

        <section class="hero">

            <span class="hero-badge">
                Authorized Access Only
            </span>

            <h1>
                Admin Login
            </h1>

            <p>
                Sign in to manage certificates.
            </p>

        </section>

        <section class="card">

            <div class="card-header">

                <h2>
                    Welcome Back
                </h2>

                <p>
                    Enter your Username and Password
                </p>

            </div>

            <?php if ($error !== ''): ?>

                <div class="alert alert-error">
                    <?= escape($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="username"
                    >
                        Username
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="username"
                        name="username"
                        value="<?= escape($username) ?>"
                        autocomplete="username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                </div>

                <button
                    class="button button-primary button-full"
                    type="submit"
                >
                    Login
                </button>

            </form>

        </section>

    </div>

</main>

<footer class="site-footer">

    &copy;
    <?= date('Y') ?>
    <?= escape(ORGANIZATION_NAME) ?>.

</footer>

</body>
</html>