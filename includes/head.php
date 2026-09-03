<?php
/**
 * FoodBridge — head.php
 * Shared <head> section. Set $page_title before including.
 * Loads Google Fonts, Bootstrap, Lucide icons, and all FoodBridge CSS.
 */
$page_title = $page_title ?? 'FoodBridge';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — FoodBridge</title>

    <!-- Fonts: Poppins (headings) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 (grid + responsive utilities) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FoodBridge Design System (order matters) -->
    <link rel="stylesheet" href="<?= url('assets/css/theme.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/glass.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/neumorphism.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/animations.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/utilities.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/responsive.css') ?>">
</head>