<?php

if (
    !isset($certificate) ||
    !is_array($certificate) ||
    !isset($customTemplate) ||
    !is_array($customTemplate)
) {
    throw new RuntimeException(
        'Custom PDF certificate data is unavailable.'
    );
}

/*
|--------------------------------------------------------------------------
| Background Image
|--------------------------------------------------------------------------
*/

$templateImagePath = trim(
    (string) (
        $customTemplate[
            'template_image_path'
        ] ?? ''
    )
);

if ($templateImagePath === '') {
    throw new RuntimeException(
        'Custom template image path is missing.'
    );
}

$templateImagePath = str_replace(
    '\\',
    '/',
    $templateImagePath
);

$imageFile =
    dirname(__DIR__) .
    '/' .
    ltrim(
        $templateImagePath,
        '/'
    );

if (!is_file($imageFile)) {
    throw new RuntimeException(
        'Custom template background image was not found.'
    );
}

$imageInfo = getimagesize(
    $imageFile
);

if ($imageInfo === false) {
    throw new RuntimeException(
        'Unable to read custom template image.'
    );
}

$originalWidth =
    (float) $imageInfo[0];

$originalHeight =
    (float) $imageInfo[1];

if (
    $originalWidth <= 0 ||
    $originalHeight <= 0
) {
    throw new RuntimeException(
        'Invalid custom template image dimensions.'
    );
}

$imageContent = file_get_contents(
    $imageFile
);

if ($imageContent === false) {
    throw new RuntimeException(
        'Unable to load custom certificate image.'
    );
}

$mimeType =
    $imageInfo['mime']
    ?? 'image/png';

$imageData =
    'data:' .
    $mimeType .
    ';base64,' .
    base64_encode(
        $imageContent
    );

/*
|--------------------------------------------------------------------------
| PDF Dimensions
|--------------------------------------------------------------------------
*/

$pdfWidth = 841.89;

$pdfHeight =
    $pdfWidth *
    (
        $originalHeight /
        $originalWidth
    );

/*
|--------------------------------------------------------------------------
| Coordinate Scaling
|--------------------------------------------------------------------------
|
| Database coordinates were saved against
| the ORIGINAL uploaded image.
|--------------------------------------------------------------------------
*/

$scaleX =
    $pdfWidth /
    $originalWidth;

$scaleY =
    $pdfHeight /
    $originalHeight;

/*
|--------------------------------------------------------------------------
| Student Name Position
|--------------------------------------------------------------------------
*/

$studentNameX =
    (float) (
        $customTemplate[
            'student_name_x'
        ] ?? 0
    ) * $scaleX;

$studentNameY =
    (float) (
        $customTemplate[
            'student_name_y'
        ] ?? 0
    ) * $scaleY;

/*
|--------------------------------------------------------------------------
| Certificate ID Position
|--------------------------------------------------------------------------
*/

$certificateIdX =
    (float) (
        $customTemplate[
            'certificate_id_x'
        ] ?? 0
    ) * $scaleX;

$certificateIdY =
    (float) (
        $customTemplate[
            'certificate_id_y'
        ] ?? 0
    ) * $scaleY;

/*
|--------------------------------------------------------------------------
| Verification Position
|--------------------------------------------------------------------------
*/

$verificationLinkX =
    (float) (
        $customTemplate[
            'verification_link_x'
        ] ?? 0
    ) * $scaleX;

$verificationLinkY =
    (float) (
        $customTemplate[
            'verification_link_y'
        ] ?? 0
    ) * $scaleY;

/*
|--------------------------------------------------------------------------
| Font
|--------------------------------------------------------------------------
*/

$originalFontSize = max(
    10,
    (int) (
        $customTemplate[
            'font_size'
        ] ?? 24
    )
);

$studentFontSize =
    max(
        10,
        $originalFontSize *
        $scaleX
    );

$fontColor = trim(
    (string) (
        $customTemplate[
            'font_color'
        ] ?? '#000000'
    )
);

if (
    !preg_match(
        '/^#[0-9A-Fa-f]{6}$/',
        $fontColor
    )
) {
    $fontColor =
        '#000000';
}

/*
|--------------------------------------------------------------------------
| Values
|--------------------------------------------------------------------------
*/

$studentName =
    (string) $certificate[
        'student_name'
    ];

$certificateId =
    (string) $certificate[
        'certificate_id'
    ];
?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 0;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
}

body {
    font-family:
        DejaVu Sans,
        sans-serif;
}

.custom-pdf {
    position: relative;

    width: <?= $pdfWidth ?>pt;
    height: <?= $pdfHeight ?>pt;

    margin: 0;
    padding: 0;

    overflow: hidden;
}

.custom-pdf-background {
    position: absolute;

    top: 0;
    left: 0;

    width: <?= $pdfWidth ?>pt;
    height: <?= $pdfHeight ?>pt;

    margin: 0;
    padding: 0;
}

.custom-pdf-field {
    position: absolute;

    white-space: nowrap;

    line-height: 1;

    text-align: center;

    z-index: 10;
}

/*
|--------------------------------------------------------------------------
| Student Name
|--------------------------------------------------------------------------
*/

.student-name {
    left: <?= $studentNameX ?>pt;

    /*
     * Dompdf text baseline compensation.
     * Name ko saved position se thora upar rakhenge.
     */
    top: <?= $studentNameY - 20 ?>pt;

    width: 400pt;
    margin-left: -200pt;

    color: <?= escape($fontColor) ?>;

    font-family: "Times New Roman", "DejaVu Serif", serif;

    font-size: <?= $studentFontSize ?>pt;

    font-weight: bold;

    line-height: 1;

    text-align: center;
}

/*
|--------------------------------------------------------------------------
| Certificate ID
|--------------------------------------------------------------------------
*/

.certificate-id {
    left:
        <?= $certificateIdX ?>pt;

    top:
        <?= $certificateIdY ?>pt;

    width: 220pt;

    margin-left: -110pt;

    color: #17324d;

    font-size: 10pt;

    font-weight: bold;
}

/*
|--------------------------------------------------------------------------
| Verification
|--------------------------------------------------------------------------
*/

.verification-link {
    left:
        <?= $verificationLinkX ?>pt;

    top:
        <?= $verificationLinkY ?>pt;

    width: 200pt;

    margin-left: -100pt;

    color: #17324d;

    font-size: 9pt;
}

</style>

</head>

<body>

<div class="custom-pdf">

    <img
        class="custom-pdf-background"
        src="<?= $imageData ?>"
        alt=""
    >

    <div
        class="
            custom-pdf-field
            student-name
        "
    >
        <?= escape($studentName) ?>
    </div>

    <div
        class="
            custom-pdf-field
            certificate-id
        "
    >
        ID:
        <?= escape($certificateId) ?>
    </div>

    <div
        class="
            custom-pdf-field
            verification-link
        "
    >
        Verify Certificate
    </div>

</div>

</body>

</html>