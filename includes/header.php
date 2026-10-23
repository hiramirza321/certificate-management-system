<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Admin Dashboard';
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
        <?= escape($pageTitle) ?> |
        <?= escape(ORGANIZATION_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= escape(BASE_URL) ?>/assets/style.css"
    >
</head>

<body class="admin-body">

<div class="admin-layout">