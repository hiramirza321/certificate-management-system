<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| Admin-only access
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['admin_logged_in']) ||
    $_SESSION['admin_logged_in'] !== true
) {
    header(
        'Location: ' . BASE_URL . '/admin/login.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Form defaults
|--------------------------------------------------------------------------
*/

$errors = [];
$studentName = '';
$fieldName = '';
$templateName = 'classic';
$issueDate = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| Form submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = trim(
        $_POST['student_name'] ?? ''
    );

    $fieldName = trim(
        $_POST['field_name'] ?? ''
    );

    $templateName = trim(
        $_POST['template_name'] ?? 'classic'
    );

    $issueDate = trim(
        $_POST['issue_date'] ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | Student name validation
    |--------------------------------------------------------------------------
    */

    if ($studentName === '') {
        $errors[] = 'Student name is required.';
    } elseif (mb_strlen($studentName) > 150) {
        $errors[] =
            'Student name must not exceed 150 characters.';
    }

    /*
    |--------------------------------------------------------------------------
    | Course / field validation
    |--------------------------------------------------------------------------
    */

    if ($fieldName === '') {
        $errors[] = 'Course or field name is required.';
    } elseif (mb_strlen($fieldName) > 150) {
        $errors[] =
            'Course or field name must not exceed 150 characters.';
    }

    /*
    |--------------------------------------------------------------------------
    | Template validation
    |--------------------------------------------------------------------------
    */

    $allowedTemplates = [
        'classic',
        'modern',
        'gold',
    ];

    if (
        !in_array(
            $templateName,
            $allowedTemplates,
            true
        )
    ) {
        $errors[] =
            'Please select a valid certificate template.';
    }

    /*
    |--------------------------------------------------------------------------
    | Date validation
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
        $errors[] = 'Please select a valid issue date.';
    }

    /*
    |--------------------------------------------------------------------------
    | Save certificate
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Temporary unique certificate ID
            |--------------------------------------------------------------------------
            */

            $temporaryCertificateId =
                'TEMP-' . bin2hex(random_bytes(12));

            $insertStatement = $pdo->prepare(
                'INSERT INTO certificates
                (
                    certificate_id,
                    student_name,
                    field_name,
                    template_name,
                    issue_date,
                    status
                )
                VALUES
                (
                    :certificate_id,
                    :student_name,
                    :field_name,
                    :template_name,
                    :issue_date,
                    :status
                )'
            );

            $insertStatement->execute([
                'certificate_id' =>
                    $temporaryCertificateId,

                'student_name' =>
                    $studentName,

                'field_name' =>
                    $fieldName,

                'template_name' =>
                    $templateName,

                'issue_date' =>
                    $issueDate,

                'status' =>
                    'active',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Professional serial ID
            |--------------------------------------------------------------------------
            */

            $databaseId = (int) $pdo->lastInsertId();

            $certificateId = sprintf(
                'JUW-%s-%06d',
                date(
                    'Y',
                    strtotime($issueDate)
                ),
                $databaseId
            );

            /*
            |--------------------------------------------------------------------------
            | Replace temporary ID
            |--------------------------------------------------------------------------
            */

            $updateStatement = $pdo->prepare(
                'UPDATE certificates
                 SET certificate_id = :certificate_id
                 WHERE id = :id'
            );

            $updateStatement->execute([
                'certificate_id' =>
                    $certificateId,

                'id' =>
                    $databaseId,
            ]);

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | Open generated certificate
            |--------------------------------------------------------------------------
            */

            header(
                'Location: certificate.php?id=' .
                urlencode($certificateId)
            );

            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Certificate could not be saved. Please try again.';
        }
    }
}
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
        Certificate Generator |
        <?= escape(ORGANIZATION_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>

<header class="site-header">

    <div class="page-wrapper header-inner">

        <a
            class="brand"
            href="<?= escape(BASE_URL) ?>/index.php"
        >

            <div class="brand-logo">
                DS
            </div>

            <div class="brand-text">

                <strong>
                    <?= escape(ORGANIZATION_NAME) ?>
                </strong>

                <span>
                    <?= escape(ORGANIZATION_TAGLINE) ?>
                </span>

            </div>

        </a>

        <nav class="navigation">

            <a href="<?= escape(BASE_URL) ?>/index.php">
                Generate
            </a>

            <a href="<?= escape(BASE_URL) ?>/verify.php">
                Verify
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/index.php">
                Admin
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/settings.php">
                Settings
            </a>

            <a href="<?= escape(BASE_URL) ?>/admin/logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>

<main class="main-content">

    <div class="page-wrapper">

        <section class="hero">

            <span class="hero-badge">
                Certificate Management System
            </span>

            <h1>
                Generate Professional Certificates
            </h1>

            <p>
                Enter the student's name, course or field,
                template and issue date. A unique certificate
                ID will be generated automatically.
            </p>

        </section>

        <section class="card">

            <div class="card-header">

                <h2>
                    Create New Certificate
                </h2>

                <p>
                    Enter the certificate details below.
                </p>

            </div>

            <?php if (!empty($errors)): ?>

                <div class="alert alert-error">

                    <?php foreach ($errors as $error): ?>

                        <div>
                            <?= escape($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <form
                method="POST"
                action=""
            >

                <div class="form-group">

                    <label
                        class="form-label"
                        for="student_name"
                    >
                        Student Name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="student_name"
                        name="student_name"
                        maxlength="150"
                        placeholder="Example: Ayesha Khan"
                        value="<?= escape($studentName) ?>"
                        required
                    >

                    <small class="form-help">
                        Enter the student's full name.
                    </small>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="field_name"
                    >
                        Course / Field Name
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="field_name"
                        name="field_name"
                        maxlength="150"
                        placeholder="Example: Web Development"
                        value="<?= escape($fieldName) ?>"
                        required
                    >

                    <small class="form-help">
                        Enter the course, training, or field name.
                    </small>

                </div>

                <div class="form-group">

                    <label
                        class="form-label"
                        for="template_name"
                    >
                        Certificate Template
                    </label>

                    <select
                        class="form-control"
                        id="template_name"
                        name="template_name"
                        required
                    >

                        <option
                            value="classic"
                            <?= $templateName === 'classic'
                                ? 'selected'
                                : '' ?>
                        >
                            Classic
                        </option>

                        <option
                            value="modern"
                            <?= $templateName === 'modern'
                                ? 'selected'
                                : '' ?>
                        >
                            Modern
                        </option>

                        <option
                            value="gold"
                            <?= $templateName === 'gold'
                                ? 'selected'
                                : '' ?>
                        >
                            Gold
                        </option>

                    </select>

                    <small class="form-help">
                        Select the certificate design.
                    </small>

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

                <button
                    class="button button-primary button-full"
                    type="submit"
                >
                    Generate Certificate
                </button>

            </form>

        </section>

    </div>

</main>

<footer class="site-footer">

    &copy;
    <?= date('Y') ?>
    <?= escape(ORGANIZATION_NAME) ?>.
    All rights reserved.

</footer>

</body>
</html>