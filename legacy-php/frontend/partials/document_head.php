<?php
declare(strict_types=1);
$title = $pageTitle ?? app_config('app_name');
?>
<!DOCTYPE html>
<html lang="en">
<script>
    (function () {
        var t = localStorage.getItem('theme');
        if (t === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f2eb">
    <meta name="description" content="Velvet Plate — fine dining, delivery, and an exceptional culinary experience.">
    <link rel="icon" type="image/png" href="<?= e(asset_url('assets/favicon.png')) ?>">
    <title><?= e(str_contains($title, 'Velvet Plate') ? $title : $title . ' | Velvet Plate') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/main.css?v=' . filemtime(__DIR__ . '/../css/main.css'))) ?>">
    <?php if (!empty($extraCss)): ?>
        <link rel="stylesheet" href="<?= e(asset_url('css/' . $extraCss)) ?>">
    <?php endif; ?>
</head>

<body class="app-body">
    <?php if (empty($minimalLayout)): ?>
        <a class="skip-link" href="#main-content">Skip to content</a>
    <?php endif; ?>