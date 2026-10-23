<?php

if (!isset($certificate)) {
    http_response_code(403);
    exit('This PDF template cannot be opened directly.');
}

$safeOrganizationName = escape($organizationName);
$safeOrganizationTagline = escape($organizationTagline);
$safeStudentName = escape($certificate['student_name']);
$safeFieldName = escape($certificate['field_name']);
$safeCertificateId = escape($certificate['certificate_id']);
$safeIssueDate = escape($formattedIssueDate);

if ($logoData !== '') {
    $logoHtml = '
        <div class="modern-logo">
            <img
                src="' . escape($logoData) . '"
                alt="Organization Logo"
            >
        </div>
    ';
} else {
    $logoHtml = '
        <div class="modern-logo modern-logo-text">
            JUW
        </div>
    ';
}

$instructorSignatureHtml = '';

if ($instructorSignatureData !== '') {
    $instructorSignatureHtml = '
        <img
            class="modern-signature-image"
            src="' . escape($instructorSignatureData) . '"
            alt="Instructor Signature"
        >
    ';
}

$directorSignatureHtml = '';

if ($directorSignatureData !== '') {
    $directorSignatureHtml = '
        <img
            class="modern-signature-image"
            src="' . escape($directorSignatureData) . '"
            alt="Director Signature"
        >
    ';
}

if ($qrCodeData !== '') {
    $qrCodeHtml = '
        <img
            class="modern-qr-image"
            src="' . escape($qrCodeData) . '"
            alt="Verification QR Code"
        >
    ';
} else {
    $qrCodeHtml = '
        <div class="modern-qr-fallback">
            QR
        </div>
    ';
}

$revokedHtml = '';

