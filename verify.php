<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$certificateId = trim($_GET['id'] ?? $_POST['certificate_id'] ?? '');
$certificate = null;
$searched = false;

if ($certificateId !== '') {
    $searched = true;

    $statement = $pdo->prepare(
        'SELECT
            certificate_id,
            student_name,
            field_name,
            issue_date,
            status,
            created_at
         FROM certificates
         WHERE certificate_id = :certificate_id
         LIMIT 1'
    );

    $statement->execute([
        'certificate_id' => $certificateId,
    ]);

    $certificate = $statement->fetch();
}

$formattedIssueDate = '';

if ($certificate) {
    $formattedIssueDate = date(
        'd F Y',
        strtotime($certificate['issue_date'])
    );
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
        Verify Certificate | <?= escape(ORGANIZATION_NAME) ?>
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

        </nav>

    </div>

</header>

<main class="main-content">

    <div class="page-wrapper">

        <?php if (!$searched): ?>

            <section class="hero">

                <span class="hero-badge">
                    Certificate Verification
                </span>

                <h1>
                    Verify a Certificate
                </h1>

                <p>
                    Enter the Certificate ID to verify the authenticity of the certificate.
                </p>

            </section>

            <section class="card">

                <div class="card-header">

                    <h2>
                        Enter Certificate ID
                    </h2>

                    <p>
                       Enter the complete Certificate ID shown on the certificate.
                    </p>

                </div>

                <form method="GET" action="">

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="certificate_id"
                        >
                            Certificate ID
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="certificate_id"
                            name="id"
                            placeholder="Example: CERT-2026-91734410"
                            required
                        >

                    </div>

                    <button
                        class="button button-primary button-full"
                        type="submit"
                    >
                        Verify Certificate
                    </button>

                </form>

            </section>

        <?php elseif ($certificate): ?>

            <section class="verification-card">

                <?php if ($certificate['status'] === 'active'): ?>

                    <div class="verification-icon">
                        ✓
                    </div>

                    <h1>
                        Certificate Verified
                    </h1>

                    <p>
                        This certificate is valid and exists in our records.
                    </p>

                <?php else: ?>

                    <div class="verification-icon invalid">
                        !
                    </div>

                    <h1>
                        Certificate Revoked
                    </h1>

                    <p>
                        This certificate exists but is no longer valid.
                    </p>

                <?php endif; ?>

                <div class="verification-details">

                    <div class="verification-row">

                        <strong>
                            Student Name
                        </strong>

                        <span>
                            <?= escape($certificate['student_name']) ?>
                        </span>

                    </div>

                    <div class="verification-row">

                        <strong>
                            Course / Field
                        </strong>

                        <span>
                            <?= escape($certificate['field_name']) ?>
                        </span>

                    </div>

                    <div class="verification-row">

                        <strong>
                            Certificate ID
                        </strong>

                        <span>
                            <?= escape($certificate['certificate_id']) ?>
                        </span>

                    </div>

                    <div class="verification-row">

                        <strong>
                            Issue Date
                        </strong>

                        <span>
                            <?= escape($formattedIssueDate) ?>
                        </span>

                    </div>

                    <div class="verification-row">

                        <strong>
                            Status
                        </strong>

                        <span>
                            <?= escape(ucfirst($certificate['status'])) ?>
                        </span>

                    </div>

                </div>

                <div
                    style="
                        margin-top: 25px;
                        display: flex;
                        justify-content: center;
                        gap: 12px;
                        flex-wrap: wrap;
                    "
                >

                    <a
                        class="button button-primary"
                        href="<?= escape(BASE_URL) ?>/certificate.php?id=<?= urlencode($certificate['certificate_id']) ?>"
                    >
                        View Certificate
                    </a>

                    <a
                        class="button button-secondary"
                        href="<?= escape(BASE_URL) ?>/verify.php"
                    >
                        Verify Another
                    </a>

                </div>

            </section>

        <?php else: ?>

            <section class="verification-card">

                <div class="verification-icon invalid">
                    ×
                </div>

                <h1>
                    Certificate Not Found
                </h1>

                <p>
                    No certificate record was found with this ID.
                </p>

                <div class="alert alert-error">

                    Entered Certificate ID:

                    <strong>
                        <?= escape($certificateId) ?>
                    </strong>

                </div>

                <a
                    class="button button-primary"
                    href="<?= escape(BASE_URL) ?>/verify.php"
                >
                    Try Again
                </a>

            </section>

        <?php endif; ?>

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