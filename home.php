<?php
$pageTitle = 'Ana Səhifə';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get latest products
$latestProducts = $db->select("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 4
");

// Get latest news
$latestNews = $db->select("
    SELECT *
    FROM news
    WHERE published = 1
    ORDER BY created_at DESC
    LIMIT 3
");

// Get statistics
$totalOrders = $db->selectOne("SELECT COUNT(*) as count FROM orders")['count'];
$totalCustomers = $db->selectOne("SELECT COUNT(*) as count FROM customers")['count'];
$totalProducts = $db->selectOne("SELECT COUNT(*) as count FROM products")['count'];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 slide-in-left">
                <h1 class="display-4 fw-bold mb-4">Alumpro.Az - Keyfiyyətli Alüminium Məhsulları</h1>
                <p class="fs-5 mb-4">Müxtəlif ölçülü, tipli və rəngli alüminium profillər və şüşəli mətbəx qapaqları, dolap qapıları, arakəsmə qapıları istehsalı və satışı</p>
                <div class="d-flex gap-3">
                    <a href="products.php" class="btn btn-light btn-lg">
                        <i class="bi bi-box-seam"></i> Məhsullar
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-telephone"></i> Bizimlə Əlaqə
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="assets/img/slide1.jpg" class="d-block w-100 rounded" alt="Alüminium Profillər">
                        </div>
                        <div class="carousel-item">
                            <img src="assets/img/slide2.jpg" class="d-block w-100 rounded" alt="Mətbəx Qapaqları">
                        </div>
                        <div class="carousel-item">
                            <img src="assets/img/slide3.jpg" class="d-block w-100 rounded" alt="Dolap Qapıları">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-light">
    <div class="container">
        <h2 class="text-center mb-5 gradient-text">Xidmətlərimiz</h2>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h4>Professional İstehsal</h4>
                    <p>Müasir avadanlıqlarla yüksək keyfiyyətli məhsul istehsalı</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h4>Çatdırılma Xidməti</h4>
                    <p>Bakı və ətraf rayonlara sürətli və təhlükəsiz çatdırılma</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h4>Professional Quraşdırma</h4>
                    <p>Təcrübəli ustalar tərəfindən keyfiyyətli quraşdırma xidməti</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h4>24/7 Dəstək</h4>
                    <p>Həftənin 7 günü müştəri dəstək xidməti</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 gradient-text">Son Məhsullar</h2>
        <div class="row g-4">
            <?php foreach ($latestProducts as $product): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card product-card">
                    <img src="assets/img/products/default.jpg" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($product['category_name']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= htmlspecialchars($product['color']) ?></span>
                            <span class="fw-bold"><?= number_format($product['sale_price'], 2) ?> ₼</span>
                        </div>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-eye"></i> Ətraflı
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary btn-lg">
                Bütün Məhsullar <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-gradient-primary text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="display-4 fw-bold"><?= $totalCustomers ?>+</div>
                <p class="fs-5">Məmnun Müştəri</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="display-4 fw-bold"><?= $totalOrders ?>+</div>
                <p class="fs-5">Tamamlanmış Sifariş</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="display-4 fw-bold"><?= $totalProducts ?>+</div>
                <p class="fs-5">Məhsul Çeşidi</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="display-4 fw-bold">10+</div>
                <p class="fs-5">İllik Təcrübə</p>
            </div>
        </div>
    </div>
</section>

<!-- Latest News -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5 gradient-text">Son Xəbərlər</h2>
        <div class="row g-4">
            <?php foreach ($latestNews as $news): ?>
            <div class="col-lg-4">
                <div class="card h-100">
                    <?php if ($news['image']): ?>
                    <img src="uploads/news/<?= htmlspecialchars($news['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($news['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($news['title']) ?></h5>
                        <p class="card-text"><?= substr(strip_tags($news['content']), 0, 150) ?>...</p>
                        <p class="text-muted small">
                            <i class="bi bi-calendar"></i> <?= date('d.m.Y', strtotime($news['created_at'])) ?>
                        </p>
                        <a href="news-detail.php?id=<?= $news['id'] ?>" class="btn btn-outline-primary">
                            Ətraflı <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 gradient-text">Partnyorlarımız</h2>
        <div class="row align-items-center">
            <div class="col-6 col-md-3 mb-4 text-center">
                <img src="assets/img/partners/partner1.png" alt="Partner 1" class="img-fluid" style="max-height: 80px;">
            </div>
            <div class="col-6 col-md-3 mb-4 text-center">
                <img src="assets/img/partners/partner2.png" alt="Partner 2" class="img-fluid" style="max-height: 80px;">
            </div>
            <div class="col-6 col-md-3 mb-4 text-center">
                <img src="assets/img/partners/partner3.png" alt="Partner 3" class="img-fluid" style="max-height: 80px;">
            </div>
            <div class="col-6 col-md-3 mb-4 text-center">
                <img src="assets/img/partners/partner4.png" alt="Partner 4" class="img-fluid" style="max-height: 80px;">
            </div>
        </div>
    </div>
</section>

<!-- PWA Install Prompt -->
<script>
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install prompt after 5 seconds
    setTimeout(() => {
        showInstallPrompt();
    }, 5000);
});

function showInstallPrompt() {
    if (deferredPrompt) {
        const modal = new bootstrap.Modal(document.getElementById('pwaModal'));
        modal.show();
    }
}

function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the install prompt');
            }
            deferredPrompt = null;
        });
    }
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    setTimeout(() => {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Notification permission granted');
                // Initialize OneSignal here
            }
        });
    }, 10000);
}
</script>

<!-- PWA Install Modal -->
<div class="modal fade" id="pwaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-phone"></i> Mobil Tətbiq Quraşdırın
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="assets/img/icon-192.png" alt="App Icon" width="96" height="96" class="mb-3">
                <p>Alumpro.Az tətbiqini cihazınıza quraşdırın və daha sürətli istifadə edin!</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="installPWA()">
                        <i class="bi bi-download"></i> İndi Quraşdır
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Daha Sonra
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>