if ($isRevoked) {
    $revokedHtml = '
        <div class="modern-revoked">
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
        color: #17324d;
        background: #ffffff;
        font-family: DejaVu Sans, sans-serif;
    }

    .modern-page {
        position: relative;
        width: 841.89pt;
        height: 595.28pt;
        overflow: hidden;
        background:
            linear-gradient(
                135deg,
                rgba(46, 163, 242, 0.12),
                transparent 35%
            ),
            linear-gradient(
                315deg,
                rgba(15, 76, 129, 0.08),
                transparent 35%
            ),
            #f7fbff;
    }

    .modern-border {
        position: absolute;
        top: 15pt;
        right: 15pt;
        bottom: 15pt;
        left: 15pt;
        border: 4pt solid #0f4c81;
    }

    .modern-top-accent {
        position: absolute;
        top: 15pt;
        right: 15pt;
        width: 180pt;
        height: 12pt;
        background: #2ea3f2;
    }

    .modern-bottom-accent {
        position: absolute;
        bottom: 15pt;
        left: 15pt;
        width: 180pt;
        height: 12pt;
        background: #2ea3f2;
    }

    .modern-content {
        position: absolute;
        top: 34pt;
        right: 42pt;
        bottom: 30pt;
        left: 42pt;
    }

    .modern-header-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .modern-header-table td {
        vertical-align: middle;
    }

    .modern-brand-cell {
        width: 70%;
        text-align: left;
    }

    .modern-label-cell {
        width: 30%;
        text-align: right;
    }

    .modern-brand-table {
        border-collapse: collapse;
    }

    .modern-brand-table td {
        vertical-align: middle;
    }

    .modern-logo {
        width: 66pt;
        height: 66pt;
        overflow: hidden;
        border: 3pt solid #2ea3f2;
        border-radius: 10pt;
        background: #ffffff;
        text-align: center;
    }

    .modern-logo img {
        width: 100%;
        height: 100%;
        padding: 5pt;
    }

    .modern-logo-text {
        color: #0f4c81;
        font-size: 14pt;
        font-weight: bold;
        line-height: 66pt;
    }

    .modern-organization-wrap {
        padding-left: 14pt;
    }

    .modern-organization-name {
        margin: 0;
        color: #0f4c81;
        font-size: 19pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .modern-organization-tagline {
        margin-top: 4pt;
        color: #667085;
        font-size: 7pt;
        letter-spacing: 1pt;
        text-transform: uppercase;
    }

    .modern-certificate-label {
        display: inline-block;
        padding: 11pt 16pt;
        border-radius: 9pt;
        color: #ffffff;
        background: #0f4c81;
        font-size: 16pt;
        font-weight: bold;
        letter-spacing: 3pt;
    }

    .modern-divider {
        height: 2pt;
        margin: 18pt 0 16pt;
        background: #2ea3f2;
    }

    .modern-main {
        text-align: center;
    }

    .modern-presented {
        margin: 0 0 8pt;
        color: #667085;
        font-size: 9pt;
    }

    .modern-student-name {
        margin: 0;
        color: #0f4c81;
        font-size: 27pt;
        font-weight: bold;
        line-height: 1.1;
    }

    .modern-name-line {
        width: 190pt;
        height: 3pt;
        margin: 10pt auto 0;
        border-radius: 999pt;
        background: #2ea3f2;
    }

    .modern-completion {
        margin: 13pt 0 4pt;
        color: #667085;
        font-size: 8.5pt;
    }

    .modern-course {
        margin: 0;
        color: #17324d;
        font-size: 18pt;
        font-weight: bold;
    }

    .modern-info-table {
        width: 100%;
        margin-top: 20pt;
        border-collapse: separate;
        border-spacing: 10pt 0;
        table-layout: fixed;
    }

    .modern-info-table td {
        padding: 10pt;
        border: 1pt solid rgba(46, 163, 242, 0.35);
        border-radius: 9pt;
        background: #ffffff;
        vertical-align: middle;
    }

    .modern-info-card {
        text-align: left;
    }

    .modern-info-label {
        display: block;
        margin-bottom: 4pt;
        color: #667085;
        font-size: 6pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .modern-info-value {
        color: #0f4c81;
        font-size: 8.5pt;
        font-weight: bold;
    }

    .modern-qr-cell {
        width: 120pt;
        text-align: center;
    }

    .modern-qr-image {
        width: 56pt;
        height: 56pt;
    }

    .modern-qr-fallback {
        width: 56pt;
        height: 56pt;
        margin: 0 auto;
        border: 1pt solid #0f4c81;
        color: #0f4c81;
        font-size: 13pt;
        font-weight: bold;
        line-height: 56pt;
    }

    .modern-qr-label {
        margin-top: 3pt;
        color: #667085;
        font-size: 6pt;
    }

    .modern-footer-table {
        width: 100%;
        margin-top: 18pt;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .modern-footer-table td {
        width: 33.333%;
        height: 82pt;
        vertical-align: bottom;
        text-align: center;
    }

    .modern-signature-image {
        width: 130pt;
        height: 38pt;
    }

    .modern-signature-line {
        width: 145pt;
        margin: 3pt auto 4pt;
        border-top: 1.5pt solid #2ea3f2;
    }

    .modern-signature-title {
        color: #0f4c81;
        font-size: 8.5pt;
        font-weight: bold;
    }

    .modern-signature-subtitle {
        margin-top: 2pt;
        color: #667085;
        font-size: 6pt;
    }

    .modern-verified-badge {
        width: 74pt;
        height: 74pt;
        margin: 0 auto;
        border: 4pt solid #2ea3f2;
        border-radius: 50%;
        color: #0f4c81;
        background: #ffffff;
        font-size: 8pt;
        font-weight: bold;
        line-height: 74pt;
        letter-spacing: 1pt;
        text-align: center;
    }

    .modern-revoked {
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

<div class="modern-page">

    <div class="modern-border"></div>
    <div class="modern-top-accent"></div>
    <div class="modern-bottom-accent"></div>

    <?= $revokedHtml ?>

    <div class="modern-content">

        <table class="modern-header-table">

            <tr>

                <td class="modern-brand-cell">

                    <table class="modern-brand-table">

                        <tr>

                            <td>
                                <?= $logoHtml ?>
                            </td>

                            <td class="modern-organization-wrap">

                                <h2 class="modern-organization-name">
                                    <?= $safeOrganizationName ?>
                                </h2>

                                <div class="modern-organization-tagline">
                                    <?= $safeOrganizationTagline ?>
                                </div>

                            </td>

                        </tr>

                    </table>

                </td>

                <td class="modern-label-cell">

                    <div class="modern-certificate-label">
                        CERTIFICATE
                    </div>

                </td>

            </tr>

        </table>

        <div class="modern-divider"></div>

        <div class="modern-main">

            <div class="modern-presented">
                This certificate is proudly presented to
            </div>

            <h1 class="modern-student-name">
                <?= $safeStudentName ?>
            </h1>

            <div class="modern-name-line"></div>

            <div class="modern-completion">
                for successfully completing the course or field
            </div>

            <div class="modern-course">
                <?= $safeFieldName ?>
            </div>

        </div>

        <table class="modern-info-table">

            <tr>

                <td class="modern-info-card">

                    <span class="modern-info-label">
                        Certificate ID
                    </span>

                    <span class="modern-info-value">
                        <?= $safeCertificateId ?>
                    </span>

                </td>

                <td class="modern-info-card">

                    <span class="modern-info-label">
                        Issue Date
                    </span>

                    <span class="modern-info-value">
                        <?= $safeIssueDate ?>
                    </span>

                </td>

                <td class="modern-qr-cell">

                    <?= $qrCodeHtml ?>

                    <div class="modern-qr-label">
                        Scan to verify
                    </div>

                </td>

            </tr>

        </table>

        <table class="modern-footer-table">

            <tr>

                <td>

                    <?= $instructorSignatureHtml ?>

                    <div class="modern-signature-line"></div>

                    <div class="modern-signature-title">
                        Course Instructor
                    </div>

                    <div class="modern-signature-subtitle">
                        Authorized Signature
                    </div>

                </td>

                <td>

                    <div class="modern-verified-badge">
                        VERIFIED
                    </div>

                </td>

                <td>

                    <?= $directorSignatureHtml ?>

                    <div class="modern-signature-line"></div>

                    <div class="modern-signature-title">
                        Authorized Director
                    </div>

                    <div class="modern-signature-subtitle">
                        Official Signature
                    </div>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>
</html>