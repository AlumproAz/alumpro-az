<?php
$pageTitle = 'Məhsullar';
require_once 'config/config.php';
require_once 'includes/header.php';

$db = Database::getInstance();

// Get categories
$categories = $db->select("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

// Get filters
$categoryId = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';

// Build query
$query = "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

$params = [];

if ($categoryId) {
    $query .= " AND p.category_id = :category_id";
    $params['category_id'] = $categoryId;
}

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.type LIKE :search OR p.color LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.sale_price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.sale_price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.name ASC";
}

$products = $db->select($query, $params);

// Get glass products
$glassProducts = $db->select("
    SELECT g.*, c.name as category_name
    FROM glass_products g
    JOIN categories c ON g.category_id = c.id
    ORDER BY g.name
");
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Məhsullarımız</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Məhsullar</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Filtrlər</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <!-- Search -->
                            <div class="mb-3">
                                <label class="form-label">Axtar</label>
                                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Məhsul adı...">
                            </div>
                            
                            <!-- Categories -->
                            <div class="mb-3">
                                <label class="form-label">Kateqoriya</label>
                                <select class="form-select" name="category">
                                    <option value="">Hamısı</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Sort -->
                            <div class="mb-3">
                                <label class="form-label">Sıralama</label>
                                <select class="form-select" name="sort">
                                    <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Ada görə</option>
                                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Qiymət (artan)</option>
                                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Qiymət (azalan)</option>
                                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Ən yeni</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filtr Tətbiq Et
                            </button>
                            
                            <?php if ($search || $categoryId): ?>
                            <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-x-circle"></i> Təmizlə
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Contact Card -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h5>Sualınız var?</h5>
                        <p class="small">Bizimlə əlaqə saxlayın</p>
                        <a href="tel:+994123456789" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-telephone"></i> Zəng Et
                        </a>
                        <a href="https://wa.me/994123456789" class="btn btn-success w-100">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#profiles">
                            <i class="bi bi-grid"></i> Alüminium Profillər
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#glass">
                            <i class="bi bi-square"></i> Şüşə Məhsullar
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Aluminum Profiles -->
                    <div class="tab-pane fade show active" id="profiles">
                        <div class="row">
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card product-card h-100">
                                        <img src="assets/img/products/<?= $product['id'] ?>.jpg" 
                                             onerror="this.src='assets/img/products/default.jpg'" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                        <div class="card-body">
                                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Kod: <?= htmlspecialchars($product['code']) ?><br>
                                                    Rəng: <?= htmlspecialchars($product['color']) ?><br>
                                                    Tip: <?= htmlspecialchars($product['type']) ?>
                                                </small>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="h5 mb-0 text-primary">
                                                    <?= number_format($product['sale_price'], 2) ?> ₼
                                                    <small>/ <?= $product['unit'] ?></small>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <button class="btn btn-outline-primary btn-sm w-100" onclick="showProductDetails(<?= $product['id'] ?>)">
                                                <i class="bi bi-eye"></i> Ətraflı
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> Məhsul tapılmadı
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Glass Products -->
                    <div class="tab-pane fade" id="glass">
                        <div class="row">
                            <?php foreach ($glassProducts as $glass): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card product-card h-100">
                                    <img src="assets/img/glass/<?= $glass['id'] ?>.jpg" 
                                         onerror="this.src='assets/img/glass/default.jpg'" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($glass['name']) ?>">
                                    <div class="card-body">
                                        <span class="badge bg-info mb-2"><?= htmlspecialchars($glass['category_name']) ?></span>
                                        <h5 class="card-title"><?= htmlspecialchars($glass['name']) ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Kod: <?= htmlspecialchars($glass['code']) ?><br>
                                                Rəng: <?= htmlspecialchars($glass['color']) ?><br>
                                                Qalınlıq: <?= $glass['thickness'] ?> mm
                                            </small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0 text-info">
                                                <?= number_format($glass['sale_price'], 2) ?> ₼
                                                <small>/ m²</small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <button class="btn btn-outline-info btn-sm w-100" onclick="showGlassDetails(<?= $glass['id'] ?>)">
                                            <i class="bi bi-eye"></i> Ətraflı
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Məhsul Detalları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetails">
                <!-- Product details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" onclick="requestQuote()">
                    <i class="bi bi-calculator"></i> Qiymət Al
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showProductDetails(productId) {
    // Load product details via AJAX
    fetch(`/api/get-product-details.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('productDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <img src="${data.image}" class="img-fluid" alt="${data.name}">
                    </div>
                    <div class="col-md-6">
                        <h4>${data.name}</h4>
                        <p class="text-muted">${data.category}</p>
                        <table class="table">
                            <tr><td>Kod:</td><td>${data.code}</td></tr>
                            <tr><td>Rəng:</td><td>${data.color}</td></tr>
                            <tr><td>Tip:</td><td>${data.type}</td></tr>
                            <tr><td>Ölçü:</td><td>${data.size}</td></tr>
                            <tr><td>Qiymət:</td><td class="h5 text-primary">${data.price} ₼</td></tr>
                        </table>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('productModal')).show();
        });
}

function showGlassDetails(glassId) {
    // Similar to showProductDetails but for glass products
}

function requestQuote() {
    window.location.href = 'contact.php?request=quote';
}
</script>

<?php require_once 'includes/footer.php'; ?>