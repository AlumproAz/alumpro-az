<?php
$pageTitle = 'Anbar İdarəsi';
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

session_start();

$auth = new Auth();
$db = Database::getInstance();

// Check if user is logged in and has access to inventory
if (!$auth->isLoggedIn() || (!$auth->hasRole('admin') && !$auth->hasRole('sales'))) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$storeId = $user['store_id'];

// Get current page and filters
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$lowStock = $_GET['low_stock'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if (!$auth->hasRole('admin') && $storeId) {
    $whereConditions[] = "i.store_id = :store_id";
    $params['store_id'] = $storeId;
}

if ($search) {
    $whereConditions[] = "(p.name LIKE :search OR p.code LIKE :search OR g.name LIKE :search OR g.code LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category) {
    $whereConditions[] = "(c1.id = :category OR c2.id = :category)";
    $params['category'] = $category;
}

if ($lowStock) {
    $whereConditions[] = "i.quantity < 10";
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get inventory data
$inventoryQuery = "
    SELECT 
        i.id,
        i.quantity,
        i.area_sqm,
        i.created_at,
        i.updated_at,
        p.id as product_id,
        p.code as product_code,
        p.name as product_name,
        p.type as product_type,
        p.color as product_color,
        p.unit as product_unit,
        p.sale_price as product_price,
        g.id as glass_id,
        g.code as glass_code,
        g.name as glass_name,
        g.type as glass_type,
        g.color as glass_color,
        g.sale_price as glass_price,
        c1.name as product_category,
        c2.name as glass_category,
        s.name as store_name
    FROM inventory i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN glass_products g ON i.glass_id = g.id
    LEFT JOIN categories c1 ON p.category_id = c1.id
    LEFT JOIN categories c2 ON g.category_id = c2.id
    LEFT JOIN stores s ON i.store_id = s.id
    $whereClause
    ORDER BY i.updated_at DESC
    LIMIT :limit OFFSET :offset
";

$params['limit'] = $limit;
$params['offset'] = $offset;

$inventory = $db->select($inventoryQuery, $params);

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total
    FROM inventory i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN glass_products g ON i.glass_id = g.id
    LEFT JOIN categories c1 ON p.category_id = c1.id
    LEFT JOIN categories c2 ON g.category_id = c2.id
    $whereClause
";
unset($params['limit'], $params['offset']);
$totalRecords = $db->selectOne($countQuery, $params)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get categories for filter
$categories = $db->select("SELECT id, name, type FROM categories ORDER BY name");

// Get stores for admin
$stores = [];
if ($auth->hasRole('admin')) {
    $stores = $db->select("SELECT id, name FROM stores ORDER BY name");
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/modern-dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark <?= $auth->hasRole('admin') ? 'bg-primary' : 'bg-success' ?>">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-boxes"></i> Anbar İdarəsi
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <i class="bi bi-boxes"></i> Anbar İdarəsi
                            </h4>
                            <?php if ($auth->hasRole('admin')): ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="bi bi-plus"></i> Məhsul Əlavə Et
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGlassModal">
                                    <i class="bi bi-plus"></i> Şüşə Əlavə Et
                                </button>
                                <button type="button" class="btn btn-info" onclick="exportInventory()">
                                    <i class="bi bi-download"></i> Excel Export
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Axtarış</label>
                                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Məhsul adı və ya kodu...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kateqoriya</label>
                                <select class="form-control" name="category">
                                    <option value="">Bütün kateqoriyalar</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Az Qalan</label>
                                <select class="form-control" name="low_stock">
                                    <option value="">Hamısı</option>
                                    <option value="1" <?= $lowStock ? 'selected' : '' ?>>Yalnız az qalanlar</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Axtar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="inventory.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Təmizlə
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            Anbar Məlumatları 
                            <small class="text-muted">(<?= $totalRecords ?> məhsul)</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kod</th>
                                        <th>Məhsul Adı</th>
                                        <th>Kateqoriya</th>
                                        <th>Tip/Rəng</th>
                                        <th>Sayı</th>
                                        <th>Vahidi</th>
                                        <th>Qiymət</th>
                                        <?php if ($auth->hasRole('admin')): ?>
                                        <th>Mağaza</th>
                                        <?php endif; ?>
                                        <th>Son Yeniləmə</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory as $item): ?>
                                    <tr class="<?= $item['quantity'] < 10 ? 'table-warning' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($item['product_code'] ?: $item['glass_code']) ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($item['product_name'] ?: $item['glass_name']) ?>
                                            <?php if ($item['quantity'] < 5): ?>
                                            <span class="badge bg-danger ms-2">TÜKƏNIR</span>
                                            <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="badge bg-warning ms-2">AZ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['product_category'] ?: $item['glass_category']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($item['product_type'] ?: $item['glass_type']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($item['product_color'] ?: $item['glass_color']) ?></small>
                                        </td>
                                        <td>
                                            <strong class="<?= $item['quantity'] < 10 ? 'text-warning' : '' ?>">
                                                <?= number_format($item['quantity'], 1) ?>
                                            </strong>
                                            <?php if ($item['area_sqm']): ?>
                                            <br><small class="text-muted"><?= number_format($item['area_sqm'], 2) ?> m²</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['product_unit'] ?: 'm²' ?></td>
                                        <td><?= number_format($item['product_price'] ?: $item['glass_price'], 2) ?> ₼</td>
                                        <?php if ($auth->hasRole('admin')): ?>
                                        <td><?= htmlspecialchars($item['store_name']) ?></td>
                                        <?php endif; ?>
                                        <td><?= date('d.m.Y', strtotime($item['updated_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="updateStock(<?= $item['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($auth->hasRole('admin')): ?>
                                                <button type="button" class="btn btn-outline-info" onclick="transferStock(<?= $item['id'] ?>)">
                                                    <i class="bi bi-arrow-left-right"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteItem(<?= $item['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Inventory pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&low_stock=<?= urlencode($lowStock) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <?php if ($auth->hasRole('admin')): ?>
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Məhsul Əlavə Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Məhsul Kodu</label>
                                <input type="text" class="form-control" id="productCode" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Məhsul Adı</label>
                                <input type="text" class="form-control" id="productName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kateqoriya</label>
                                <select class="form-control" id="productCategory" required>
                                    <option value="">Seçin</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tip</label>
                                <input type="text" class="form-control" id="productType" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rəng</label>
                                <input type="text" class="form-control" id="productColor">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vahid</label>
                                <select class="form-control" id="productUnit" required>
                                    <option value="piece">Ədəd</option>
                                    <option value="meter">Metr</option>
                                    <option value="square_meter">Kvadrat metr</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alış Qiyməti (₼)</label>
                                <input type="number" class="form-control" id="purchasePrice" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Satış Qiyməti (₼)</label>
                                <input type="number" class="form-control" id="salePrice" step="0.01" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mağaza</label>
                                <select class="form-control" id="productStore" required>
                                    <option value="">Seçin</option>
                                    <?php foreach ($stores as $store): ?>
                                    <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İlkin Sayı</label>
                                <input type="number" class="form-control" id="initialQuantity" min="0" value="0" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Yadda Saxla</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stock Update Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stok Yenilə</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStockForm">
                        <input type="hidden" id="updateInventoryId">
                        <div class="mb-3">
                            <label class="form-label">Məhsul</label>
                            <input type="text" class="form-control" id="updateProductName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hazırkı Say</label>
                            <input type="text" class="form-control" id="currentQuantity" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Əməliyyat Tipi</label>
                            <select class="form-control" id="transactionType" required>
                                <option value="in">Giriş (+)</option>
                                <option value="out">Çıxış (-)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Say</label>
                            <input type="number" class="form-control" id="transactionQuantity" min="0.1" step="0.1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Qeyd</label>
                            <textarea class="form-control" id="transactionNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                    <button type="button" class="btn btn-primary" onclick="saveStockUpdate()">Yenilə</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
    function updateStock(inventoryId) {
        // Get inventory details
        fetch('../api/get-inventory-data.php?id=' + inventoryId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('updateInventoryId').value = inventoryId;
                document.getElementById('updateProductName').value = data.name;
                document.getElementById('currentQuantity').value = data.quantity + ' ' + data.unit;
                
                const modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
                modal.show();
            });
    }

    function saveStockUpdate() {
        const formData = new FormData();
        formData.append('inventory_id', document.getElementById('updateInventoryId').value);
        formData.append('transaction_type', document.getElementById('transactionType').value);
        formData.append('quantity', document.getElementById('transactionQuantity').value);
        formData.append('notes', document.getElementById('transactionNotes').value);
        
        fetch('../api/update-inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Xəta: ' + data.message);
            }
        });
    }

    <?php if ($auth->hasRole('admin')): ?>
    function saveProduct() {
        const formData = new FormData();
        formData.append('code', document.getElementById('productCode').value);
        formData.append('name', document.getElementById('productName').value);
        formData.append('category_id', document.getElementById('productCategory').value);
        formData.append('type', document.getElementById('productType').value);
        formData.append('color', document.getElementById('productColor').value);
        formData.append('unit', document.getElementById('productUnit').value);
        formData.append('purchase_price', document.getElementById('purchasePrice').value);
        formData.append('sale_price', document.getElementById('salePrice').value);
        formData.append('store_id', document.getElementById('productStore').value);
        formData.append('initial_quantity', document.getElementById('initialQuantity').value);
        
        fetch('../api/save-product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Xəta: ' + data.message);
            }
        });
    }

    function exportInventory() {
        window.open('../api/export-inventory.php?store_id=<?= $storeId ?>', '_blank');
    }

    function deleteItem(inventoryId) {
        if (confirm('Bu məhsulu silmək istəyirsiniz?')) {
            fetch('../api/delete-inventory.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({inventory_id: inventoryId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Xəta: ' + data.message);
                }
            });
        }
    }
    <?php endif; ?>
    </script>
</body>
</html>