<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Template ID
|--------------------------------------------------------------------------
*/

$templateId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$templateId || $templateId < 1) {
    http_response_code(400);
    exit('Valid template ID is required.');
}

/*
|--------------------------------------------------------------------------
| Load Template
|--------------------------------------------------------------------------
*/
$statement = $pdo->prepare(
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

        field_name_x,
        field_name_y,

        issue_date_x,
        issue_date_y,

        font_size,
        font_color,
        status

     FROM certificate_templates
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $templateId,
]);

$template = $statement->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    http_response_code(404);
    exit('Certificate template not found.');
}

$imageUrl =
    rtrim(BASE_URL, '/') . '/' .
    ltrim(
        (string) $template['template_image_path'],
        '/\\'
    );
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
        Template Position Editor |
        <?= escape((string) $template['template_name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= escape(BASE_URL) ?>/assets/style.css?v=20"
    >

    <style>
        .editor-page {
            width: min(1450px, calc(100% - 32px));
            margin: 35px auto 60px;
        }

        .editor-heading {
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        .editor-heading h1 {
            margin: 0 0 7px;
            color: #17324d;
            font-size: 29px;
        }

        .editor-heading p {
            margin: 0;
            color: #667085;
            line-height: 1.6;
        }

        .editor-layout {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr) 330px;
            gap: 24px;
            align-items: start;
        }

        .editor-preview-card,
        .editor-controls {
            border-radius: 18px;
            background: #ffffff;
            box-shadow:
                0 18px 45px rgba(15, 35, 55, 0.12);
        }

        .editor-preview-card {
            padding: 18px;
            overflow: auto;
        }

        .template-stage-wrapper {
            position: relative;
            width: 100%;
            overflow: auto;
            border: 1px solid #d7dde3;
            border-radius: 12px;
            background: #e9eef3;
        }

        .template-stage {
            position: relative;
            width: 100%;
            line-height: 0;
            user-select: none;
        }

        .template-stage-image {
            width: 100%;
            height: auto;
            display: block;
            pointer-events: none;
        }

        .draggable-field {
            position: absolute;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 130px;
            min-height: 32px;
            padding: 6px 12px;
            border: 2px dashed currentColor;
            border-radius: 7px;
            background: rgba(255, 255, 255, 0.88);
            line-height: 1.2;
            white-space: nowrap;
            cursor: move;
            touch-action: none;
            transform: translate(-50%, -50%);
        }

        .draggable-field.dragging {
            opacity: 0.82;
            box-shadow:
                0 10px 25px rgba(15, 35, 55, 0.25);
        }

        .student-field {
            color: #0f4c81;
            font-weight: 800;
        }

        .certificate-id-field {
            color: #8a6518;
            font-size: 14px;
            font-weight: 700;
        }

        .verification-field {
            color: #16803c;
            font-size: 12px;
            font-weight: 700;
        }

        .editor-controls {
            position: sticky;
            top: 20px;
            padding: 24px;
        }

        .editor-controls h2 {
            margin: 0 0 8px;
            color: #17324d;
            font-size: 21px;
        }

        .editor-controls > p {
            margin: 0 0 22px;
            color: #667085;
            font-size: 13px;
            line-height: 1.6;
        }

        .coordinate-group {
            margin-bottom: 18px;
            padding: 15px;
            border: 1px solid #e1e6eb;
            border-radius: 12px;
            background: #f8fafc;
        }

        .coordinate-group strong {
            display: block;
            margin-bottom: 11px;
            color: #17324d;
            font-size: 14px;
        }

        .coordinate-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .coordinate-grid label {
            display: block;
            margin-bottom: 5px;
            color: #667085;
            font-size: 11px;
            font-weight: 700;
        }

        .coordinate-grid input {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #d7dde3;
            border-radius: 8px;
            background: #ffffff;
        }

        .editor-message {
            display: none;
            margin-bottom: 18px;
            padding: 13px 14px;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.5;
        }

        .editor-message-success {
            display: block;
            color: #0f612c;
            border: 1px solid #a8d9b7;
            background: #edfff2;
        }

        .editor-message-error {
            display: block;
            color: #8f1f1f;
            border: 1px solid #efb5b5;
            background: #fff1f1;
        }

        .editor-actions {
            display: grid;
            gap: 10px;
        }

        .editor-actions .button {
            width: 100%;
        }

        .editor-help {
            margin-top: 16px;
            padding: 13px;
            border-radius: 10px;
            color: #667085;
            background: #f8fafc;
            font-size: 12px;
            line-height: 1.6;
        }
 



        @media (max-width: 1000px) {
            .editor-layout {
                grid-template-columns: 1fr;
            }

            .editor-controls {
                position: static;
            }
        }
    </style>

</head>

<body>

<header class="site-header">

    <div class="page-wrapper header-inner">

        <a
            class="brand"
            href="<?= escape(BASE_URL) ?>/admin/index.php"
        >

            <div class="brand-logo">
                DS
            </div>

            <div class="brand-text">

                <strong>
                    <?= escape(ORGANIZATION_NAME) ?>
                </strong>

                <span>
                    Template Position Editor
                </span>

            </div>

        </a>

        <nav class="navigation">

            <a href="<?= escape(BASE_URL) ?>/admin/index.php">
                Dashboard
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/template-upload.php">
                Upload Template
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>

<main class="editor-page">

    <div class="editor-heading">

        <div>

            <h1>
                <?= escape((string) $template['template_name']) ?>
            </h1>

            <p>
                Drag the fields with your mouse. Their positions will be saved
    based on the original dimensions of the uploaded image.
            </p>

        </div>

        <a
            class="button button-secondary"
            href="<?= escape(BASE_URL) ?>/admin/template-upload.php"
        >
            Upload Another Template
        </a>

    </div>

    <div class="editor-layout">

        <section class="editor-preview-card">

            <div class="template-stage-wrapper">

                <div
                    class="template-stage"
                    id="template_stage"
                >

                    <img
                        class="template-stage-image"
                        id="template_image"
                        src="<?= escape($imageUrl) ?>"
                        alt="Uploaded certificate template"
                    >

                    <div
                        class="draggable-field student-field"
                        id="student_field"
                        data-field="student"
                    >
                        Student Name
                    </div>

                    <div
                        class="draggable-field certificate-id-field"
                        id="certificate_id_field"
                        data-field="certificate_id"
                    >
                        ID: IBRAS-2026-000001
                    </div>

                    <div
                        class="draggable-field verification-field"
                        id="verification_field"
                        data-field="verification"
                    >
                        Verify: yourwebsite.com/verify
                    </div>




                </div>

            </div>

        </section>

        <aside class="editor-controls">

            <h2>
                Field Positions
            </h2>

            <p>
               Drag the fields to automatically update their coordinates.
            </p>

            <div
                class="editor-message"
                id="editor_message"
            ></div>

            <input
                type="hidden"
                id="template_id"
                value="<?= escape((string) $template['id']) ?>"
            >

            <div class="coordinate-group">

                <strong>
                    Student Name
                </strong>

                <div class="coordinate-grid">

                    <div>

                        <label for="student_name_x">
                            X Position
                        </label>

                        <input
                            type="number"
                            id="student_name_x"
                            value="<?= escape(
                                (string) $template['student_name_x']
                            ) ?>"
                            min="0"
                        >

                    </div>

                    <div>

                        <label for="student_name_y">
                            Y Position
                        </label>

                        <input
                            type="number"
                            id="student_name_y"
                            value="<?= escape(
                                (string) $template['student_name_y']
                            ) ?>"
                            min="0"
                        >

                    </div>

                </div>

            </div>

            <div class="coordinate-group">

                <strong>
                    Certificate ID
                </strong>

                <div class="coordinate-grid">

                    <div>

                        <label for="certificate_id_x">
                            X Position
                        </label>

                        <input
                            type="number"
                            id="certificate_id_x"
                            value="<?= escape(
                                (string) $template['certificate_id_x']
                            ) ?>"
                            min="0"
                        >

                    </div>

                    <div>

                        <label for="certificate_id_y">
                            Y Position
                        </label>

                        <input
                            type="number"
                            id="certificate_id_y"
                            value="<?= escape(
                                (string) $template['certificate_id_y']
                            ) ?>"
                            min="0"
                        >

                    </div>

                </div>

            </div>

            <div class="coordinate-group">

    <strong>
        Verification Link
    </strong>

    <div class="coordinate-grid">

        <div>
            <label for="verification_link_x">
                X Position
            </label>

            <input
                type="number"
                id="verification_link_x"
                value="<?= (int) $template['verification_link_x'] ?>"
                min="0"
            >
        </div>

        <div>
            <label for="verification_link_y">
                Y Position
            </label>

            <input
                type="number"
                id="verification_link_y"
                value="<?= (int) $template['verification_link_y'] ?>"
                min="0"
            >
        </div>

    </div>

