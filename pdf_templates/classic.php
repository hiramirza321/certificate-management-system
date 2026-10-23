<?php

if (!isset($certificate)) {
    http_response_code(403);
    exit('This PDF template cannot be opened directly.');
}

/*
|--------------------------------------------------------------------------
| Safe Values
|--------------------------------------------------------------------------
*/

$safeOrganizationName = escape($organizationName);
$safeOrganizationTagline = escape($organizationTagline);
$safeStudentName = escape($certificate['student_name']);
$safeFieldName = escape($certificate['field_name']);
$safeCertificateId = escape($certificate['certificate_id']);
$safeIssueDate = escape($formattedIssueDate);

/*
|--------------------------------------------------------------------------
| Dynamic Image HTML
|--------------------------------------------------------------------------
*/

if ($logoData !== '') {
    $logoHtml = '
        <div class="classic-logo">
            <img
                src="' . escape($logoData) . '"
                alt="Organization Logo"
            >
        </div>
    ';
} else {
    $logoHtml = '
        <div class="classic-logo classic-logo-text">
            JUW
        </div>
    ';
}

$instructorSignatureHtml = '';

if ($instructorSignatureData !== '') {
    $instructorSignatureHtml = '
        <img
            class="classic-signature-image"
            src="' . escape($instructorSignatureData) . '"
            alt="Instructor Signature"
        >
    ';
}

$directorSignatureHtml = '';

if ($directorSignatureData !== '') {
    $directorSignatureHtml = '
        <img
            class="classic-signature-image"
            src="' . escape($directorSignatureData) . '"
            alt="Director Signature"
        >
    ';
}

if ($qrCodeData !== '') {
    $qrCodeHtml = '
        <img
            class="classic-qr-image"
            src="' . escape($qrCodeData) . '"
            alt="Verification QR Code"
        >
    ';
} else {
    $qrCodeHtml = '
        <div class="classic-qr-fallback">
            QR
        </div>
    ';
}

$revokedHtml = '';

