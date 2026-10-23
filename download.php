<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function getPdfSetting(
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


function localImageToDataUrl(
    string $relativePath
): string {
    if ($relativePath === '') {
        return '';
    }

    $relativePath = str_replace(
        '\\',
        '/',
        $relativePath
    );

    $fullPath =
        __DIR__ . '/' .
        ltrim($relativePath, '/');

    if (!is_file($fullPath)) {
        return '';
    }

    $mimeType = mime_content_type($fullPath);
    $content = file_get_contents($fullPath);

    if (
        !is_string($mimeType) ||
        $content === false
    ) {
        return '';
    }

    return sprintf(
        'data:%s;base64,%s',
        $mimeType,
        base64_encode($content)
    );
}


function remoteImageToDataUrl(
    string $url
): string {
    if ($url === '') {
        return '';
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0',
        ],
        'https' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0',
        ],
    ]);

    $content = @file_get_contents(
        $url,
        false,
        $context
    );

    if ($content === false) {
        return '';
    }

    return
        'data:image/png;base64,' .
        base64_encode($content);
}

/*
|--------------------------------------------------------------------------
| Certificate ID
|--------------------------------------------------------------------------
*/

$certificateId = trim(
    (string) ($_GET['id'] ?? '')
);

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

$certificate = $statement->fetch(
    PDO::FETCH_ASSOC
);

if (!$certificate) {
    http_response_code(404);
    exit('Certificate not found.');
}

/*
|--------------------------------------------------------------------------
| Template Selection
|--------------------------------------------------------------------------
*/

$templateName = trim(
    (string) (
        $certificate['template_name']
        ?? 'classic'
    )
);

$allowedTemplates = [
    'classic',
    'modern',
    'gold',
    'custom',
];

if (
    !in_array(
        $templateName,
        $allowedTemplates,
        true
    )
) {
    http_response_code(500);

    exit(
        'Unsupported certificate template: ' .
        escape($templateName)
    );
}

/*
|--------------------------------------------------------------------------
| Load Custom Template
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

    $customTemplate =
        $customTemplateStatement->fetch(
            PDO::FETCH_ASSOC
        );

    if (!$customTemplate) {
        http_response_code(500);

        exit(
            'Custom certificate template was not found.'
        );
    }

    if (
        isset($customTemplate['status']) &&
        $customTemplate['status'] !== 'active'
    ) {
        http_response_code(500);

        exit(
            'Custom certificate template is inactive.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Organization Settings
|--------------------------------------------------------------------------
*/

$organizationName = getPdfSetting(
    $pdo,
    'organization_name',
    ORGANIZATION_NAME
);

$organizationTagline = getPdfSetting(
    $pdo,
    'organization_tagline',
    ORGANIZATION_TAGLINE
);

$logoPath = getPdfSetting(
    $pdo,
    'logo_path'
);

$instructorSignaturePath = getPdfSetting(
    $pdo,
    'instructor_signature_path'
);

$directorSignaturePath = getPdfSetting(
    $pdo,
    'director_signature_path'
);

/*
|--------------------------------------------------------------------------
| Convert Uploaded Images
|--------------------------------------------------------------------------
*/

$logoData = localImageToDataUrl(
    $logoPath
);

$instructorSignatureData =
    localImageToDataUrl(
        $instructorSignaturePath
    );

$directorSignatureData =
    localImageToDataUrl(
        $directorSignaturePath
    );

/*
|--------------------------------------------------------------------------
| Verification Information
|--------------------------------------------------------------------------
*/

$verificationUrl = getVerificationUrl(
    (string) $certificate['certificate_id']
);

$qrCodeUrl =
    'https://api.qrserver.com/v1/create-qr-code/' .
    '?size=220x220' .
    '&data=' .
    urlencode($verificationUrl);

$qrCodeData = remoteImageToDataUrl(
    $qrCodeUrl
);

/*
|--------------------------------------------------------------------------
| Certificate Values
|--------------------------------------------------------------------------
*/