</div>

<div
    class="editor-message"
    id="editor_message"
></div>

<div class="editor-actions">

    <button
        class="button button-primary"
        type="button"
        id="save_positions"
    >
        Save Positions
    </button>

    <a
        class="button button-secondary"
        href="<?= escape(BASE_URL) ?>/admin/index.php"
    >
        Back to Dashboard
    </a>

</div>
            <div class="editor-help">
               X position is the distance from the left, and Y position is the distance
from the top. Coordinates are saved based on the original resolution
of the uploaded image.
            </div>

        </aside>

    </div>

</main>

<footer class="site-footer">

    &copy;
    <?= date('Y') ?>
    <?= escape(ORGANIZATION_NAME) ?>.
    Template Editor.

</footer>

<script>
'use strict';

const stage =
    document.getElementById('template_stage');

const image =
    document.getElementById('template_image');

const messageBox =
    document.getElementById('editor_message');

const saveButton =
    document.getElementById('save_positions');

/*
|--------------------------------------------------------------------------
| Fields
|--------------------------------------------------------------------------
*/

const fields = {
    student: {
        element:
            document.getElementById('student_field'),

        xInput:
            document.getElementById('student_name_x'),

        yInput:
            document.getElementById('student_name_y')
    },

    certificate_id: {
        element:
            document.getElementById('certificate_id_field'),

        xInput:
            document.getElementById('certificate_id_x'),

        yInput:
            document.getElementById('certificate_id_y')
    },

    verification: {
        element:
            document.getElementById('verification_field'),

        xInput:
            document.getElementById('verification_link_x'),

        yInput:
            document.getElementById('verification_link_y')
    }



};

