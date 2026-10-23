<?php

if (!isset($certificate)) {
    http_response_code(403);
    exit('This template cannot be opened directly.');
}

$logoPath = $logoPath ?? '';
$instructorSignaturePath =
    $instructorSignaturePath ?? '';
$directorSignaturePath =
    $directorSignaturePath ?? '';

?>

<main class="certificate-wrapper">

    <section class="certificate gold-certificate">

        <div class="gold-frame gold-frame-outer"></div>
        <div class="gold-frame gold-frame-inner"></div>

        <div class="gold-corner gold-corner-top-left"></div>
        <div class="gold-corner gold-corner-top-right"></div>
        <div class="gold-corner gold-corner-bottom-left"></div>
        <div class="gold-corner gold-corner-bottom-right"></div>

        <div class="gold-certificate-inner">

            <div class="gold-header">

                <?php if ($logoPath !== ''): ?>

                    <div class="gold-logo">

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($logoPath, '/')
                            ) ?>"
                            alt="Organization logo"
                        >

                    </div>

                <?php else: ?>

                    <div class="gold-logo gold-logo-text">
                        JUW
                    </div>

                <?php endif; ?>

                <h2 class="gold-organization-name">
                    <?= escape($organizationName) ?>
                </h2>

                <p class="gold-organization-tagline">
                    <?= escape($organizationTagline) ?>
                </p>

            </div>

            <div class="gold-ribbon">
                Certificate of Achievement
            </div>

            <p class="gold-presented-text">
                This certificate is proudly presented to
            </p>

            <h1 class="gold-student-name">
                <?= escape($certificate['student_name']) ?>
            </h1>

            <div class="gold-divider">
                <span></span>
            </div>

            <p class="gold-completion-text">
                for successfully completing the course or field
            </p>

            <h3 class="gold-course-name">
                <?= escape($certificate['field_name']) ?>
            </h3>

            <section class="gold-meta-grid">

                <div class="gold-meta-card">

                    <span class="gold-meta-label">
                        Certificate ID
                    </span>

                    <strong class="gold-meta-value">
                        <?= escape($certificate['certificate_id']) ?>
                    </strong>

                </div>

                <div class="gold-seal">

                    <div class="gold-seal-inner">
                        VERIFIED
                    </div>

                </div>

                <div class="gold-meta-card">

                    <span class="gold-meta-label">
                        Issue Date
                    </span>

                    <strong class="gold-meta-value">
                        <?= escape($formattedIssueDate) ?>
                    </strong>

                </div>

            </section>

            <section class="gold-footer">

                <div class="gold-signature">

                    <?php if ($instructorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($instructorSignaturePath, '/')
                            ) ?>"
                            alt="Instructor signature"
                        >

                    <?php endif; ?>

                    <div class="gold-signature-line"></div>

                    <strong>
                        Course Instructor
                    </strong>

                    <span>
                        Authorized Signature
                    </span>

                </div>

                <div class="gold-qr-box">

                    <img
                        src="<?= escape($qrCodeUrl) ?>"
                        alt="Certificate verification QR code"
                    >

                    <span>
                        Scan to verify
                    </span>

                </div>

                <div class="gold-signature">

                    <?php if ($directorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($directorSignaturePath, '/')
                            ) ?>"
                            alt="Director signature"
                        >

                    <?php endif; ?>

                    <div class="gold-signature-line"></div>

                    <strong>
                        Authorized Director
                    </strong>

                    <span>
                        Official Signature
                    </span>

                </div>

            </section>

        </div>

    </section>

</main>