<?php
$pageTitle = 'Əlaqə';
require_once 'config/config.php';
require_once 'includes/header.php';

$db = Database::getInstance();

// Get contact info from settings
$settings = [];
$settingsData = $db->select("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('phone', 'email', 'address', 'whatsapp', 'instagram', 'facebook')");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Handle contact form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Zəhmət olmasa bütün sahələri doldurun';
    } else {
        // Save to database
        $db->insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $success = 'Mesajınız uğurla göndərildi. Tezliklə sizinlə əlaqə saxlanılacaq.';
        
        // Send email notification to admin
        // mail($settings['email'], $subject, $message, "From: $email");
    }
}
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Bizimlə Əlaqə</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Əlaqə</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Contact Info -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title gradient-text">Əlaqə Məlumatları</h4>
                        
                        <div class="contact-info-item">
                            <i class="bi bi-geo-alt text-primary"></i>
                            <div>
                                <h6>Ünvan</h6>
                                <p><?= htmlspecialchars($settings['address'] ?? 'Bakı, Azərbaycan') ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="bi bi-telephone text-primary"></i>
                            <div>
                                <h6>Telefon</h6>
                                <p><a href="tel:<?= htmlspecialchars($settings['phone'] ?? '+994123456789') ?>">
                                    <?= htmlspecialchars($settings['phone'] ?? '+994 12 345 67 89') ?>
                                </a></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="bi bi-whatsapp text-primary"></i>
                            <div>
                                <h6>WhatsApp</h6>
                                <p><a href="https://wa.me/<?= str_replace(['+', ' '], '', $settings['whatsapp'] ?? '') ?>">
                                    <?= htmlspecialchars($settings['whatsapp'] ?? '+994 50 123 45 67') ?>
                                </a></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="bi bi-envelope text-primary"></i>
                            <div>
                                <h6>E-mail</h6>
                                <p><a href="mailto:<?= htmlspecialchars($settings['email'] ?? 'info@alumpro.az') ?>">
                                    <?= htmlspecialchars($settings['email'] ?? 'info@alumpro.az') ?>
                                </a></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>İş Saatları</h5>
                        <ul class="list-unstyled">
                            <li><strong>Bazar ertəsi - Cümə:</strong> 09:00 - 18:00</li>
                            <li><strong>Şənbə:</strong> 10:00 - 16:00</li>
                            <li><strong>Bazar:</strong> İstirahət</li>
                        </ul>
                        
                        <hr>
                        
                        <h5>Sosial Şəbəkələr</h5>
                        <div class="social-links">
                            <a href="<?= htmlspecialchars($settings['facebook'] ?? '#') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="<?= htmlspecialchars($settings['instagram'] ?? '#') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="https://wa.me/<?= str_replace(['+', ' '], '', $settings['whatsapp'] ?? '') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title gradient-text">Bizə Yazın</h4>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Ad və Soyad *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">E-poçt *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+994 XX XXX XX XX">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Mövzu</label>
                                    <select class="form-select" id="subject" name="subject">
                                        <option value="Ümumi sual">Ümumi sual</option>
                                        <option value="Qiymət sorğusu">Qiymət sorğusu</option>
                                        <option value="Sifariş">Sifariş</option>
                                        <option value="Şikayət">Şikayət</option>
                                        <option value="Təklif">Təklif</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Mesaj *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Göndər
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Map -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d194472.6744309969!2d49.71487285!3d40.3947365!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40307d6bd6211cf9%3A0x343f6b5e7ae56c6b!2sBaku%2C%20Azerbaijan!5e0!3m2!1sen!2s!4v1702816410287!5m2!1sen!2s" 
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.contact-info-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.contact-info-item i {
    font-size: 24px;
    width: 40px;
}

.contact-info-item h6 {
    margin-bottom: 5px;
    font-weight: 600;
}

.contact-info-item p {
    margin: 0;
}

.social-links {
    display: flex;
    gap: 10px;
}
</style>

<?php require_once 'includes/footer.php'; ?>