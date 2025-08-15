<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

$auth = new Auth();
$db = Database::getInstance();
$currentUser = $auth->getCurrentUser();

// Get settings
$siteName = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")['setting_value'];
$logoPath = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'logo_path'")['setting_value'];
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= $siteName ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/img/favicon.ico" type="image/x-icon">
    <!-- Web App Manifest -->
    <link rel="manifest" href="<?= SITE_URL ?>/manifest.json">
    <?php if (isset($extraHeadContent)) echo $extraHeadContent; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary fixed-top">
            <div class="container">
                <a class="navbar-brand" href="<?= SITE_URL ?>">
                    <img src="<?= SITE_URL ?>/<?= $logoPath ?>" alt="<?= $siteName ?>" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>"><i class="bi bi-house-door"></i> Ana Səhifə</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/about.php"><i class="bi bi-info-circle"></i> Haqqımızda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/products.php"><i class="bi bi-box-seam"></i> Məhsullar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/services.php"><i class="bi bi-gear"></i> Xidmətlər</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/gallery.php"><i class="bi bi-images"></i> Qalereya</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/news.php"><i class="bi bi-newspaper"></i> Xəbərlər</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/contact.php"><i class="bi bi-envelope"></i> Əlaqə</a>
                        </li>
                    </ul>
                    
                    <div class="d-flex align-items-center">
                        <?php if ($auth->isLoggedIn()): ?>
                            <?php
                            // Get unread notifications count
                            $notificationCount = $db->selectOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0", ['user_id' => $currentUser['id']])['count'];
                            ?>
                            <div class="dropdown me-3">
                                <a class="btn btn-light position-relative" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell"></i>
                                    <?php if ($notificationCount > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $notificationCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                                    <?php
                                    $notifications = $db->select("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10", ['user_id' => $currentUser['id']]);
                                    if (count($notifications) > 0):
                                        foreach ($notifications as $notification):
                                    ?>
                                    <li>
                                        <a class="dropdown-item <?= $notification['is_read'] ? '' : 'fw-bold' ?>" href="<?= SITE_URL ?>/notification.php?id=<?= $notification['id'] ?>">
                                            <div class="small text-muted"><?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?></div>
                                            <?= $notification['title'] ?>
                                        </a>
                                    </li>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                    <li><a class="dropdown-item" href="#">Bildiriş yoxdur</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center" href="<?= SITE_URL ?>/notifications.php">Bütün bildirişlər</a></li>
                                </ul>
                            </div>
                            
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php if (!empty($currentUser['profile_image'])): ?>
                                        <img src="<?= SITE_URL ?>/uploads/profiles/<?= $currentUser['profile_image'] ?>" alt="Profile" class="rounded-circle" width="32" height="32">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-4"></i>
                                    <?php endif; ?>
                                    <span class="ms-2 d-none d-lg-inline-block"><?= $currentUser['full_name'] ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/admin/index.php"><i class="bi bi-speedometer2"></i> Admin Panel</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php elseif ($auth->hasRole('sales')): ?>
                                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/sales/index.php"><i class="bi bi-shop"></i> Satış Panel</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php elseif ($auth->hasRole('customer')): ?>
                                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/customer/index.php"><i class="bi bi-person"></i> Profil</a></li>
                                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/customer/orders.php"><i class="bi bi-bag"></i> Sifarişlərim</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-gear"></i> Profil Ayarları</a></li>
                                    <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Çıxış</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/login.php" class="btn btn-light me-2"><i class="bi bi-box-arrow-in-right"></i> Giriş</a>
                            <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-light"><i class="bi bi-person-plus"></i> Qeydiyyat</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">