/*
|--------------------------------------------------------------------------
| Validate Required Elements
|--------------------------------------------------------------------------
*/

if (!stage) {
    console.error(
        'Missing element: #template_stage'
    );
}

if (!image) {
    console.error(
        'Missing element: #template_image'
    );
}

Object.entries(fields).forEach(
    function ([fieldName, field]) {
        if (!field.element) {
            console.error(
                'Missing draggable element:',
                fieldName
            );
        }

        if (!field.xInput) {
            console.error(
                'Missing X input:',
                fieldName
            );
        }

        if (!field.yInput) {
            console.error(
                'Missing Y input:',
                fieldName
            );
        }
    }
);

/*
|--------------------------------------------------------------------------
| Scale
|--------------------------------------------------------------------------
*/

function getScale() {
    if (!image) {
        return {
            x: 1,
            y: 1
        };
    }

    const naturalWidth =
        image.naturalWidth;

    const naturalHeight =
        image.naturalHeight;

    if (
        !naturalWidth ||
        !naturalHeight
    ) {
        return {
            x: 1,
            y: 1
        };
    }

    return {
        x:
            image.clientWidth /
            naturalWidth,

        y:
            image.clientHeight /
            naturalHeight
    };
}

/*
|--------------------------------------------------------------------------
| Position Field
|--------------------------------------------------------------------------
*/

function positionField(fieldName) {
    const field =
        fields[fieldName];

    if (
        !field ||
        !field.element ||
        !field.xInput ||
        !field.yInput
    ) {
        return;
    }

    const scale =
        getScale();

    const originalX =
        Number(
            field.xInput.value
        ) || 0;

    const originalY =
        Number(
            field.yInput.value
        ) || 0;

    field.element.style.left =
        (originalX * scale.x) +
        'px';

    field.element.style.top =
        (originalY * scale.y) +
        'px';
}

/*
|--------------------------------------------------------------------------
| Position All Fields
|--------------------------------------------------------------------------
*/

function positionAllFields() {
    Object.keys(fields).forEach(
        function (fieldName) {
            positionField(fieldName);
        }
    );
}

/*
|--------------------------------------------------------------------------
| Dragging
|--------------------------------------------------------------------------
*/

