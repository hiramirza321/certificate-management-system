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

    <section class="certificate modern-certificate">

        <div class="modern-accent modern-accent-top"></div>
        <div class="modern-accent modern-accent-bottom"></div>

        <div class="modern-certificate-inner">

            <div class="modern-header">

                <div class="modern-brand-block">

                    <?php if ($logoPath !== ''): ?>

                        <div class="modern-logo">

                            <img
                                src="<?= escape(
                                    BASE_URL . '/' .
                                    ltrim($logoPath, '/')
                                ) ?>"
                                alt="Organization logo"
                            >

                        </div>

                    <?php else: ?>

                        <div class="modern-logo modern-logo-text">
                            JUW
                        </div>

                    <?php endif; ?>

                    <div class="modern-organization">

                        <h2>
                            <?= escape($organizationName) ?>
                        </h2>

                        <p>
                            <?= escape($organizationTagline) ?>
                        </p>

                    </div>

                </div>

                <div class="modern-certificate-label">
                    CERTIFICATE
                </div>

            </div>

            <div class="modern-divider"></div>

            <section class="modern-main-content">

                <p class="modern-presented-text">
                    This certificate is proudly presented to
                </p>

                <h1 class="modern-student-name">
                    <?= escape($certificate['student_name']) ?>
                </h1>

                <p class="modern-completion-text">
                    for successfully completing the course or field
                </p>

                <h3 class="modern-course-name">
                    <?= escape($certificate['field_name']) ?>
                </h3>

            </section>

            <section class="modern-info-grid">

                <div class="modern-info-card">

                    <span class="modern-info-label">
                        Certificate ID
                    </span>

                    <strong class="modern-info-value">
                        <?= escape($certificate['certificate_id']) ?>
                    </strong>

                </div>

                <div class="modern-info-card">

                    <span class="modern-info-label">
                        Issue Date
                    </span>

                    <strong class="modern-info-value">
                        <?= escape($formattedIssueDate) ?>
                    </strong>

                </div>

                <div class="modern-qr-card">

                    <img
                        src="<?= escape($qrCodeUrl) ?>"
                        alt="Certificate verification QR code"
                    >

                    <span>
                        Scan to verify
                    </span>

                </div>

            </section>

            <section class="modern-footer">

                <div class="modern-signature">

                    <?php if ($instructorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($instructorSignaturePath, '/')
                            ) ?>"
                            alt="Instructor signature"
                        >

                    <?php endif; ?>

                    <div class="modern-signature-line"></div>

                    <strong>
                        Course Instructor
                    </strong>

                    <span>
                        Authorized Signature
                    </span>

                </div>

                <div class="modern-issued-badge">
                    VERIFIED
                </div>

                <div class="modern-signature">

                    <?php if ($directorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($directorSignaturePath, '/')
                            ) ?>"
                            alt="Director signature"
                        >

                    <?php endif; ?>

                    <div class="modern-signature-line"></div>

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