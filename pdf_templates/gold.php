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
        <div class="gold-logo">

            <img
                src="' . escape($logoData) . '"
                alt="Organization Logo"
            >

        </div>
    ';

} else {

    $logoHtml = '
        <div class="gold-logo gold-logo-text">
            JUW
        </div>
    ';
}

$instructorSignatureHtml = '';

if ($instructorSignatureData !== '') {

    $instructorSignatureHtml = '
        <img
            class="gold-signature-image"
            src="' . escape($instructorSignatureData) . '"
            alt="Instructor Signature"
        >
    ';
}

$directorSignatureHtml = '';

if ($directorSignatureData !== '') {

    $directorSignatureHtml = '
        <img
            class="gold-signature-image"
            src="' . escape($directorSignatureData) . '"
            alt="Director Signature"
        >
    ';
}

if ($qrCodeData !== '') {

    $qrCodeHtml = '
        <img
            class="gold-qr-image"
            src="' . escape($qrCodeData) . '"
            alt="Verification QR Code"
        >
    ';

} else {

    $qrCodeHtml = '
        <div class="gold-qr-fallback">
            QR
        </div>
    ';
}

$revokedHtml = '';

if ($isRevoked) {

    $revokedHtml = '
        <div class="gold-revoked">
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

*{

    box-sizing:border-box;
}

html,
body{

    width:841.89pt;
    height:595.28pt;

    margin:0;
    padding:0;

    overflow:hidden;

    font-family:DejaVu Sans,sans-serif;

    background:#fff;
}

.gold-page{

    position:relative;

    width:841.89pt;
    height:595.28pt;

    overflow:hidden;

    background:

        linear-gradient(
            rgba(255,251,239,.98),
            rgba(255,247,224,.98)
        );

}

.gold-outer-border{

    position:absolute;

    top:14pt;
    left:14pt;
    right:14pt;
    bottom:14pt;

    border:8pt solid #d4af37;
}

.gold-inner-border{

    position:absolute;

    top:30pt;
    left:30pt;
    right:30pt;
    bottom:30pt;

    border:2pt solid #7a5a18;
}

.gold-content{

    position:absolute;

    top:38pt;
    left:48pt;
    right:48pt;
    bottom:34pt;

    text-align:center;
}

/* ---------- CSS CONTINUES ---------- */
.gold-logo {
    width: 64pt;
    height: 64pt;
    margin: 0 auto 6pt;
    overflow: hidden;
    border: 3pt solid #d4af37;
    border-radius: 50%;
    background: #ffffff;
    text-align: center;
}

.gold-logo img {
    width: 100%;
    height: 100%;
    padding: 5pt;
}

.gold-logo-text {
    color: #7a5a18;
    font-family: DejaVu Serif, serif;
    font-size: 14pt;
    font-weight: bold;
    line-height: 64pt;
}

.gold-organization-name {
    margin: 0;
    color: #3b2a12;
    font-family: DejaVu Serif, serif;
    font-size: 20pt;
    font-weight: bold;
    text-transform: uppercase;
}

.gold-organization-tagline {
    margin: 4pt 0 9pt;
    color: #806d4f;
    font-size: 7pt;
    letter-spacing: 1pt;
    text-transform: uppercase;
}

.gold-ribbon {
    display: inline-block;
    margin: 2pt auto 10pt;
    padding: 8pt 22pt;
    border-radius: 999pt;
    color: #ffffff;
    background: #a77d1f;
    font-family: DejaVu Serif, serif;
    font-size: 18pt;
    font-weight: bold;
    letter-spacing: 0.6pt;
}

.gold-presented {
    margin: 10pt 0 5pt;
    color: #6d6252;
    font-size: 9pt;
}

.gold-student-name {
    margin: 0;
    color: #3b2a12;
    font-family: DejaVu Serif, serif;
    font-size: 28pt;
    font-weight: bold;
    line-height: 1.1;
}

.gold-divider {
    width: 340pt;
    margin: 8pt auto;
    border-top: 1.5pt solid #d4af37;
}

.gold-completion {
    margin: 9pt 0 3pt;
    color: #6d6252;
    font-size: 8.5pt;
}

.gold-course {
    margin: 0;
    color: #7a5a18;
    font-family: DejaVu Serif, serif;
    font-size: 19pt;
    font-weight: bold;
}

.gold-meta-table {
    width: 100%;
    margin-top: 17pt;
    border-collapse: separate;
    border-spacing: 12pt 0;
    table-layout: fixed;
}

.gold-meta-table td {
    vertical-align: middle;
}

.gold-meta-card {
    padding: 9pt 11pt;
    border: 1pt solid #d4af37;
    background: #ffffff;
    text-align: left;
}

.gold-meta-label {
    display: block;
    margin-bottom: 3pt;
    color: #806d4f;
    font-size: 6pt;
    font-weight: bold;
    text-transform: uppercase;
}

.gold-meta-value {
    color: #3b2a12;
    font-size: 8.5pt;
    font-weight: bold;
}

.gold-seal-cell {
    width: 100pt;
    text-align: center;
}

.gold-seal {
    width: 74pt;
    height: 74pt;
    margin: 0 auto;
    border: 4pt double #d4af37;
    border-radius: 50%;
    color: #7a5a18;
    background: #ffffff;
    font-size: 8pt;
    font-weight: bold;
    line-height: 74pt;
    letter-spacing: 1pt;
    text-align: center;
}

.gold-footer-table {
    width: 100%;
    margin-top: 15pt;
    border-collapse: collapse;
    table-layout: fixed;
}

.gold-footer-table td {
    width: 33.333%;
    height: 82pt;
    vertical-align: bottom;
    text-align: center;
}

.gold-signature-image {
    width: 130pt;
    height: 38pt;
}

.gold-signature-line {
    width: 145pt;
    margin: 3pt auto 4pt;
    border-top: 1.5pt solid #d4af37;
}

.gold-signature-title {
    color: #3b2a12;
    font-family: DejaVu Serif, serif;
    font-size: 8.5pt;
    font-weight: bold;
}

.gold-signature-subtitle {
    margin-top: 2pt;
    color: #806d4f;
    font-size: 6pt;
}

.gold-qr-image {
    width: 56pt;
    height: 56pt;
}

.gold-qr-fallback {
    width: 56pt;
    height: 56pt;
    margin: 0 auto;
    border: 1pt solid #7a5a18;
    color: #7a5a18;
    font-size: 13pt;
    font-weight: bold;
    line-height: 56pt;
}

.gold-qr-label {
    margin-top: 3pt;
    color: #806d4f;
    font-size: 6pt;
}

.gold-revoked {
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

<div class="gold-page">

    <div class="gold-outer-border"></div>
    <div class="gold-inner-border"></div>

    <?= $revokedHtml ?>

    <div class="gold-content">

        <?= $logoHtml ?>

        <h2 class="gold-organization-name">
            <?= $safeOrganizationName ?>
        </h2>

        <div class="gold-organization-tagline">
            <?= $safeOrganizationTagline ?>
        </div>

        <div class="gold-ribbon">
            Certificate of Achievement
        </div>

        <div class="gold-presented">
            This certificate is proudly presented to
        </div>

        <div class="gold-student-name">
            <?= $safeStudentName ?>
        </div>

        <div class="gold-divider"></div>

        <div class="gold-completion">
            for successfully completing the course or field
        </div>

        <div class="gold-course">
            <?= $safeFieldName ?>
        </div>

        <table class="gold-meta-table">

            <tr>

                <td>

                    <div class="gold-meta-card">

                        <span class="gold-meta-label">
                            Certificate ID
                        </span>

                        <span class="gold-meta-value">
                            <?= $safeCertificateId ?>
                        </span>

                    </div>

                </td>

                <td class="gold-seal-cell">

                    <div class="gold-seal">
                        VERIFIED
                    </div>

                </td>

                <td>

                    <div class="gold-meta-card">

                        <span class="gold-meta-label">
                            Issue Date
                        </span>

                        <span class="gold-meta-value">
                            <?= $safeIssueDate ?>
                        </span>

                    </div>

                </td>

            </tr>

        </table>

        <table class="gold-footer-table">

            <tr>

                <td>

                    <?= $instructorSignatureHtml ?>

                    <div class="gold-signature-line"></div>

                    <div class="gold-signature-title">
                        Course Instructor
                    </div>

                    <div class="gold-signature-subtitle">
                        Authorized Signature
                    </div>

                </td>

                <td>

                    <?= $qrCodeHtml ?>

                    <div class="gold-qr-label">
                        Scan to verify
                    </div>

                </td>

                <td>

                    <?= $directorSignatureHtml ?>

                    <div class="gold-signature-line"></div>

                    <div class="gold-signature-title">
                        Authorized Director
                    </div>

                    <div class="gold-signature-subtitle">
                        Official Signature
                    </div>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>
</html>