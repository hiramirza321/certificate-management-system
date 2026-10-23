<?php

if (!isset($certificate)) {
    exit('This template cannot be opened directly.');
}

?>

<main class="certificate-wrapper">

    <section class="certificate">

        <div class="corner corner-top-left"></div>
        <div class="corner corner-top-right"></div>
        <div class="corner corner-bottom-left"></div>
        <div class="corner corner-bottom-right"></div>

        <div class="certificate-inner">

            <?php if ($logoPath !== ''): ?>

                <div class="certificate-logo">

                    <img
                        src="<?= escape(
                            BASE_URL . '/' . ltrim($logoPath, '/')
                        ) ?>"
                        alt="Organization logo"
                    >

                </div>

            <?php else: ?>

                <div class="certificate-logo">
                    DS
                </div>

            <?php endif; ?>

            <h2 class="organization-name">
                <?= escape($organizationName) ?>
            </h2>

            <p class="organization-tagline">
                <?= escape($organizationTagline) ?>
            </p>

            <h1 class="certificate-title">
                Certificate
            </h1>

            <p class="certificate-subtitle">
                of Achievement
            </p>

            <p class="presented-text">
                This certificate is proudly presented to
            </p>

            <div class="student-name">
                <?= escape($certificate['student_name']) ?>
            </div>

            <p class="completion-text">
                for successfully completing the course or field
            </p>

            <h3 class="course-name">
                <?= escape($certificate['field_name']) ?>
            </h3>

            <div class="certificate-meta">

                <div class="meta-item">

                    <span class="meta-label">
                        Certificate ID
                    </span>

                    <span class="meta-value">
                        <?= escape($certificate['certificate_id']) ?>
                    </span>

                </div>

                <div class="meta-item">

                    <span class="meta-label">
                        Issue Date
                    </span>

                    <span class="meta-value">
                        <?= escape($formattedIssueDate) ?>
                    </span>

                </div>

            </div>

            <div class="certificate-footer">

                <div class="signature">

                    <?php if ($instructorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($instructorSignaturePath, '/')
                            ) ?>"
                            alt="Instructor signature"
                        >

                    <?php endif; ?>

                    <div class="signature-line"></div>

                    <strong>
                        Course Instructor
                    </strong>

                    <span>
                        Authorized Signature
                    </span>

                </div>

                <div class="qr-box">

                    <img
                        src="<?= escape($qrCodeUrl) ?>"
                        alt="Certificate verification QR code"
                    >

                    <span>
                        Scan to verify
                    </span>

                </div>

                <div class="signature">

                    <?php if ($directorSignaturePath !== ''): ?>

                        <img
                            src="<?= escape(
                                BASE_URL . '/' .
                                ltrim($directorSignaturePath, '/')
                            ) ?>"
                            alt="Director signature"
                        >

                    <?php endif; ?>

                    <div class="signature-line"></div>

                    <strong>
                        Authorized Director
                    </strong>

                    <span>
                        Official Signature
                    </span>

                </div>

            </div>

        </div>

    </section>

</main>