<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Template Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $templateId = filter_var(
        $_POST['template_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $templateId === false ||
        $templateId === null ||
        $templateId < 1
    ) {
        $error = 'Valid template ID is required.';
    } elseif ($action === 'change_status') {
        $newStatus = $_POST['new_status'] ?? '';

        if (!in_array($newStatus, ['active', 'inactive'], true)) {
            $error = 'Invalid template status.';
        } else {
            try {
                $statement = $pdo->prepare(
                    'UPDATE certificate_templates
                     SET status = :status
                     WHERE id = :id'
                );

                $statement->execute([
                    'status' => $newStatus,
                    'id' => $templateId,
                ]);

                if ($statement->rowCount() > 0) {
                    $message =
                        'Template status updated successfully.';
                } else {
                    $error =
                        'Template status could not be updated.';
                }
            } catch (PDOException $exception) {
                $error =
                    'Template status could not be updated.';
            }
        }
    } elseif ($action === 'delete') {
        try {
            $checkStatement = $pdo->prepare(
                'SELECT template_image_path
                 FROM certificate_templates
                 WHERE id = :id
                 LIMIT 1'
            );

            $checkStatement->execute([
                'id' => $templateId,
            ]);

            $template = $checkStatement->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$template) {
                $error = 'Template not found.';
            } else {
                $deleteStatement = $pdo->prepare(
                    'DELETE FROM certificate_templates
                     WHERE id = :id'
                );

                $deleteStatement->execute([
                    'id' => $templateId,
                ]);

                if ($deleteStatement->rowCount() > 0) {
                    $imagePath =
                        dirname(__DIR__) . '/' .
                        ltrim(
                            (string) $template[
                                'template_image_path'
                            ],
                            '/\\'
                        );

                    if (is_file($imagePath)) {
                        @unlink($imagePath);
                    }

                    $message =
                        'Template deleted successfully.';
                } else {
                    $error =
                        'Template could not be deleted.';
                }
            }
        } catch (PDOException $exception) {
            $error =
                'Template could not be deleted. It may already be in use.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Templates List
|--------------------------------------------------------------------------
*/

$statement = $pdo->query(
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
        font_size,
        font_color,
        status,
        created_at,
        updated_at
     FROM certificate_templates
     ORDER BY id DESC'
);

$templates = $statement->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Shared Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Certificate Templates';

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
                    Certificate Templates
                </h1>

                <p>
                    Upload, configure and manage certificate backgrounds.
                </p>

            </div>

        </div>

        <div class="admin-topbar-date">
            <?= date('d F Y') ?>
        </div>

    </header>

    <div class="admin-content">

        <section class="admin-welcome">

            <div class="dashboard-section-header">

                <div>

                    <h2>
                        Manage Templates
                    </h2>

                    <p>
                        Edit field positions, change status or remove templates.
                    </p>

                </div>

                <a
                    class="button button-primary"
                    href="<?= escape(BASE_URL) ?>/admin/template-upload.php"
                >
                    Upload New Template
                </a>

            </div>

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

        <section class="template-grid">

            <?php if (empty($templates)): ?>

                <div class="template-empty">

                    <h3>
                        No templates found
                    </h3>

                    <p>
                        Upload your first certificate template to begin.
                    </p>

                    <a
                        class="button button-primary"
                        href="<?= escape(BASE_URL) ?>/admin/template-upload.php"
                    >
                        Upload Template
                    </a>

                </div>

            <?php else: ?>

                <?php foreach ($templates as $template): ?>

                    <?php
                    $imageUrl =
                        rtrim(BASE_URL, '/') . '/' .
                        ltrim(
                            (string) $template[
                                'template_image_path'
                            ],
                            '/\\'
                        );
                    ?>

                    <article class="template-card">

                        <div class="template-preview">

                            <img
                                src="<?= escape($imageUrl) ?>"
                                alt="<?= escape(
                                    (string) $template['template_name']
                                ) ?>"
                            >

                            <span
                                class="status <?= $template['status'] === 'active'
                                    ? 'status-active'
                                    : 'status-revoked' ?>"
                            >
                                <?= $template['status'] === 'active'
                                    ? 'Active'
                                    : 'Inactive' ?>
                            </span>

                        </div>

                        <div class="template-card-body">

                            <h3>
                                <?= escape(
                                    (string) $template['template_name']
                                ) ?>
                            </h3>

                            <div class="template-meta">

                                <span>
                                    Student:
                                    X <?= (int) $template['student_name_x'] ?>,
                                    Y <?= (int) $template['student_name_y'] ?>
                                </span>

                                <span>
                                    Certificate ID:
                                    X <?= (int) $template['certificate_id_x'] ?>,
                                    Y <?= (int) $template['certificate_id_y'] ?>
                                </span>

                                <span>
                                    Verify Link:
                                    X <?= (int) $template['verification_link_x'] ?>,
                                    Y <?= (int) $template['verification_link_y'] ?>
                                </span>

                                <span>
                                    Uploaded:
                                    <?= escape(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                (string) $template['created_at']
                                            )
                                        )
                                    ) ?>
                                </span>

                            </div>

                            <div class="template-actions">

                                <a
                                    class="button button-secondary"
                                    href="<?= escape(BASE_URL) ?>/admin/template-editor.php?id=<?= (int) $template['id'] ?>"
                                >
                                    Edit Positions
                                </a>

                                <a
                                    class="button button-secondary"
                                    href="<?= escape($imageUrl) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Preview
                                </a>

                                <form
                                    method="POST"
                                    action=""
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="change_status"
                                    >

                                    <input
                                        type="hidden"
                                        name="template_id"
                                        value="<?= (int) $template['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="new_status"
                                        value="<?= $template['status'] === 'active'
                                            ? 'inactive'
                                            : 'active' ?>"
                                    >

                                    <button
                                        class="button button-secondary"
                                        type="submit"
                                    >
                                        <?= $template['status'] === 'active'
                                            ? 'Deactivate'
                                            : 'Activate' ?>
                                    </button>

                                </form>

                                <form
                                    method="POST"
                                    action=""
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="template_id"
                                        value="<?= (int) $template['id'] ?>"
                                    >

                                    <button
                                        class="button button-danger"
                                        type="submit"
                                        onclick="return confirm(
                                            'Kya aap is template ko permanently delete karna chahte hain?'
                                        );"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>

