<?php

if (
    !isset($certificate) ||
    !is_array($certificate) ||
    !isset($customTemplate) ||
    !is_array($customTemplate)
) {
    http_response_code(500);
    exit('Custom certificate template is unavailable.');
}

$imageUrl =
    rtrim(BASE_URL, '/') . '/' .
    ltrim(
        (string) $customTemplate['template_image_path'],
        '/\\'
    );

$studentNameX =
    (float) $customTemplate['student_name_x'];

$studentNameY =
    (float) $customTemplate['student_name_y'];

$certificateIdX =
    (float) $customTemplate['certificate_id_x'];

$certificateIdY =
    (float) $customTemplate['certificate_id_y'];

$verificationLinkX =
    (float) $customTemplate['verification_link_x'];

$verificationLinkY =
    (float) $customTemplate['verification_link_y'];

$fontSize = max(
    10,
    (int) $customTemplate['font_size']
);

$fontColor =
    (string) $customTemplate['font_color'];
?>

<main class="certificate-wrapper">

    <section
        class="custom-certificate"
        id="custom_certificate"
    >

        <img
            class="custom-template-image"
            id="custom_template_image"
            src="<?= escape($imageUrl) ?>"
            alt="Certificate template"
        >

        <div
            class="custom-field custom-student-name"
            data-original-x="<?= $studentNameX ?>"
            data-original-y="<?= $studentNameY ?>"
            data-original-font-size="<?= $fontSize ?>"
        >
            <?= escape($certificate['student_name']) ?>
        </div>

        <div
            class="custom-field custom-certificate-id"
            data-original-x="<?= $certificateIdX ?>"
            data-original-y="<?= $certificateIdY ?>"
        >
            Certificate ID:
            <?= escape($certificate['certificate_id']) ?>
        </div>

        <a
            class="custom-field custom-verification-link"
            data-original-x="<?= $verificationLinkX ?>"
            data-original-y="<?= $verificationLinkY ?>"
            href="<?= escape($verificationUrl) ?>"
            target="_blank"
            rel="noopener"
        >
            Verify:
            <?= escape($verificationUrl) ?>
        </a>

    </section>

</main>

<style>

.certificate-wrapper {
    width: 100%;
    min-height: 0 !important;
    height: auto !important;
    padding: 0 !important;
    margin: 20px auto !important;
}

.custom-certificate {
    position: relative;
    width: min(900px, calc(100% - 30px));
    height: auto !important;
    min-height: 0 !important;
    margin: 0 auto;
    overflow: hidden;
}

.custom-template-image {
    display: block;
    width: 100%;
    height: auto;
    margin: 0;
}

.custom-field {
    position: absolute;
    transform: translate(-50%, -50%);
    z-index: 5;
    line-height: 1.2;
}

.custom-certificate-id {
    font-size: 13px;
    white-space: nowrap;
}

.custom-verification-link {
    font-size: 11px;
    white-space: nowrap;
}

.custom-student-name {
    color: <?= escape($fontColor) ?>;
    font-weight: 700;
    text-align: center;
}



</style>

<script>
(function () {
    'use strict';

    const certificate =
        document.getElementById('custom_certificate');

    const image =
        document.getElementById('custom_template_image');

    if (!certificate || !image) {
        return;
    }

    function positionCustomFields() {

        /*
         * Database coordinates original uploaded
         * image resolution ke pixels hain.
         */
        const naturalWidth = image.naturalWidth;
        const naturalHeight = image.naturalHeight;

        if (!naturalWidth || !naturalHeight) {
            return;
        }

        /*
         * Browser mein image kitni size par
         * display ho rahi hai.
         */
        const displayedWidth = image.clientWidth;
        const displayedHeight = image.clientHeight;

        if (!displayedWidth || !displayedHeight) {
            return;
        }

        const scaleX =
            displayedWidth / naturalWidth;

        const scaleY =
            displayedHeight / naturalHeight;

        const fields =
            certificate.querySelectorAll(
                '.custom-field'
            );

        fields.forEach(function (field) {

            const originalX =
                Number(field.dataset.originalX) || 0;

            const originalY =
                Number(field.dataset.originalY) || 0;

            field.style.left =
                (originalX * scaleX) + 'px';

            field.style.top =
                (originalY * scaleY) + 'px';

            /*
             * Student name ka font bhi image
             * ke scale ke saath resize hoga.
             */
            if (
                field.classList.contains(
                    'custom-student-name'
                )
            ) {
                const originalFontSize =
                    Number(
                        field.dataset.originalFontSize
                    ) || 24;

                field.style.fontSize =
                    Math.max(
                        10,
                        originalFontSize * scaleX
                    ) + 'px';
            }
        });
    }

    image.addEventListener(
        'load',
        positionCustomFields
    );

    window.addEventListener(
        'resize',
        positionCustomFields
    );

    if (image.complete) {
        positionCustomFields();
    }

})();
</script>