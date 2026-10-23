<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$errors = [];
$successMessage = '';

$templateName = '';
$studentNameX = 660;
$studentNameY = 455;
$certificateIdX = 90;
$certificateIdY = 820;
$verificationLinkX = 90;
$verificationLinkY = 850;
$fontSize = 34;
$fontColor = '#172330';

/*
|--------------------------------------------------------------------------
| Upload Directory
|--------------------------------------------------------------------------
*/

$uploadDirectory =
    dirname(__DIR__) .
    '/uploads/certificate_templates';

if (
    !is_dir($uploadDirectory) &&
    !mkdir($uploadDirectory, 0755, true) &&
    !is_dir($uploadDirectory)
) {
    http_response_code(500);
    exit('Template upload directory could not be created.');
}

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateName = trim(
        $_POST['template_name'] ?? ''
    );

    $studentNameX = filter_var(
        $_POST['student_name_x'] ?? 660,
        FILTER_VALIDATE_INT
    );

    $studentNameY = filter_var(
        $_POST['student_name_y'] ?? 455,
        FILTER_VALIDATE_INT
    );

    $certificateIdX = filter_var(
        $_POST['certificate_id_x'] ?? 90,
        FILTER_VALIDATE_INT
    );

    $certificateIdY = filter_var(
        $_POST['certificate_id_y'] ?? 820,
        FILTER_VALIDATE_INT
    );

    $verificationLinkX = filter_var(
        $_POST['verification_link_x'] ?? 90,
        FILTER_VALIDATE_INT
    );

    $verificationLinkY = filter_var(
        $_POST['verification_link_y'] ?? 850,
        FILTER_VALIDATE_INT
    );

    $fontSize = filter_var(
        $_POST['font_size'] ?? 34,
        FILTER_VALIDATE_INT
    );

    $fontColor = trim(
        $_POST['font_color'] ?? '#172330'
    );

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($templateName === '') {
        $errors[] = 'Template name is required.';
    } elseif (mb_strlen($templateName) > 150) {
        $errors[] =
            'Template name must not exceed 150 characters.';
    }

    $positionValues = [
        'Student name X' =>
            $studentNameX,

        'Student name Y' =>
            $studentNameY,

        'Certificate ID X' =>
            $certificateIdX,

        'Certificate ID Y' =>
            $certificateIdY,

        'Verification link X' =>
            $verificationLinkX,

        'Verification link Y' =>
            $verificationLinkY,
    ];

    foreach ($positionValues as $label => $value) {
        if (
            $value === false ||
            $value < 0 ||
            $value > 5000
        ) {
            $errors[] =
                $label . ' position is invalid.';
        }
    }

    if (
        $fontSize === false ||
        $fontSize < 10 ||
        $fontSize > 150
    ) {
        $errors[] =
            'Font size must be between 10 and 150.';
    }

    if (
        !preg_match(
            '/^#[0-9A-Fa-f]{6}$/',
            $fontColor
        )
    ) {
        $errors[] =
            'Please select a valid font colour.';
    }

    /*
    |--------------------------------------------------------------------------
    | Uploaded File Validation
    |--------------------------------------------------------------------------
    */

    $uploadedFile =
        $_FILES['template_image'] ?? null;

    if (
        !$uploadedFile ||
        !isset($uploadedFile['error'])
    ) {
        $errors[] =
            'Please select a certificate template image.';
    } elseif (
        $uploadedFile['error'] !== UPLOAD_ERR_OK
    ) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE =>
                'The uploaded image exceeds the server limit.',

            UPLOAD_ERR_FORM_SIZE =>
                'The uploaded image is too large.',

            UPLOAD_ERR_PARTIAL =>
                'The image was only partially uploaded.',

            UPLOAD_ERR_NO_FILE =>
                'Please select a certificate template image.',
        ];

        $errors[] =
            $uploadErrors[$uploadedFile['error']]
            ?? 'Template image upload failed.';
    }

    if (
        empty($errors) &&
        is_array($uploadedFile)
    ) {
        $maximumFileSize =
            8 * 1024 * 1024;

        if (
            (int) $uploadedFile['size'] >
            $maximumFileSize
        ) {
            $errors[] =
                'Template image must not exceed 8 MB.';
        }

        $temporaryPath =
            $uploadedFile['tmp_name'];

        if (
            !is_uploaded_file($temporaryPath)
        ) {
            $errors[] =
                'Invalid uploaded file.';
        }

        $imageInformation =
            @getimagesize($temporaryPath);

        if ($imageInformation === false) {
            $errors[] =
                'Uploaded file is not a valid image.';
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $fileInformation =
            new finfo(FILEINFO_MIME_TYPE);

        $mimeType =
            $fileInformation->file($temporaryPath);

        if (
            !is_string($mimeType) ||
            !isset($allowedMimeTypes[$mimeType])
        ) {
            $errors[] =
                'Only JPG and PNG images are allowed.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save File and Database Record
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        is_array($uploadedFile)
    ) {
        $extension =
            $allowedMimeTypes[$mimeType];

        $fileName = sprintf(
            '%s-%s.%s',
            date('YmdHis'),
            bin2hex(random_bytes(8)),
            $extension
        );

        $destinationPath =
            $uploadDirectory .
            DIRECTORY_SEPARATOR .
            $fileName;

        $relativePath =
            'uploads/certificate_templates/' .
            $fileName;

        try {
            if (
                !move_uploaded_file(
                    $uploadedFile['tmp_name'],
                    $destinationPath
                )
            ) {
                throw new RuntimeException(
                    'Uploaded image could not be saved.'
                );
            }

            $statement = $pdo->prepare(
                'INSERT INTO certificate_templates
                (
                    template_name,
                    template_image_path,
                    student_name_x,
                    student_name_y,
                    certificate_id_x,
                    certificate_id_y,
                    verification_link_x,
                    verification_link_y,
                    font_size,
                    font_color,
                    status
                )
                VALUES
                (
                    :template_name,
                    :template_image_path,
                    :student_name_x,
                    :student_name_y,
                    :certificate_id_x,
                    :certificate_id_y,
                    :verification_link_x,
                    :verification_link_y,
                    :font_size,
                    :font_color,
                    :status
                )'
            );

            $statement->execute([
                'template_name' =>
                    $templateName,

                'template_image_path' =>
                    $relativePath,

                'student_name_x' =>
                    $studentNameX,

                'student_name_y' =>
                    $studentNameY,

                'certificate_id_x' =>
                    $certificateIdX,

                'certificate_id_y' =>
                    $certificateIdY,

                'verification_link_x' =>
                    $verificationLinkX,

                'verification_link_y' =>
                    $verificationLinkY,

                'font_size' =>
                    $fontSize,

                'font_color' =>
                    $fontColor,

                'status' =>
                    'active',
            ]);

            $newTemplateId =
                (int) $pdo->lastInsertId();

            header(
                'Location: template-upload.php' .
                '?success=1&id=' .
                $newTemplateId
            );

            exit;
        } catch (Throwable $exception) {
            if (is_file($destinationPath)) {
                unlink($destinationPath);
            }

            $errors[] =
                'Template could not be saved. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

if (($_GET['success'] ?? '') === '1') {
    $successMessage =
        'Certificate template uploaded successfully.';
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
        Upload Certificate Template |
        <?= escape(ORGANIZATION_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= escape(BASE_URL) ?>/assets/style.css?v=10"
    >

    <style>
        .template-upload-container {
            width: min(900px, calc(100% - 32px));
            margin: 45px auto;
        }

        .template-upload-card {
            padding: 32px;
            border-radius: 18px;
            background: #ffffff;
            box-shadow:
                0 18px 45px rgba(15, 35, 55, 0.12);
        }

        .template-upload-heading {
            margin-bottom: 28px;
        }

        .template-upload-heading h1 {
            margin: 0 0 8px;
            color: #17324d;
        }

        .template-upload-heading p {
            margin: 0;
            color: #667085;
            line-height: 1.6;
        }

        .position-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .appearance-grid {
            display: grid;
            grid-template-columns: 1fr 180px;
            gap: 18px;
        }

        .image-preview {
            display: none;
            margin: 25px 0;
            padding: 16px;
            border: 1px solid #d7dde3;
            border-radius: 14px;
            background: #f8fafc;
            text-align: center;
        }

        .image-preview img {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
        }

        .page-actions {
            margin-bottom: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 650px) {
            .position-grid,
            .appearance-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<header class="site-header">

    <div class="page-wrapper header-inner">

        <a
            class="brand"
            href="<?= escape(BASE_URL) ?>/admin/index.php"
        >

            <div class="brand-logo">
                DS
            </div>

            <div class="brand-text">

                <strong>
                    <?= escape(ORGANIZATION_NAME) ?>
                </strong>

                <span>
                    Template Management
                </span>

            </div>

        </a>

        <nav class="navigation">

            <a href="<?= escape(BASE_URL) ?>/admin/index.php">
                Dashboard
            </a>

            <a href="<?= escape(BASE_URL) ?>/index.php">
                Generate
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

<main class="template-upload-container">

    <div class="page-actions">

        <a
            class="button button-secondary"
            href="<?= escape(BASE_URL) ?>/admin/index.php"
        >
            Back to Dashboard
        </a>

    </div>

    <section class="template-upload-card">

        <div class="template-upload-heading">

            <h1>
                Upload Certificate Template
            </h1>

            <p>
                Upload a landscape JPG or PNG certificate
                background. Student name, unique certificate ID
                and verification link will be placed over it.
            </p>

        </div>

        <?php if ($successMessage !== ''): ?>

            <div class="alert alert-success">
                <?= escape($successMessage) ?>
            </div>

        <?php endif; ?>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <?php foreach ($errors as $error): ?>

                    <div>
                        <?= escape($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            action=""
        >

            <div class="form-group">

                <label
                    class="form-label"
                    for="template_name"
                >
                    Template Name
                </label>

                <input
                    class="form-control"
                    type="text"
                    id="template_name"
                    name="template_name"
                    maxlength="150"
                    placeholder="Example: IBRAS Participation 2025"
                    value="<?= escape($templateName) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label
                    class="form-label"
                    for="template_image"
                >
                    Certificate Template Image
                </label>

                <input
                    class="form-control"
                    type="file"
                    id="template_image"
                    name="template_image"
                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                    required
                >

                <small class="form-help">
                    Maximum size: 8 MB. Recommended landscape
                    resolution: 1323 × 925 pixels or higher.
                </small>

            </div>

            <div
                class="image-preview"
                id="image_preview"
            >

                <img
                    id="preview_image"
                    src=""
                    alt="Certificate template preview"
                >

            </div>

            <h2>
                Text Positions
            </h2>

            <p class="form-help">
                These values use the original uploaded image's
                pixel coordinates. They can be adjusted later in
                the visual position editor.
            </p>

            <div class="position-grid">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="student_name_x"
                    >
                        Student Name X
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="student_name_x"
                        name="student_name_x"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $studentNameX
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="student_name_y"
                    >
                        Student Name Y
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="student_name_y"
                        name="student_name_y"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $studentNameY
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="certificate_id_x"
                    >
                        Certificate ID X
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="certificate_id_x"
                        name="certificate_id_x"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $certificateIdX
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="certificate_id_y"
                    >
                        Certificate ID Y
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="certificate_id_y"
                        name="certificate_id_y"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $certificateIdY
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="verification_link_x"
                    >
                        Verification Link X
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="verification_link_x"
                        name="verification_link_x"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $verificationLinkX
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="verification_link_y"
                    >
                        Verification Link Y
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="verification_link_y"
                        name="verification_link_y"
                        min="0"
                        max="5000"
                        value="<?= escape(
                            (string) $verificationLinkY
                        ) ?>"
                        required
                    >

                </div>

            </div>

            <h2>
                Text Appearance
            </h2>

            <div class="appearance-grid">

                <div class="form-group">

                    <label
                        class="form-label"
                        for="font_size"
                    >
                        Student Name Font Size
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        id="font_size"
                        name="font_size"
                        min="10"
                        max="150"
                        value="<?= escape(
                            (string) $fontSize
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="font_color"
                    >
                        Font Colour
                    </label>

                    <input
                        class="form-control"
                        type="color"
                        id="font_color"
                        name="font_color"
                        value="<?= escape($fontColor) ?>"
                        required
                    >

                </div>

            </div>

            <button
                class="button button-primary button-full"
                type="submit"
            >
                Upload Certificate Template
            </button>

        </form>

    </section>

</main>

<footer class="site-footer">

    &copy;
    <?= date('Y') ?>
    <?= escape(ORGANIZATION_NAME) ?>.
    Template Management.

</footer>

<script>
    const templateInput =
        document.getElementById('template_image');

    const previewContainer =
        document.getElementById('image_preview');

    const previewImage =
        document.getElementById('preview_image');

    templateInput.addEventListener(
        'change',
        function () {
            const file = this.files[0];

            if (!file) {
                previewContainer.style.display = 'none';
                previewImage.src = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                previewContainer.style.display = 'none';
                previewImage.src = '';
                return;
            }

            const reader = new FileReader();

            reader.addEventListener(
                'load',
                function () {
                    previewImage.src = reader.result;
                    previewContainer.style.display = 'block';
                }
            );

            reader.readAsDataURL(file);
        }
    );
</script>

</body>
</html>