</main>

<style>
    .template-grid {
        display: grid;
        grid-template-columns:
            repeat(auto-fit, minmax(320px, 1fr));
        gap: 22px;
    }

    .template-card {
        overflow: hidden;
        border: 1px solid #e0e6ec;
        border-radius: 16px;
        background: #ffffff;
        box-shadow:
            0 14px 30px rgba(15, 35, 55, 0.08);
    }

    .template-preview {
        position: relative;
        aspect-ratio: 16 / 11;
        overflow: hidden;
        background: #eef2f5;
    }

    .template-preview img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .template-preview .status {
        position: absolute;
        top: 14px;
        right: 14px;
    }

    .template-card-body {
        padding: 20px;
    }

    .template-card-body h3 {
        margin: 0 0 14px;
        color: #17324d;
        font-size: 20px;
    }

    .template-meta {
        display: grid;
        gap: 7px;
        margin-bottom: 18px;
        color: #667085;
        font-size: 12px;
        line-height: 1.5;
    }

    .template-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .template-actions form {
        margin: 0;
    }

    .template-actions .button {
        min-height: 40px;
        padding: 8px 13px;
        font-size: 12px;
    }

    .template-empty {
        grid-column: 1 / -1;
        padding: 50px 25px;
        border-radius: 16px;
        background: #ffffff;
        box-shadow:
            0 14px 30px rgba(15, 35, 55, 0.08);
        text-align: center;
    }

    .template-empty h3 {
        margin: 0 0 8px;
        color: #17324d;
    }

    .template-empty p {
        margin: 0 0 20px;
        color: #667085;
    }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>