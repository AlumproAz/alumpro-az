<?php
$pageTitle = 'Xidmətlərimiz';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Xidmətlərimiz</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Xidmətlər</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <!-- Main Services -->
        <div class="row mb-5">
            <div class="col-lg-4 mb-4">
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h3>İstehsalat</h3>
                    <p>Müasir avadanlıqlarla alüminium profil və şüşə məhsulların istehsalı</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-primary"></i> Mətbəx qapaqları</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Dolap qapıları</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Arakəsmə sistemləri</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Duş kabinələri</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary mt-3">Sifariş Et</a>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3>Çatdırılma</h3>
                    <p>Bakı və ətraf rayonlara sürətli və təhlükəsiz çatdırılma xidməti</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-primary"></i> Sürətli çatdırılma</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Quraşdırma xidməti</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Sığortalı daşıma</li>
                        <li><i class="bi bi-check-circle text-primary"></i> İzləmə sistemi</li>
                    </ul>
                    <a href="#delivery-calculator" class="btn btn-primary mt-3">Qiymət Hesabla</a>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h3>Quraşdırma</h3>
                    <p>Təcrübəli ustalar tərəfindən professional quraşdırma xidməti</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-primary"></i> Pulsuz ölçü</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Professional quraşdırma</li>
                        <li><i class="bi bi-check-circle text-primary"></i> 1 il zəmanət</li>
                        <li><i class="bi bi-check-circle text-primary"></i> Servis xidməti</li>
                    </ul>
                    <a href="contact.php" class="btn btn-primary mt-3">Usta Çağır</a>
                </div>
            </div>
        </div>
        
        <!-- Additional Services -->
        <div class="row">
            <div class="col-12">
                <h3 class="text-center mb-4">Əlavə Xidmətlər</h3>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-calculator text-primary"></i> Dizayn və Layihələndirmə</h5>
                        <p>3D dizayn və layihələndirmə xidməti ilə sifarişinizi əvvəlcədən görün</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-palette text-primary"></i> Rəng Seçimi</h5>
                        <p>Geniş rəng çeşidi ilə interyerinizə uyğun məhsul seçimi</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-recycle text-primary"></i> Köhnə Məhsulların Dəyişdirilməsi</h5>
                        <p>Köhnə məhsullarınızı yeniləri ilə əvəz etmə xidməti</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-wrench text-primary"></i> Təmir və Baxım</h5>
                        <p>Quraşdırdığımız məhsullara texniki xidmət və təmir</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delivery Calculator -->
        <div class="row mt-5" id="delivery-calculator">
            <div class="col-lg-6 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-calculator"></i> Çatdırılma Qiymət Kalkulyatoru</h4>
                    </div>
                    <div class="card-body">
                        <form id="deliveryForm">
                            <div class="mb-3">
                                <label class="form-label">Ünvan (Rayon)</label>
                                <select class="form-select" id="district" required>
                                    <option value="">Seçin...</option>
                                    <option value="0">Bakı (mərkəz)</option>
                                    <option value="10">Xırdalan</option>
                                    <option value="15">Sumqayıt</option>
                                    <option value="20">Abşeron rayonu</option>
                                    <option value="50">Digər rayonlar</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Məhsul sayı</label>
                                <input type="number" class="form-control" id="productCount" min="1" value="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Quraşdırma lazımdır?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="installation">
                                    <label class="form-check-label" for="installation">
                                        Bəli, quraşdırma lazımdır (+50 ₼)
                                    </label>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary w-100" onclick="calculateDelivery()">
                                Hesabla
                            </button>
                        </form>
                        
                        <div id="deliveryResult" class="alert alert-info mt-3" style="display: none;">
                            <h5>Təxmini qiymət: <span id="totalPrice">0</span> ₼</h5>
                            <small>* Bu qiymət təxminidir və dəqiqləşdirilə bilər</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.service-card {
    padding: 30px;
    text-align: center;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.service-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #1a936f, #1a5493);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
}
</style>

<script>
function calculateDelivery() {
    const district = parseInt(document.getElementById('district').value) || 0;
    const productCount = parseInt(document.getElementById('productCount').value) || 1;
    const installation = document.getElementById('installation').checked;
    
    let basePrice = district;
    let productPrice = productCount > 5 ? productCount * 2 : 0;
    let installationPrice = installation ? 50 : 0;
    
    let totalPrice = basePrice + productPrice + installationPrice;
    
    document.getElementById('totalPrice').textContent = totalPrice;
    document.getElementById('deliveryResult').style.display = 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>