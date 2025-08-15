<?php
$pageTitle = 'Haqqımızda';
require_once 'config/config.php';
require_once 'includes/header.php';

$db = Database::getInstance();
$content = $db->selectOne("SELECT * FROM website_content WHERE section = 'about'");
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Haqqımızda</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Haqqımızda</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <h2 class="gradient-text mb-4">Alumpro.Az - Keyfiyyət və Etibarlılıq</h2>
                <p class="lead">
                    <?= $content ? nl2br(htmlspecialchars($content['content'])) : 'Alumpro.Az olaraq, 10 ildən artıq təcrübəmizlə alüminium profil və şüşə məhsulları sahəsində lider şirkətlərdən biriyik.' ?>
                </p>
                
                <div class="mt-4">
                    <h4>Missiyamız</h4>
                    <p>Müştərilərimizə ən yüksək keyfiyyətli məhsulları təqdim edərək, onların yaşam məkanlarını daha funksional və estetik etmək.</p>
                    
                    <h4 class="mt-4">Vizyonumuz</h4>
                    <p>Azərbaycanda və regionda alüminium profil sektorunda innovativ həllərlər təqdim edən lider şirkət olmaq.</p>
                </div>
                
                <div class="row mt-5">
                    <div class="col-6 col-md-3 text-center mb-3">
                        <div class="counter-box">
                            <h3 class="text-primary">10+</h3>
                            <p>İllik Təcrübə</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center mb-3">
                        <div class="counter-box">
                            <h3 class="text-primary">5000+</h3>
                            <p>Məmnun Müştəri</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center mb-3">
                        <div class="counter-box">
                            <h3 class="text-primary">100+</h3>
                            <p>Məhsul Çeşidi</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center mb-3">
                        <div class="counter-box">
                            <h3 class="text-primary">2</h3>
                            <p>Mağaza</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <img src="assets/img/about-us.jpg" alt="Haqqımızda" class="img-fluid rounded shadow">
                
                <div class="mt-4">
                    <h4>Niyə Bizi Seçməlisiniz?</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Yüksək keyfiyyətli məhsullar
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Professional quraşdırma xidməti
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Müştəri məmnuniyyəti zəmanəti
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Rəqabətli qiymətlər
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Geniş məhsul çeşidi
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Pulsuz ölçü və konsultasiya
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Team Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-center mb-4">Komandamız</h3>
            </div>
            <div class="col-md-3 text-center mb-4">
                <img src="assets/img/team/ceo.jpg" alt="CEO" class="rounded-circle mb-3" width="150">
                <h5>Rəşad Məmmədov</h5>
                <p class="text-muted">Təsisçi və CEO</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <img src="assets/img/team/manager.jpg" alt="Manager" class="rounded-circle mb-3" width="150">
                <h5>Elnur Həsənov</h5>
                <p class="text-muted">Satış Meneceri</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <img src="assets/img/team/production.jpg" alt="Production" class="rounded-circle mb-3" width="150">
                <h5>Vüsal Əliyev</h5>
                <p class="text-muted">İstehsalat Müdiri</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <img src="assets/img/team/support.jpg" alt="Support" class="rounded-circle mb-3" width="150">
                <h5>Aysel Quliyeva</h5>
                <p class="text-muted">Müştəri Dəstək</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>