<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (
    empty($_SESSION['admin_logged_in']) ||
    $_SESSION['admin_logged_in'] !== true
) {
    header(
        'Location: ' . BASE_URL . '/admin/login.php'
    );

    exit;
}