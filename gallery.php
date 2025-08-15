<?php
$pageTitle = 'Qalereya';
require_once 'config/config.php';
require_once 'includes/header.php';

$db = Database::getInstance();

// Get gallery categories
$categories = $db->select("
    SELECT DISTINCT category, COUNT(*) as count
    FROM gallery_images
    WHERE is_active = 1
    GROUP BY category
    ORDER BY category
");

// Get images
$selectedCategory = $_GET['category'] ?? 'all';
$query = "SELECT * FROM gallery_images WHERE is_active = 1";
$params = [];

if ($selectedCategory !== 'all') {
    $query .= " AND category = :category";
    $params['category'] = $selectedCategory;
}

$query .= " ORDER BY created_at DESC";
$images = $db->select($query, $params);
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">İşlərimiz</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Qalereya</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills justify-content-center mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $selectedCategory === 'all' ? 'active' : '' ?>" href="?category=all">
                    Hamısı
                </a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li class="nav-item">
                <a class="nav-link <?= $selectedCategory === $cat['category'] ? 'active' : '' ?>" 
                   href="?category=<?= urlencode($cat['category']) ?>">
                    <?= htmlspecialchars($cat['category']) ?> (<?= $cat['count'] ?>)
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Gallery Grid -->
        <div class="row gallery-grid">
            <?php foreach ($images as $image): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="gallery-item">
                    <img src="uploads/gallery/<?= htmlspecialchars($image['image_path']) ?>" 
                         alt="<?= htmlspecialchars($image['title']) ?>"
                         class="img-fluid"
                         data-bs-toggle="modal"
                         data-bs-target="#imageModal"
                         onclick="showImage('<?= htmlspecialchars($image['image_path']) ?>', '<?= htmlspecialchars($image['title']) ?>', '<?= htmlspecialchars($image['description']) ?>')">
                    <div class="gallery-overlay">
                        <h5><?= htmlspecialchars($image['title']) ?></h5>
                        <p><?= htmlspecialchars($image['category']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($images)): ?>
        <div class="text-center py-5">
            <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
            <p class="mt-3">Bu kateqoriyada şəkil yoxdur</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid">
                <p id="imageDescription" class="mt-3"></p>
            </div>
        </div>
    </div>
</div>

<style>
.gallery-grid {
    margin: -10px;
}

.gallery-grid > div {
    padding: 10px;
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.gallery-item:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.gallery-item img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.gallery-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    padding: 20px 15px 15px;
    transform: translateY(100%);
    transition: all 0.3s;
}

.gallery-item:hover .gallery-overlay {
    transform: translateY(0);
}

.gallery-overlay h5 {
    margin: 0;
    font-size: 16px;
}

.gallery-overlay p {
    margin: 5px 0 0;
    font-size: 12px;
    opacity: 0.9;
}
</style>

<script>
function showImage(path, title, description) {
    document.getElementById('modalImage').src = 'uploads/gallery/' + path;
    document.getElementById('imageTitle').textContent = title;
    document.getElementById('imageDescription').textContent = description || '';
}
</script>

<?php require_once 'includes/footer.php'; ?>