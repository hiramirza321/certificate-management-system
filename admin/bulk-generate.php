<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Initial Variables
|--------------------------------------------------------------------------
*/

$errors = [];
$successMessage = '';
$students = [];

$templateId = '';
$fieldName = '';
$issueDate = date('Y-m-d');

$selectedTemplate = null;

/*
|--------------------------------------------------------------------------
| Flash Results
|--------------------------------------------------------------------------
*/

$generatedCertificates =
    $_SESSION['bulk_generated_certificates'] ?? [];

unset($_SESSION['bulk_generated_certificates']);

$createdCount = filter_input(
    INPUT_GET,
    'created',
    FILTER_VALIDATE_INT
);

if (
    $createdCount !== false &&
    $createdCount !== null &&
    $createdCount > 0
) {
    $successMessage =
        $createdCount .
        ' certificates generated successfully.';
}

/*
|--------------------------------------------------------------------------
| Active Templates
|--------------------------------------------------------------------------
*/

$templateStatement = $pdo->query(
    "SELECT
        id,
        template_name,
        certificate_prefix
     FROM certificate_templates
     WHERE status = 'active'
     ORDER BY template_name ASC"
);

$templates = $templateStatement->fetchAll(
    PDO::FETCH_ASSOC
);

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Submitted Values
    |--------------------------------------------------------------------------
    */

    $templateId = trim(
        (string) ($_POST['template_id'] ?? '')
    );

    $fieldName = trim(
        (string) ($_POST['field_name'] ?? '')
    );

    $issueDate = trim(
        (string) ($_POST['issue_date'] ?? '')
    );

    /*
    |--------------------------------------------------------------------------
    | Template Validation
    |--------------------------------------------------------------------------
    */

    if (
        $templateId === '' ||
        !ctype_digit($templateId)
    ) {
        $errors[] =
            'Please select a valid template.';
    } else {
        $templateCheckStatement = $pdo->prepare(
            "SELECT
                id,
                template_name,
                certificate_prefix
             FROM certificate_templates
             WHERE id = :id
               AND status = 'active'
             LIMIT 1"
        );

        $templateCheckStatement->execute([
            'id' => (int) $templateId,
        ]);

        $selectedTemplate =
            $templateCheckStatement->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$selectedTemplate) {
            $errors[] =
                'Selected template is not available.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Course / Event Validation
    |--------------------------------------------------------------------------
    */

    if ($fieldName === '') {
        $errors[] =
            'Course or event name is required.';
    } elseif (mb_strlen($fieldName) > 150) {
        $errors[] =
            'Course or event name must not exceed 150 characters.';
    }

    /*
    |--------------------------------------------------------------------------
    | Date Validation
    |--------------------------------------------------------------------------
    */

    $dateObject = DateTime::createFromFormat(
        'Y-m-d',
        $issueDate
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $issueDate
    ) {
        $errors[] =
            'Please select a valid issue date.';
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Upload Validation
    |--------------------------------------------------------------------------
    */

    $uploadedFile =
        $_FILES['csv_file'] ?? null;

    if (
        !$uploadedFile ||
        !is_array($uploadedFile) ||
        !isset($uploadedFile['error'])
    ) {
        $errors[] =
            'Please select a CSV file.';
    } elseif (
        (int) $uploadedFile['error'] !== UPLOAD_ERR_OK
    ) {
        $errors[] =
            'CSV file upload failed.';
    }

    if (
        empty($errors) &&
        is_array($uploadedFile)
    ) {
        $extension = strtolower(
            pathinfo(
                (string) $uploadedFile['name'],
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {
            $errors[] =
                'Only CSV files are allowed.';
        }

        if (
            (int) $uploadedFile['size'] >
            (5 * 1024 * 1024)
        ) {
            $errors[] =
                'CSV file must not exceed 5 MB.';
        }

        if (
            !is_uploaded_file(
                (string) $uploadedFile['tmp_name']
            )
        ) {
            $errors[] =
                'Invalid CSV upload.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Read CSV
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        is_array($uploadedFile)
    ) {
        $handle = fopen(
            (string) $uploadedFile['tmp_name'],
            'r'
        );

        if ($handle === false) {
            $errors[] =
                'CSV file could not be opened.';
        } else {
            $header = fgetcsv($handle);

            if ($header === false) {
                $errors[] =
                    'CSV file is empty.';
            } else {

                /*
                |--------------------------------------------------------------------------
                | Normalize CSV Header
                |--------------------------------------------------------------------------
                */

                $header = array_map(
                    static function ($value): string {
                        $value = (string) $value;

                        /*
                         * Remove UTF-8 BOM if Excel added one.
                         */
                        $value = preg_replace(
                            '/^\xEF\xBB\xBF/',
                            '',
                            $value
                        ) ?? $value;

                        return strtolower(
                            trim($value)
                        );
                    },
                    $header
                );

                /*
                |--------------------------------------------------------------------------
                | Find student_name Column
                |--------------------------------------------------------------------------
                */

                $studentColumn = array_search(
                    'student_name',
                    $header,
                    true
                );

                if ($studentColumn === false) {
                    $errors[] =
                        'CSV must contain a student_name column.';
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Read Students
                    |--------------------------------------------------------------------------
                    */

                    while (
                        ($row = fgetcsv($handle)) !== false
                    ) {
                        if (
                            !isset($row[$studentColumn])
                        ) {
                            continue;
                        }

                        $studentName = trim(
                            (string) $row[$studentColumn]
                        );

                        if ($studentName === '') {
                            continue;
                        }

                        if (
                            mb_strlen($studentName) > 150
                        ) {
                            continue;
                        }

                        $students[] = $studentName;
                    }
                }
            }

            fclose($handle);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Imported Students
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        empty($students)
    ) {
        $errors[] =
            'No valid student names were found in the CSV file.';
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Certificate Records
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        !empty($students) &&
        is_array($selectedTemplate)
    ) {
        try {

            /*
            |--------------------------------------------------------------------------
            | Certificate Prefix
            |--------------------------------------------------------------------------
            */

            $certificatePrefix = strtoupper(
                trim(
                    (string) (
                        $selectedTemplate[
                            'certificate_prefix'
                        ] ?? ''
                    )
                )
            );

            /*
             * Only keep letters, numbers, underscore
             * and hyphen in the prefix.
             */
            $certificatePrefix = preg_replace(
                '/[^A-Z0-9_-]/',
                '',
                $certificatePrefix
            ) ?? '';

            if ($certificatePrefix === '') {
                $certificatePrefix = 'CERT';
            }

            /*
            |--------------------------------------------------------------------------
            | Certificate Year
            |--------------------------------------------------------------------------
            */

            $certificateYear = date(
                'Y',
                strtotime($issueDate)
            );

            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Prepare INSERT
            |--------------------------------------------------------------------------
            */

            $insertStatement = $pdo->prepare(
                'INSERT INTO certificates
                (
                    certificate_id,
                    student_name,
                    field_name,
                    template_name,
                    custom_template_id,
                    issue_date,
                    status
                )
                VALUES
                (
                    :certificate_id,
                    :student_name,
                    :field_name,
                    :template_name,
                    :custom_template_id,
                    :issue_date,
                    :status
                )'
            );

            /*
            |--------------------------------------------------------------------------
            | Prepare Final ID Update
            |--------------------------------------------------------------------------
            */

            $updateStatement = $pdo->prepare(
                'UPDATE certificates
                 SET certificate_id = :certificate_id
                 WHERE id = :id'
            );

            $generatedCertificates = [];

            /*
            |--------------------------------------------------------------------------
            | Generate Certificate For Every Student
            |--------------------------------------------------------------------------
            */

            foreach ($students as $studentName) {

                /*
                 * A temporary unique ID is used first because
                 * the final ID depends on MySQL auto_increment ID.
                 */
                $temporaryCertificateId =
                    'TEMP-' .
                    bin2hex(
                        random_bytes(16)
                    );

                /*
                |--------------------------------------------------------------------------
                | Insert Certificate
                |--------------------------------------------------------------------------
                */

                $insertStatement->execute([
                    'certificate_id' =>
                        $temporaryCertificateId,

                    'student_name' =>
                        $studentName,

                    'field_name' =>
                        $fieldName,

                    'template_name' =>
                        'custom',

                    'custom_template_id' =>
                        (int) $selectedTemplate['id'],

                    'issue_date' =>
                        $issueDate,

                    'status' =>
                        'active',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Get Auto Increment Database ID
                |--------------------------------------------------------------------------
                */

                $databaseId =
                    (int) $pdo->lastInsertId();

                if ($databaseId < 1) {
                    throw new RuntimeException(
                        'Could not obtain the certificate database ID.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Generate Final Certificate ID
                |--------------------------------------------------------------------------
                |
                | Examples:
                |
                | JTECH-2026-000051
                | IBRAS-2026-000052
                | CERT-2026-000053
                |
                |--------------------------------------------------------------------------
                */

                $certificateId = sprintf(
                    '%s-%s-%06d',
                    $certificatePrefix,
                    $certificateYear,
                    $databaseId
                );

                /*
                |--------------------------------------------------------------------------
                | Update Temporary ID With Final ID
                |--------------------------------------------------------------------------
                */

                $updateStatement->execute([
                    'certificate_id' =>
                        $certificateId,

                    'id' =>
                        $databaseId,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Store Result For Success Table
                |--------------------------------------------------------------------------
                */

                $generatedCertificates[] = [
                    'certificate_id' =>
                        $certificateId,

                    'student_name' =>
                        $studentName,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Commit All Certificates
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | Flash Generated Results
            |--------------------------------------------------------------------------
            */

            $_SESSION['bulk_generated_certificates'] =
                $generatedCertificates;

            /*
            |--------------------------------------------------------------------------
            | Redirect To Avoid Duplicate Form Submission
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                BASE_URL .
                '/admin/bulk-generate.php?created=' .
                count($generatedCertificates)
            );

            exit;

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Roll Back All Certificates If Anything Fails
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            /*
             * Development message.
             * Later production mein generic message use kar sakte hain.
             */
            $errors[] =
                'Bulk generation error: ' .
                $exception->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Shared Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Bulk Generate Certificates';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="admin-main">

    <header class="admin-topbar">

        <div class="admin-topbar-left">

            <button
                class="sidebar-toggle"
                type="button"
                data-sidebar-toggle
                aria-label="Open sidebar"
            >
                ☰
            </button>

            <div>

                <h1>
                    Bulk Generate Certificates
                </h1>

                <p>
                    Upload a CSV file and generate certificates
                    for multiple students.
                </p>

            </div>

        </div>

        <div class="admin-topbar-date">
            <?= date('d F Y') ?>
        </div>

    </header>

    <div class="admin-content">

        <section class="card">

            <div class="card-header">

                <h2>
                    Bulk Certificate Import
                </h2>

                <p>
                    Select a template, enter the event name and
                    upload a CSV containing student names.
                </p>

            </div>

            <?php if ($successMessage !== ''): ?>

                <div class="alert alert-success">
                    <?= escape($successMessage) ?>
                </div>

            <?php endif; ?>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-error">

                    <?php foreach ($errors as $error): ?>

                        <div>
                            <?= escape($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <?php if (!empty($generatedCertificates)): ?>

                <div
                    class="table-card"
                    style="margin-bottom: 24px;"
                >

                    <table class="data-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Certificate ID</th>
                                <th>Student Name</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $generatedCertificates
                                as $index => $generated
                            ): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <?= escape(
                                            (string) $generated[
                                                'certificate_id'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= escape(
                                            (string) $generated[
                                                'student_name'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>

                                        <a
                                            href="<?= escape(BASE_URL) ?>/certificate.php?id=<?= urlencode(
                                                (string) $generated[
                                                    'certificate_id'
                                                ]
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            View
                                        </a>

                                        &nbsp;|&nbsp;

                                        <a
                                            href="<?= escape(BASE_URL) ?>/verify.php?id=<?= urlencode(
                                                (string) $generated[
                                                    'certificate_id'
                                                ]
                                            ) ?>"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Verify
                                        </a>

                                        &nbsp;|&nbsp;

                                        <a
                                            href="<?= escape(BASE_URL) ?>/download.php?id=<?= urlencode(
                                                (string) $generated[
                                                    'certificate_id'
                                                ]
                                            ) ?>"
                                        >
                                            Download
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

            <form
                method="POST"
                enctype="multipart/form-data"
                action=""
            >

                <div class="form-group">

                    <label
                        class="form-label"
                        for="template_id"
                    >
                        Certificate Template
                    </label>

                    <select
                        class="form-control"
                        id="template_id"
                        name="template_id"
                        required
                    >

                        <option value="">
                            Select Template
                        </option>

                        <?php foreach ($templates as $template): ?>

                            <?php
                            $templatePrefix = strtoupper(
                                trim(
                                    (string) (
                                        $template[
                                            'certificate_prefix'
                                        ] ?? ''
                                    )
                                )
                            );
                            ?>

                            <option
                                value="<?= (int) $template['id'] ?>"
                                <?= $templateId ===
                                    (string) $template['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= escape(
                                    (string) $template[
                                        'template_name'
                                    ]
                                ) ?>

                                <?php if ($templatePrefix !== ''): ?>
                                    (<?= escape($templatePrefix) ?>)
                                <?php endif; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small class="form-help">
                        The selected template's certificate prefix
                        will be used automatically.
                    </small>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="field_name"
                    >
                        Course / Event Name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="field_name"
                        name="field_name"
                        maxlength="150"
                        placeholder="Example: Oral Presentation"
                        value="<?= escape($fieldName) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="issue_date"
                    >
                        Issue Date
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        id="issue_date"
                        name="issue_date"
                        value="<?= escape($issueDate) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="csv_file"
                    >
                        Students CSV File
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        id="csv_file"
                        name="csv_file"
                        accept=".csv,text/csv"
                        required
                    >

                    <small class="form-help">
                        Required column:
                        <strong>student_name</strong>
                    </small>

                </div>

                <div class="alert alert-success">

                    CSV example:

                    <pre style="margin: 10px 0 0;">student_name
Ayesha Khan
Sara Ali
Hina Ahmed</pre>

                </div>

                <button
                    class="button button-primary button-full"
                    type="submit"
                >
                    Generate Certificates
                </button>

            </form>

        </section>

    </div>

</main>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>