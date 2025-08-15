<?php
$pageTitle = 'Xəbərlər';
require_once 'config/config.php';
require_once 'includes/header.php';

$db = Database::getInstance();

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Get total news count
$totalNews = $db->selectOne("SELECT COUNT(*) as count FROM news WHERE is_published = 1")['count'];
$totalPages = ceil($totalNews / $perPage);

// Get news
$news = $db->select("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.created_by = u.id
    WHERE n.is_published = 1
    ORDER BY n.created_at DESC
    LIMIT :limit OFFSET :offset
", ['limit' => $perPage, 'offset' => $offset]);
?>

<div class="page-header-section">
    <div class="container">
        <h1 class="page-title">Xəbərlər və Yeniliklər</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item active">Xəbərlər</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <?php foreach ($news as $article): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card news-card h-100">
                    <?php if ($article['featured_image']): ?>
                    <img src="uploads/news/<?= htmlspecialchars($article['featured_image']) ?>" 
                         class="card-img-top news-image" 
                         alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php else: ?>
                    <div class="news-placeholder">
                        <i class="bi bi-newspaper"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="news-meta">
                            <span><i class="bi bi-calendar3"></i> <?= date('d.m.Y', strtotime($article['created_at'])) ?></span>
                            <span><i class="bi bi-person"></i> <?= htmlspecialchars($article['author_name']) ?></span>
                        </div>
                        
                        <h5 class="card-title mt-2">
                            <a href="news-detail.php?id=<?= $article['id'] ?>&slug=<?= $article['slug'] ?>">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h5>
                        
                        <p class="card-text">
                            <?= htmlspecialchars(mb_substr(strip_tags($article['content']), 0, 150)) ?>...
                        </p>
                        
                        <a href="news-detail.php?id=<?= $article['id'] ?>&slug=<?= $article['slug'] ?>" 
                           class="btn btn-outline-primary btn-sm">
                            Ətraflı <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($news)): ?>
        <div class="text-center py-5">
            <i class="bi bi-newspaper text-muted" style="font-size: 4rem;"></i>
            <p class="mt-3">Hal-hazırda xəbər yoxdur</p>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Əvvəlki</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Növbəti</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</section>

<style>
.news-card {
    transition: all 0.3s;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.news-image {
    height: 250px;
    object-fit: cover;
}

.news-placeholder {
    height: 250px;
    background: linear-gradient(135deg, #1a936f, #1a5493);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 4rem;
}

.news-meta {
    font-size: 0.875rem;
    color: #6c757d;
    display: flex;
    gap: 15px;
}

.news-meta i {
    margin-right: 5px;
}

.card-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s;
}

.card-title a:hover {
    color: #1a936f;
}
</style>

<?php require_once 'includes/footer.php'; ?>