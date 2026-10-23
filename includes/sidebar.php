<?php

declare(strict_types=1);

$currentPage = basename($_SERVER['PHP_SELF']);

$currentDirectory = basename(
    dirname($_SERVER['PHP_SELF'])
);

function sidebarActive(
    string $page,
    string $directory = ''
): string {
    global $currentPage, $currentDirectory;

    if (
        $currentPage === $page &&
        (
            $directory === '' ||
            $currentDirectory === $directory
        )
    ) {
        return 'sidebar-link-active';
    }

    return '';
}
?>

<aside class="admin-sidebar">

    <div class="sidebar-brand">

        <div class="sidebar-logo">
            JUW
        </div>

        <div class="sidebar-brand-text">

            <strong>
                <?= escape(ORGANIZATION_NAME) ?>
            </strong>

            <span>
                Certificate System
            </span>

        </div>

    </div>

    <nav class="sidebar-navigation">

    <a
        class="sidebar-link <?= sidebarActive('index.php', 'admin') ?>"
        href="<?= escape(BASE_URL) ?>/admin/index.php"
    >
        <span class="sidebar-icon">⌂</span>
        <span>Dashboard</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('index.php', 'certificate_generator') ?>"
        href="<?= escape(BASE_URL) ?>/index.php"
    >
        <span class="sidebar-icon">＋</span>
        <span>Generate Certificate</span>
    </a>

    <a
        class="sidebar-link"
        href="<?= escape(BASE_URL) ?>/admin/index.php#certificates"
    >
        <span class="sidebar-icon">▤</span>
        <span>Certificates</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('template-upload.php', 'admin') ?>"
        href="<?= escape(BASE_URL) ?>/admin/template-upload.php"
    >
        <span class="sidebar-icon">↑</span>
        <span>Upload Template</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('templates.php', 'admin') ?>"
        href="<?= escape(BASE_URL) ?>/admin/templates.php"
    >
        <span class="sidebar-icon">▣</span>
        <span>Manage Templates</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('bulk-generate.php', 'admin') ?>"
        href="<?= escape(BASE_URL) ?>/admin/bulk-generate.php"
    >
        <span class="sidebar-icon">⇧</span>
        <span>Bulk Generate</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('verify.php') ?>"
        href="<?= escape(BASE_URL) ?>/verify.php"
        target="_blank"
        rel="noopener"
    >
        <span class="sidebar-icon">✓</span>
        <span>Verify Certificate</span>
    </a>

    <a
        class="sidebar-link <?= sidebarActive('settings.php', 'admin') ?>"
        href="<?= escape(BASE_URL) ?>/admin/settings.php"
    >
        <span class="sidebar-icon">⚙</span>
        <span>Settings</span>
    </a>

</nav>

    <div class="sidebar-footer">

        <div class="sidebar-user">

            <div class="sidebar-user-avatar">
                <?= escape(
                    strtoupper(
                        substr(
                            $_SESSION['admin_username'] ?? 'A',
                            0,
                            1
                        )
                    )
                ) ?>
            </div>

            <div class="sidebar-user-details">

                <strong>
                    <?= escape(
                        $_SESSION['admin_username'] ?? 'Admin'
                    ) ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

        <a
            class="sidebar-logout"
            href="<?= escape(BASE_URL) ?>/admin/logout.php"
        >
            Logout
        </a>

    </div>

</aside>