<?php
// Landing Page - Alumpro.Az
$pageTitle = 'Ana Səhifə';
require_once 'config/config.php';
require_once 'includes/header.php';

// Redirect to home.php for now since home.php contains the landing page content
// In a production environment, you might want to move content here or have separate landing logic
header('Location: home.php');
exit;
?>