$formattedIssueDate = date(
    'd F Y',
    strtotime(
        (string) $certificate['issue_date']
    )
);

$isRevoked =
    ($certificate['status'] ?? '') === 'revoked';

/*
|--------------------------------------------------------------------------
| PDF Template File
|--------------------------------------------------------------------------
*/

$pdfTemplateFiles = [
    'classic' =>
        __DIR__ .
        '/pdf_templates/classic.php',

    'modern' =>
        __DIR__ .
        '/pdf_templates/modern.php',

    'gold' =>
        __DIR__ .
        '/pdf_templates/gold.php',

    'custom' =>
        __DIR__ .
        '/pdf_templates/custom.php',
];

$pdfTemplateFile =
    $pdfTemplateFiles[$templateName]
    ?? '';

if (
    $pdfTemplateFile === '' ||
    !is_file($pdfTemplateFile) ||
    filesize($pdfTemplateFile) === 0
) {
    http_response_code(500);

    exit(
        'PDF template file is missing or empty: ' .
        basename($pdfTemplateFile)
    );
}

/*
|--------------------------------------------------------------------------
| Render Template HTML
|--------------------------------------------------------------------------
*/

ob_start();

require $pdfTemplateFile;

$html = ob_get_clean();

if (
    $html === false ||
    trim($html) === ''
) {
    http_response_code(500);

    exit(
        'PDF HTML could not be generated.'
    );
}

/*
|--------------------------------------------------------------------------
| Dompdf Configuration
|--------------------------------------------------------------------------
*/

$options = new Options();

$options->set(
    'isRemoteEnabled',
    true
);

$options->set(
    'isHtml5ParserEnabled',
    true
);

$options->set(
    'defaultFont',
    'DejaVu Sans'
);

$options->set(
    'chroot',
    __DIR__
);

$dompdf = new Dompdf(
    $options
);

$dompdf->loadHtml(
    $html,
    'UTF-8'
);

/*
|--------------------------------------------------------------------------
| PDF Paper Size
|--------------------------------------------------------------------------
*/

if (
    $templateName === 'custom' &&
    is_array($customTemplate)
) {
    $templateImagePath = trim(
        (string) (
            $customTemplate[
                'template_image_path'
            ] ?? ''
        )
    );

    $templateImagePath = str_replace(
        '\\',
        '/',
        $templateImagePath
    );

    $imageFile =
        __DIR__ . '/' .
        ltrim(
            $templateImagePath,
            '/'
        );

    $imageInfo = is_file($imageFile)
        ? getimagesize($imageFile)
        : false;

    if ($imageInfo === false) {
        http_response_code(500);

        exit(
            'Custom template background image could not be found.'
        );
    }

    $imageWidth =
        (float) $imageInfo[0];

    $imageHeight =
        (float) $imageInfo[1];

    if (
        $imageWidth <= 0 ||
        $imageHeight <= 0
    ) {
        http_response_code(500);

        exit(
            'Custom template image dimensions are invalid.'
        );
    }

    /*
     * Keep width close to A4 landscape width
     * while preserving uploaded template ratio.
     */
    $paperWidth = 841.89;

    $paperHeight =
        $paperWidth *
        (
            $imageHeight /
            $imageWidth
        );

    $dompdf->setPaper([
        0,
        0,
        $paperWidth,
        $paperHeight,
    ]);

} else {
    $dompdf->setPaper(
        'A4',
        'landscape'
    );
}

/*
|--------------------------------------------------------------------------
| Render PDF
|--------------------------------------------------------------------------
*/

$dompdf->render();

/*
|--------------------------------------------------------------------------
| Download PDF
|--------------------------------------------------------------------------
*/

$fileName = preg_replace(
    '/[^A-Za-z0-9\-]/',
    '-',
    (string) $certificate[
        'certificate_id'
    ]
);

if (
    !is_string($fileName) ||
    $fileName === ''
) {
    $fileName = 'certificate';
}

$dompdf->stream(
    $fileName . '.pdf',
    [
        'Attachment' => true,
    ]
);

exit;