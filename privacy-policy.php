<?php
$pageTitle = 'Gizlilik Siyasəti';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Gizlilik Siyasəti</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Gizlilik Siyasəti</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted">Son yenilənmə: <?= date('d.m.Y') ?></p>
                        
                        <h3>1. Ümumi Müddəalar</h3>
                        <p>Alumpro.Az olaraq, müştərilərimizin şəxsi məlumatlarının qorunmasına böyük əhəmiyyət veririk. Bu gizlilik siyasəti, sizin şəxsi məlumatlarınızın necə toplandığını, istifadə edildiyini və qorunduğunu izah edir.</p>
                        
                        <h3>2. Toplanan Məlumatlar</h3>
                        <p>Biz aşağıdakı məlumatları toplaya bilərik:</p>
                        <ul>
                            <li>Ad və soyad</li>
                            <li>Telefon nömrəsi</li>
                            <li>E-poçt ünvanı</li>
                            <li>Çatdırılma ünvanı</li>
                            <li>Sifariş tarixçəsi</li>
                            <li>Ödəniş məlumatları (təhlükəsiz şəkildə)</li>
                        </ul>
                        
                        <h3>3. Məlumatların İstifadəsi</h3>
                        <p>Topladığımız məlumatları aşağıdakı məqsədlər üçün istifadə edirik:</p>
                        <ul>
                            <li>Sifarişlərinizi emal etmək və çatdırmaq</li>
                            <li>Müştəri xidməti təmin etmək</li>
                            <li>Yeni məhsul və kampaniyalar haqqında məlumat vermək</li>
                            <li>Xidmətlərimizi təkmilləşdirmək</li>
                            <li>Qanuni öhdəlikləri yerinə yetirmək</li>
                        </ul>
                        
                        <h3>4. Məlumatların Qorunması</h3>
                        <p>Şəxsi məlumatlarınızı qorumaq üçün müasir təhlükəsizlik tədbirləri tətbiq edirik:</p>
                        <ul>
                            <li>SSL şifrələmə</li>
                            <li>Məhdud giriş hüquqları</li>
                            <li>Müntəzəm təhlükəsizlik yeniləmələri</li>
                            <li>Firewall qorunması</li>
                        </ul>
                        
                        <h3>5. Üçüncü Tərəflərlə Paylaşma</h3>
                        <p>Biz sizin şəxsi məlumatlarınızı satmırıq və ya icarəyə vermirik. Məlumatlarınız yalnız aşağıdakı hallarda paylaşıla bilər:</p>
                        <ul>
                            <li>Sizin açıq razılığınızla</li>
                            <li>Qanuni tələblərə uyğun olaraq</li>
                            <li>Çatdırılma xidməti təmin etmək üçün (məhdud məlumat)</li>
                        </ul>
                        
                        <h3>6. Cookie Siyasəti</h3>
                        <p>Saytımız istifadəçi təcrübəsini yaxşılaşdırmaq üçün cookie-lərdən istifadə edir. Cookie-lər kiçik mətn fayllarıdır və kompüterinizdə saxlanılır.</p>
                        
                        <h3>7. Hüquqlarınız</h3>
                        <p>Siz aşağıdakı hüquqlara maliksiniz:</p>
                        <ul>
                            <li>Şəxsi məlumatlarınıza giriş tələb etmək</li>
                            <li>Məlumatlarınızın düzəldilməsini istəmək</li>
                            <li>Məlumatlarınızın silinməsini tələb etmək</li>
                            <li>Marketinq mesajlarından imtina etmək</li>
                        </ul>
                        
                        <h3>8. Uşaqların Məxfiliyi</h3>
                        <p>Xidmətlərimiz 18 yaşdan kiçik şəxslər üçün nəzərdə tutulmayıb. Biz bilərəkdən uşaqlardan şəxsi məlumat toplamırıq.</p>
                        
                        <h3>9. Dəyişikliklər</h3>
                        <p>Bu gizlilik siyasəti vaxtaşırı yenilənə bilər. Əhəmiyyətli dəyişikliklər barədə sizə məlumat veriləcək.</p>
                        
                        <h3>10. Əlaqə</h3>
                        <p>Gizlilik siyasəti ilə bağlı suallarınız üçün bizimlə əlaqə saxlaya bilərsiniz:</p>
                        <ul>
                            <li>E-poçt: privacy@alumpro.az</li>
                            <li>Telefon: +994 12 345 67 89</li>
                            <li>Ünvan: Bakı, Azərbaycan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>