if ($isRevoked) {
    $revokedHtml = '
        <div class="classic-revoked">
            REVOKED
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<style>
    @page {
        size: A4 landscape;
        margin: 0;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        width: 841.89pt;
        height: 595.28pt;
        margin: 0;
        padding: 0;
        overflow: hidden;
        color: #172330;
        background: #ffffff;
        font-family: DejaVu Sans, sans-serif;
    }

    .classic-page {
        position: relative;
        width: 841.89pt;
        height: 595.28pt;
        overflow: hidden;
        background: #fffdf5;
    }

    .classic-outer-border {
        position: absolute;
        top: 14pt;
        right: 14pt;
        bottom: 14pt;
        left: 14pt;
        border: 8pt solid #c49a3a;
    }

    .classic-inner-border {
        position: absolute;
        top: 31pt;
        right: 31pt;
        bottom: 31pt;
        left: 31pt;
        border: 2pt solid #17324d;
    }

    .classic-content {
        position: absolute;
        top: 40pt;
        right: 48pt;
        bottom: 36pt;
        left: 48pt;
        text-align: center;
    }

    .classic-logo {
        width: 54pt;
        height: 54pt;
        margin: 0 auto 5pt;
        overflow: hidden;
        border: 2pt solid #c49a3a;
        border-radius: 50%;
        background: #ffffff;
        text-align: center;
    }

    .classic-logo img {
        width: 100%;
        height: 100%;
        padding: 4pt;
    }

    .classic-logo-text {
        color: #17324d;
        font-family: DejaVu Serif, serif;
        font-size: 13pt;
        font-weight: bold;
        line-height: 54pt;
    }

    .classic-organization-name {
        margin: 0;
        color: #17324d;
        font-family: DejaVu Serif, serif;
        font-size: 19pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .classic-organization-tagline {
        margin: 4pt 0 7pt;
        color: #667085;
        font-size: 7pt;
        letter-spacing: 1pt;
        text-transform: uppercase;
    }

    .classic-title {
        margin: 2pt 0 0;
        color: #c49a3a;
        font-family: DejaVu Serif, serif;
        font-size: 34pt;
        font-weight: bold;
        line-height: 1;
    }

    .classic-subtitle {
        margin-top: 3pt;
        color: #17324d;
        font-family: DejaVu Serif, serif;
        font-size: 15pt;
        font-style: italic;
    }

    .classic-presented {
        margin: 14pt 0 5pt;
        color: #59636e;
        font-size: 9pt;
    }

    .classic-student-name {
        display: inline-block;
        min-width: 430pt;
        max-width: 650pt;
        padding: 0 20pt 5pt;
        border-bottom: 1.5pt solid #c49a3a;
        color: #152c43;
        font-family: DejaVu Serif, serif;
        font-size: 25pt;
        font-weight: bold;
        line-height: 1.1;
    }

    .classic-completion {
        margin: 8pt 0 3pt;
        color: #59636e;
        font-size: 8.5pt;
    }

    .classic-course {
        margin: 0 0 9pt;
        color: #17324d;
        font-family: DejaVu Serif, serif;
        font-size: 18pt;
        font-weight: bold;
    }

    .classic-meta-table {
        width: 100%;
        margin-top: 5pt;
        border-collapse: separate;
        border-spacing: 12pt 0;
        table-layout: fixed;
    }

    .classic-meta-table td {
        width: 50%;
        padding: 7pt 10pt;
        border: 1pt solid #d7dde3;
        background: #ffffff;
        text-align: left;
        vertical-align: middle;
    }

    .classic-meta-label {
        display: block;
        margin-bottom: 2pt;
        color: #667085;
        font-size: 6pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .classic-meta-value {
        color: #17324d;
        font-size: 8pt;
        font-weight: bold;
    }

    .classic-footer-table {
        width: 100%;
        margin-top: 10pt;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .classic-footer-table td {
        width: 33.333%;
        height: 72pt;
        vertical-align: bottom;
        text-align: center;
    }

    .classic-signature-image {
        width: 135pt;
        height: 38pt;
    }

    .classic-signature-line {
        width: 150pt;
        margin: 3pt auto 4pt;
        border-top: 1pt solid #263747;
    }

    .classic-signature-title {
        color: #17324d;
        font-family: DejaVu Serif, serif;
        font-size: 8.5pt;
        font-weight: bold;
    }

    .classic-signature-subtitle {
        margin-top: 2pt;
        color: #667085;
        font-size: 6pt;
    }

    .classic-qr-image {
        width: 58pt;
        height: 58pt;
    }

    .classic-qr-fallback {
        width: 58pt;
        height: 58pt;
        margin: 0 auto;
        border: 1pt solid #17324d;
        color: #17324d;
        font-size: 14pt;
        font-weight: bold;
        line-height: 58pt;
    }

    .classic-qr-label {
        margin-top: 2pt;
        color: #667085;
        font-size: 6pt;
    }

    .classic-revoked {
        position: absolute;
        top: 245pt;
        left: 215pt;
        z-index: 20;
        width: 410pt;
        padding: 12pt;
        border: 6pt solid #c62828;
        color: #c62828;
        font-size: 34pt;
        font-weight: bold;
        text-align: center;
        transform: rotate(-15deg);
        opacity: 0.22;
    }
</style>

</head>

<body>

<div class="classic-page">

    <div class="classic-outer-border"></div>
    <div class="classic-inner-border"></div>

    <?= $revokedHtml ?>

    <div class="classic-content">

        <?= $logoHtml ?>

        <h2 class="classic-organization-name">
            <?= $safeOrganizationName ?>
        </h2>

        <div class="classic-organization-tagline">
            <?= $safeOrganizationTagline ?>
        </div>

        <div class="classic-title">
            Certificate
        </div>

        <div class="classic-subtitle">
            of Achievement
        </div>

        <div class="classic-presented">
            This certificate is proudly presented to
        </div>

        <div class="classic-student-name">
            <?= $safeStudentName ?>
        </div>

        <div class="classic-completion">
            for successfully completing the course or field
        </div>

        <div class="classic-course">
            <?= $safeFieldName ?>
        </div>

        <table class="classic-meta-table">

            <tr>

                <td>
                    <span class="classic-meta-label">
                        Certificate ID
                    </span>

                    <span class="classic-meta-value">
                        <?= $safeCertificateId ?>
                    </span>
                </td>

                <td>
                    <span class="classic-meta-label">
                        Issue Date
                    </span>

                    <span class="classic-meta-value">
                        <?= $safeIssueDate ?>
                    </span>
                </td>

            </tr>

        </table>

        <table class="classic-footer-table">

            <tr>

                <td>

                    <?= $instructorSignatureHtml ?>

                    <div class="classic-signature-line"></div>

                    <div class="classic-signature-title">
                        Course Instructor
                    </div>

                    <div class="classic-signature-subtitle">
                        Authorized Signature
                    </div>

                </td>

                <td>

                    <?= $qrCodeHtml ?>

                    <div class="classic-qr-label">
                        Scan to verify
                    </div>

                </td>

                <td>

                    <?= $directorSignatureHtml ?>

                    <div class="classic-signature-line"></div>

                    <div class="classic-signature-title">
                        Authorized Director
                    </div>

                    <div class="classic-signature-subtitle">
                        Official Signature
                    </div>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>
</html>