<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$message = '';
$error = '';

function getSetting(PDO $pdo, string $key, string $default = ''): string
{
    $statement = $pdo->prepare(
        'SELECT setting_value
         FROM site_settings
         WHERE setting_key = :setting_key
         LIMIT 1'
    );

    $statement->execute([
        'setting_key' => $key,
    ]);

    $result = $statement->fetch();

    return $result['setting_value'] ?? $default;
}

function saveSetting(PDO $pdo, string $key, string $value): void
{
    $statement = $pdo->prepare(
        'INSERT INTO site_settings
        (
            setting_key,
            setting_value
        )
        VALUES
        (
            :setting_key,
            :setting_value
        )
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)'
    );

    $statement->execute([
        'setting_key' => $key,
        'setting_value' => $value,
    ]);
}

function uploadImage(
    array $file,
    string $targetDirectory,
    string $relativeDirectory
): string {
    if (
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException(
            'Image size must not exceed 2 MB.'
        );
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException(
            'Only JPG, PNG and WEBP images are allowed.'
        );
    }

    if (!is_dir($targetDirectory)) {
        if (!mkdir($targetDirectory, 0755, true)) {
            throw new RuntimeException(
                'Upload directory could not be created.'
            );
        }
    }

    $extension = $allowedMimeTypes[$mimeType];

    $fileName =
        date('YmdHis') .
        '-' .
        bin2hex(random_bytes(5)) .
        '.' .
        $extension;

    $destination =
        rtrim($targetDirectory, DIRECTORY_SEPARATOR) .
        DIRECTORY_SEPARATOR .
        $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Uploaded image could not be saved.'
        );
    }

    return
        trim($relativeDirectory, '/') .
        '/' .
        $fileName;
}

$organizationName = getSetting(
    $pdo,
    'organization_name',
    ORGANIZATION_NAME
);

$organizationTagline = getSetting(
    $pdo,
    'organization_tagline',
    ORGANIZATION_TAGLINE
);

$logoPath = getSetting(
    $pdo,
    'logo_path'
);

$instructorSignaturePath = getSetting(
    $pdo,
    'instructor_signature_path'
);

$directorSignaturePath = getSetting(
    $pdo,
    'director_signature_path'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $organizationName = trim(
        $_POST['organization_name'] ?? ''
    );

    $organizationTagline = trim(
        $_POST['organization_tagline'] ?? ''
    );

    if ($organizationName === '') {
        $error = 'Organization name is required.';
    } elseif (mb_strlen($organizationName) > 150) {
        $error = 'Organization name is too long.';
    } elseif (mb_strlen($organizationTagline) > 200) {
        $error = 'Organization tagline is too long.';
    }

    if ($error === '') {
        try {
            $newLogoPath = uploadImage(
                $_FILES['logo'] ?? [],
                __DIR__ . '/../uploads/logos',
                'uploads/logos'
            );

            $newInstructorSignaturePath = uploadImage(
                $_FILES['instructor_signature'] ?? [],
                __DIR__ . '/../uploads/signatures',
                'uploads/signatures'
            );

            $newDirectorSignaturePath = uploadImage(
                $_FILES['director_signature'] ?? [],
                __DIR__ . '/../uploads/signatures',
                'uploads/signatures'
            );

            if ($newLogoPath !== '') {
                $logoPath = $newLogoPath;
            }

            if ($newInstructorSignaturePath !== '') {
                $instructorSignaturePath =
                    $newInstructorSignaturePath;
            }

            if ($newDirectorSignaturePath !== '') {
                $directorSignaturePath =
                    $newDirectorSignaturePath;
            }

            saveSetting(
                $pdo,
                'organization_name',
                $organizationName
            );

            saveSetting(
                $pdo,
                'organization_tagline',
                $organizationTagline
            );

            saveSetting(
                $pdo,
                'logo_path',
                $logoPath
            );

            saveSetting(
                $pdo,
                'instructor_signature_path',
                $instructorSignaturePath
            );

            saveSetting(
                $pdo,
                'director_signature_path',
                $directorSignaturePath
            );

            $message = 'Settings saved successfully.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
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
        Settings | <?= escape(ORGANIZATION_NAME) ?>
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
                    Branding Settings
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

            <a href="<?= escape(BASE_URL) ?>/admin/index.php">
                Admin
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/settings.php">
                Settings
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>

<main class="main-content">

    <div class="page-wrapper">

        <section class="hero">

            <span class="hero-badge">
                Branding Configuration
            </span>

            <h1>
                Certificate Settings
            </h1>

            <p>
                Organization name, tagline, logo aur signatures
                yahan se update karein.
            </p>

        </section>

        <section class="card">

            <?php if ($message !== ''): ?>

                <div class="alert alert-success">
                    <?= escape($message) ?>
                </div>

            <?php endif; ?>

            <?php if ($error !== ''): ?>

                <div class="alert alert-error">
                    <?= escape($error) ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-group">

                    <label
                        class="form-label"
                        for="organization_name"
                    >
                        Organization Name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="organization_name"
                        name="organization_name"
                        maxlength="150"
                        value="<?= escape($organizationName) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="organization_tagline"
                    >
                        Organization Tagline
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="organization_tagline"
                        name="organization_tagline"
                        maxlength="200"
                        value="<?= escape($organizationTagline) ?>"
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="logo"
                    >
                        Institute Logo
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        id="logo"
                        name="logo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small class="form-help">
                        JPG, PNG ya WEBP. Maximum size 2 MB.
                    </small>

                    <?php if ($logoPath !== ''): ?>

                        <div style="margin-top: 12px;">

                            <img
                                src="<?= escape(BASE_URL . '/' . $logoPath) ?>"
                                alt="Current logo"
                                style="
                                    width: 100px;
                                    height: 100px;
                                    object-fit: contain;
                                    border: 1px solid #ddd;
                                    border-radius: 10px;
                                    background: #fff;
                                    padding: 8px;
                                "
                            >

                        </div>

                    <?php endif; ?>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="instructor_signature"
                    >
                        Instructor Signature
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        id="instructor_signature"
                        name="instructor_signature"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <?php if ($instructorSignaturePath !== ''): ?>

                        <div style="margin-top: 12px;">

                            <img
                                src="<?= escape(
                                    BASE_URL .
                                    '/' .
                                    $instructorSignaturePath
                                ) ?>"
                                alt="Instructor signature"
                                style="
                                    width: 180px;
                                    height: 70px;
                                    object-fit: contain;
                                    border: 1px solid #ddd;
                                    background: #fff;
                                    padding: 8px;
                                "
                            >

                        </div>

                    <?php endif; ?>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="director_signature"
                    >
                        Director Signature
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        id="director_signature"
                        name="director_signature"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <?php if ($directorSignaturePath !== ''): ?>

                        <div style="margin-top: 12px;">

                            <img
                                src="<?= escape(
                                    BASE_URL .
                                    '/' .
                                    $directorSignaturePath
                                ) ?>"
                                alt="Director signature"
                                style="
                                    width: 180px;
                                    height: 70px;
                                    object-fit: contain;
                                    border: 1px solid #ddd;
                                    background: #fff;
                                    padding: 8px;
                                "
                            >

                        </div>

                    <?php endif; ?>

                </div>

                <button
                    class="button button-primary button-full"
                    type="submit"
                >
                    Save Settings
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