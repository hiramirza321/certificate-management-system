<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$search = trim($_GET['search'] ?? '');
$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Certificate Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $certificateId = trim($_POST['certificate_id'] ?? '');

    if ($certificateId === '') {
        $error = 'Certificate ID is required.';
    } elseif ($action === 'delete') {
        try {
            $deleteStatement = $pdo->prepare(
                'DELETE FROM certificates
                 WHERE certificate_id = :certificate_id'
            );

            $deleteStatement->execute([
                'certificate_id' => $certificateId,
            ]);

            if ($deleteStatement->rowCount() > 0) {
                $message = 'Certificate deleted successfully.';
            } else {
                $error = 'Certificate not found.';
            }
        } catch (PDOException $exception) {
            $error = 'Certificate could not be deleted.';
        }
    } elseif ($action === 'change_status') {
        $newStatus = $_POST['new_status'] ?? '';

        if (!in_array($newStatus, ['active', 'revoked'], true)) {
            $error = 'Invalid certificate status.';
        } else {
            try {
                $statusStatement = $pdo->prepare(
                    'UPDATE certificates
                     SET status = :status
                     WHERE certificate_id = :certificate_id'
                );

                $statusStatement->execute([
                    'status' => $newStatus,
                    'certificate_id' => $certificateId,
                ]);

                if ($statusStatement->rowCount() > 0) {
                    $message = 'Certificate status updated successfully.';
                } else {
                    $error = 'Certificate status could not be updated.';
                }
            } catch (PDOException $exception) {
                $error = 'Certificate status could not be updated.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalCertificates = (int) $pdo
    ->query('SELECT COUNT(*) FROM certificates')
    ->fetchColumn();

$activeCertificates = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM certificates
         WHERE status = 'active'"
    )
    ->fetchColumn();

$revokedCertificates = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM certificates
         WHERE status = 'revoked'"
    )
    ->fetchColumn();

$todayCertificates = (int) $pdo
    ->query(
        'SELECT COUNT(*)
         FROM certificates
         WHERE DATE(created_at) = CURDATE()'
    )
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| Certificates List
|--------------------------------------------------------------------------
*/

if ($search !== '') {
    $statement = $pdo->prepare(
        'SELECT
            id,
            certificate_id,
            student_name,
            field_name,
            issue_date,
            status,
            created_at
         FROM certificates
         WHERE certificate_id LIKE ?
            OR student_name LIKE ?
            OR field_name LIKE ?
         ORDER BY id DESC'
    );

    $searchValue = '%' . $search . '%';

    $statement->execute([
        $searchValue,
        $searchValue,
        $searchValue,
    ]);
} else {
    $statement = $pdo->query(
        'SELECT
            id,
            certificate_id,
            student_name,
            field_name,
            issue_date,
            status,
            created_at
         FROM certificates
         ORDER BY id DESC'
    );
}

$certificates = $statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Shared Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';

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
                <h1>Dashboard</h1>

                <p>
                    Certificate management overview
                </p>
            </div>

        </div>

        <div class="admin-topbar-date">
            <?= date('d F Y') ?>
        </div>

    </header>

    <div class="admin-content">

        <section class="admin-welcome">

            <h2>
                Welcome,
                <?= escape(
                    ucfirst(
                        $_SESSION['admin_username'] ?? 'Admin'
                    )
                ) ?>
            </h2>

            <p>
                Certificates generate, search and manage karein.
            </p>

        </section>

        <?php if ($message !== ''): ?>

            <div class="alert alert-success">
                <?= escape($message) ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="alert alert-error">
                <?= escape($error) ?>
            </div>

        <?php endif; ?>

        <section class="dashboard-stats">

            <div class="stat-card stat-total">

                <div class="stat-label">
                    Total Certificates
                </div>

                <div class="stat-value">
                    <?= $totalCertificates ?>
                </div>

            </div>

            <div class="stat-card stat-active">

                <div class="stat-label">
                    Active Certificates
                </div>

                <div class="stat-value">
                    <?= $activeCertificates ?>
                </div>

            </div>

            <div class="stat-card stat-revoked">

                <div class="stat-label">
                    Revoked Certificates
                </div>

                <div class="stat-value">
                    <?= $revokedCertificates ?>
                </div>

            </div>

            <div class="stat-card stat-today">

                <div class="stat-label">
                    Generated Today
                </div>

                <div class="stat-value">
                    <?= $todayCertificates ?>
                </div>

            </div>

        </section>

        <section
            class="dashboard-section"
            id="certificates"
        >

            <div class="dashboard-section-header">

                <div>
                    <h2>Certificates</h2>

                    <p>
                        Search and manage certificate records.
                    </p>
                </div>

                <a
                    class="button button-primary"
                    href="<?= escape(BASE_URL) ?>/index.php"
                >
                    Generate Certificate
                </a>

            </div>

            <form
                class="search-form"
                method="GET"
                action=""
            >

                <input
                    class="form-control"
                    type="search"
                    name="search"
                    placeholder="Search by student name, certificate ID or course"
                    value="<?= escape($search) ?>"
                >

                <button
                    class="button button-primary"
                    type="submit"
                >
                    Search
                </button>

                <?php if ($search !== ''): ?>

                    <a
                        class="button button-secondary"
                        href="<?= escape(BASE_URL) ?>/admin/index.php"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </form>

            <div class="table-card">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Certificate ID</th>
                            <th>Student Name</th>
                            <th>Course / Field</th>
                            <th>Issue Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($certificates)): ?>

                            <tr>
                                <td colspan="7">
                                    No certificates found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($certificates as $index => $certificate): ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>

                                    <td>
                                        <?= escape($certificate['certificate_id']) ?>
                                    </td>

                                    <td>
                                        <?= escape($certificate['student_name']) ?>
                                    </td>

                                    <td>
                                        <?= escape($certificate['field_name']) ?>
                                    </td>

                                    <td>
                                        <?= escape(
                                            date(
                                                'd M Y',
                                                strtotime($certificate['issue_date'])
                                            )
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if ($certificate['status'] === 'active'): ?>

                                            <span class="status status-active">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="status status-revoked">
                                                Revoked
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="action-links">

                                            <a
                                                href="<?= escape(BASE_URL) ?>/certificate.php?id=<?= urlencode($certificate['certificate_id']) ?>"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                View
                                            </a>

                                            <a
                                                href="<?= escape(BASE_URL) ?>/verify.php?id=<?= urlencode($certificate['certificate_id']) ?>"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                Verify
                                            </a>

                                            <form
                                                method="POST"
                                                action=""
                                                style="display: inline;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="change_status"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="certificate_id"
                                                    value="<?= escape($certificate['certificate_id']) ?>"
                                                >

                                                <?php if ($certificate['status'] === 'active'): ?>

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="revoked"
                                                    >

                                                    <button
                                                        type="submit"
                                                        style="
                                                            border: 0;
                                                            padding: 0;
                                                            color: #c62828;
                                                            background: none;
                                                            font-weight: bold;
                                                            cursor: pointer;
                                                        "
                                                        onclick="return confirm('Is certificate ko revoke karna hai?');"
                                                    >
                                                        Revoke
                                                    </button>

                                                <?php else: ?>

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="active"
                                                    >

                                                    <button
                                                        type="submit"
                                                        style="
                                                            border: 0;
                                                            padding: 0;
                                                            color: #16803c;
                                                            background: none;
                                                            font-weight: bold;
                                                            cursor: pointer;
                                                        "
                                                        onclick="return confirm('Is certificate ko active karna hai?');"
                                                    >
                                                        Activate
                                                    </button>

                                                <?php endif; ?>

                                            </form>

                                            <form
                                                method="POST"
                                                action=""
                                                style="display: inline;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="certificate_id"
                                                    value="<?= escape($certificate['certificate_id']) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    style="
                                                        border: 0;
                                                        padding: 0;
                                                        color: #c62828;
                                                        background: none;
                                                        font-weight: bold;
                                                        cursor: pointer;
                                                    "
                                                    onclick="return confirm('Kya aap is certificate ko permanently delete karna chahte hain?');"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>