function makeDraggable(fieldName) {
    const field =
        fields[fieldName];

    if (
        !field ||
        !field.element ||
        !field.xInput ||
        !field.yInput ||
        !stage ||
        !image
    ) {
        console.warn(
            'Skipping draggable field:',
            fieldName
        );

        return;
    }

    const element =
        field.element;

    let dragging = false;

    element.addEventListener(
        'pointerdown',
        function (event) {
            dragging = true;

            element.classList.add(
                'dragging'
            );

            element.setPointerCapture(
                event.pointerId
            );

            event.preventDefault();
        }
    );

    element.addEventListener(
        'pointermove',
        function (event) {
            if (!dragging) {
                return;
            }

            const stageRectangle =
                stage.getBoundingClientRect();

            let displayedX =
                event.clientX -
                stageRectangle.left;

            let displayedY =
                event.clientY -
                stageRectangle.top;

            displayedX = Math.max(
                0,
                Math.min(
                    displayedX,
                    image.clientWidth
                )
            );

            displayedY = Math.max(
                0,
                Math.min(
                    displayedY,
                    image.clientHeight
                )
            );

            element.style.left =
                displayedX + 'px';

            element.style.top =
                displayedY + 'px';

            const scale =
                getScale();

            if (scale.x > 0) {
                field.xInput.value =
                    Math.round(
                        displayedX /
                        scale.x
                    );
            }

            if (scale.y > 0) {
                field.yInput.value =
                    Math.round(
                        displayedY /
                        scale.y
                    );
            }
        }
    );

    function stopDragging(event) {
        if (!dragging) {
            return;
        }

        dragging = false;

        element.classList.remove(
            'dragging'
        );

        if (
            event &&
            element.hasPointerCapture(
                event.pointerId
            )
        ) {
            element.releasePointerCapture(
                event.pointerId
            );
        }
    }

    element.addEventListener(
        'pointerup',
        stopDragging
    );

    element.addEventListener(
        'pointercancel',
        stopDragging
    );

    field.xInput.addEventListener(
        'input',
        function () {
            positionField(
                fieldName
            );
        }
    );

    field.yInput.addEventListener(
        'input',
        function () {
            positionField(
                fieldName
            );
        }
    );
}

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

function showMessage(
    type,
    text
) {
    if (!messageBox) {
        return;
    }

    messageBox.className =
        'editor-message ' +
        'editor-message-' +
        type;

    messageBox.textContent =
        text;
}

/*
|--------------------------------------------------------------------------
| Initialize Fields
|--------------------------------------------------------------------------
*/

Object.keys(fields).forEach(
    function (fieldName) {
        makeDraggable(
            fieldName
        );
    }
);

if (image) {
    image.addEventListener(
        'load',
        function () {
            positionAllFields();
        }
    );

    if (image.complete) {
        positionAllFields();
    }
}

window.addEventListener(
    'resize',
    function () {
        positionAllFields();
    }
);

/*
|--------------------------------------------------------------------------
| Save Positions
|--------------------------------------------------------------------------
*/

if (saveButton) {
    saveButton.addEventListener(
        'click',
        async function () {
            saveButton.disabled =
                true;

            saveButton.textContent =
                'Saving...';

            if (messageBox) {
                messageBox.className =
                    'editor-message';

                messageBox.textContent =
                    '';
            }

            const templateIdInput =
                document.getElementById(
                    'template_id'
                );

            if (!templateIdInput) {
                showMessage(
                    'error',
                    'Template ID field is missing.'
                );

                saveButton.disabled =
                    false;

                saveButton.textContent =
                    'Save Positions';

                return;
            }

            const formData =
                new FormData();

            formData.append(
                'template_id',
                templateIdInput.value
            );

            /*
            |--------------------------------------------------------------------------
            | Student Name
            |--------------------------------------------------------------------------
            */

            formData.append(
                'student_name_x',
                fields.student.xInput.value
            );

            formData.append(
                'student_name_y',
                fields.student.yInput.value
            );

            /*
            |--------------------------------------------------------------------------
            | Certificate ID
            |--------------------------------------------------------------------------
            */

            formData.append(
                'certificate_id_x',
                fields.certificate_id.xInput.value
            );

            formData.append(
                'certificate_id_y',
                fields.certificate_id.yInput.value
            );

            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            formData.append(
                'verification_link_x',
                fields.verification.xInput.value
            );

            formData.append(
                'verification_link_y',
                fields.verification.yInput.value
            );

            
          

            try {
                const response =
                    await fetch(
                        '<?= escape(BASE_URL) ?>/admin/save-template-position.php',
                        {
                            method:
                                'POST',

                            body:
                                formData,

                            credentials:
                                'same-origin'
                        }
                    );

                const result =
                    await response.json();

                if (
                    !response.ok ||
                    result.success !== true
                ) {
                    throw new Error(
                        result.message ||
                        'Positions could not be saved.'
                    );
                }

                showMessage(
                    'success',
                    result.message
                );

            } catch (error) {
                showMessage(
                    'error',
                    error.message ||
                    'Positions could not be saved.'
                );

            } finally {
                saveButton.disabled =
                    false;

                saveButton.textContent =
                    'Save Positions';
            }
        }
    );
}

</script>

</body>
</html>
