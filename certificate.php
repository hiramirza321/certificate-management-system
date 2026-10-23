<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| Settings Helper
|--------------------------------------------------------------------------
*/

function getCertificateSetting(
    PDO $pdo,
    string $key,
    string $default = ''
): string {
    $statement = $pdo->prepare(
        'SELECT setting_value
         FROM site_settings
         WHERE setting_key = :setting_key
         LIMIT 1'
    );

    $statement->execute([
        'setting_key' => $key,
    ]);

    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if (
        !$result ||
        !array_key_exists('setting_value', $result)
    ) {
        return $default;
    }

    return (string) $result['setting_value'];
}

/*
|--------------------------------------------------------------------------
| Organization Settings
|--------------------------------------------------------------------------
*/

$organizationName = getCertificateSetting(
    $pdo,
    'organization_name',
    ORGANIZATION_NAME
);

$organizationTagline = getCertificateSetting(
    $pdo,
    'organization_tagline',
    ORGANIZATION_TAGLINE
);

$logoPath = getCertificateSetting(
    $pdo,
    'logo_path'
);

$instructorSignaturePath = getCertificateSetting(
    $pdo,
    'instructor_signature_path'
);

$directorSignaturePath = getCertificateSetting(
    $pdo,
    'director_signature_path'
);

/*
|--------------------------------------------------------------------------
| Certificate ID
|--------------------------------------------------------------------------
*/

$certificateId = trim($_GET['id'] ?? '');

if ($certificateId === '') {
    http_response_code(400);
    exit('Certificate ID is required.');
}

/*
|--------------------------------------------------------------------------
| Certificate Record
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT
        certificate_id,
        student_name,
        field_name,
        template_name,
        custom_template_id,
        issue_date,
        status,
        created_at
     FROM certificates
     WHERE certificate_id = :certificate_id
     LIMIT 1'
);

$statement->execute([
    'certificate_id' => $certificateId,
]);

$certificate = $statement->fetch(PDO::FETCH_ASSOC);

if (!$certificate) {
    http_response_code(404);
    exit('Certificate not found.');
}

/*
|--------------------------------------------------------------------------
| Template Selection
|--------------------------------------------------------------------------
*/

$templateName = (string) (
    $certificate['template_name'] ?? 'classic'
);

$allowedTemplates = [
    'classic',
    'modern',
    'gold',
    'custom',
];

if (!in_array($templateName, $allowedTemplates, true)) {
    $templateName = 'classic';
}

/*
|--------------------------------------------------------------------------
| Custom Template Record
|--------------------------------------------------------------------------
*/

$customTemplate = null;

if ($templateName === 'custom') {
    $customTemplateId = (int) (
        $certificate['custom_template_id'] ?? 0
    );

    if ($customTemplateId < 1) {
        http_response_code(500);

        exit(
            'Custom template ID is missing for this certificate.'
        );
    }

    $customTemplateStatement = $pdo->prepare(
        'SELECT
            id,
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
         FROM certificate_templates
         WHERE id = :id
         LIMIT 1'
    );

    $customTemplateStatement->execute([
        'id' => $customTemplateId,
    ]);

    $customTemplate = $customTemplateStatement->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$customTemplate) {
        http_response_code(500);

        exit(
            'Custom certificate template record was not found.'
        );
    }

    if ($customTemplate['status'] !== 'active') {
        http_response_code(500);

        exit(
            'Custom certificate template is currently inactive.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Template CSS
|--------------------------------------------------------------------------
*/

$templateCssFiles = [
    'classic' => 'classic.css',
    'modern' => 'modern.css',
    'gold' => 'gold.css',
];

$templateCss = $templateCssFiles[$templateName] ?? null;

/*
|--------------------------------------------------------------------------
| Verification Information
|--------------------------------------------------------------------------
*/

$verificationUrl = getVerificationUrl(
    $certificate['certificate_id']
);

$qrCodeUrl =
    'https://api.qrserver.com/v1/create-qr-code/' .
    '?size=180x180' .
    '&data=' . urlencode($verificationUrl);

$formattedIssueDate = date(
    'd F Y',
    strtotime((string) $certificate['issue_date'])
);

$isRevoked =
    $certificate['status'] === 'revoked';

/*
|--------------------------------------------------------------------------
| Template File
|--------------------------------------------------------------------------
*/

$templateFiles = [
    'classic' =>
        __DIR__ . '/templates/classic.php',

    'modern' =>
        __DIR__ . '/templates/modern.php',

    'gold' =>
        __DIR__ . '/templates/gold.php',

    'custom' =>
        __DIR__ . '/templates/custom.php',
];

$templateFile =
    $templateFiles[$templateName]
    ?? $templateFiles['classic'];

if (!is_file($templateFile)) {
    http_response_code(500);

    exit(
        'Certificate template file is missing.'
    );
}

/*
|--------------------------------------------------------------------------
| Template Variable Safety
|--------------------------------------------------------------------------
*/

$logoPath = $logoPath ?? '';

$instructorSignaturePath =
    $instructorSignaturePath ?? '';

$directorSignaturePath =
    $directorSignaturePath ?? '';
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
        Certificate |
        <?= escape($certificate['student_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= escape(BASE_URL) ?>/assets/style.css?v=12"
    >

    <?php if ($templateCss !== null): ?>

        <link
            rel="stylesheet"
            href="<?= escape(BASE_URL) ?>/assets/<?= escape($templateCss) ?>?v=12"
        >

    <?php endif; ?>

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
                    <?= escape($organizationName) ?>
                </strong>

                <span>
                    <?= escape($organizationTagline) ?>
                </span>

            </div>

        </a>

        <nav class="navigation">

            <a href="<?= escape(BASE_URL) ?>/index.php">
                Generate
            </a>

            <a
                href="<?= escape(BASE_URL) ?>/verify.php?id=<?= urlencode(
                    $certificate['certificate_id']
                ) ?>"
            >
                Verify
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/index.php">
                Admin
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/settings.php">
                Settings
            </a>

        </nav>

    </div>

</header>

<div class="toolbar">

    <a
        class="button button-primary"
        href="<?= escape(BASE_URL) ?>/download.php?id=<?= urlencode(
            $certificate['certificate_id']
        ) ?>"
    >
        Download PDF
    </a>

    <button
        class="button button-primary"
        type="button"
        onclick="window.print()"
    >
        Print / Save as PDF
    </button>

    <a
        class="button button-secondary"
        href="<?= escape(BASE_URL) ?>/index.php"
    >
        Create Another
    </a>

    <a
        class="button button-secondary"
        href="<?= escape($verificationUrl) ?>"
        target="_blank"
        rel="noopener"
    >
        Verify Certificate
    </a>

</div>

<?php if ($isRevoked): ?>

    <div
        class="alert alert-error"
        style="
            width: min(1120px, calc(100% - 30px));
            margin: 0 auto 20px;
            text-align: center;
        "
    >
        Warning: This certificate has been revoked.
    </div>

<?php endif; ?>

<?php
require $templateFile;
?>

<footer class="site-footer">

    &copy;
    <?= date('Y') ?>
    <?= escape($organizationName) ?>.
    All rights reserved.

</footer>

<script>
    document.addEventListener(
        'keydown',
        function (event) {
            if (
                event.ctrlKey &&
                event.key.toLowerCase() === 'p'
            ) {
                event.preventDefault();
                window.print();
            }
        }
    );
</script>

</body>
</html>