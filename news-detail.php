<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

$newsId = $_GET['id'] ?? 0;
$slug = $_GET['slug'] ?? '';

// Get news article
$article = $db->selectOne("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.created_by = u.id
    WHERE n.id = :id AND n.is_published = 1
", ['id' => $newsId]);

if (!$article) {
    header('Location: news.php');
    exit;
}

// Update view count
$db->query("UPDATE news SET view_count = view_count + 1 WHERE id = :id", ['id' => $newsId]);

// Get related news
$relatedNews = $db->select("
    SELECT id, title, slug, featured_image, created_at
    FROM news
    WHERE id != :id AND is_published = 1
    ORDER BY created_at DESC
    LIMIT 3
", ['id' => $newsId]);

$pageTitle = $article['title'];
require_once 'includes/header.php';
?>

<div class="page-header-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="home.php">Ana Səhifə</a></li>
                <li class="breadcrumb-item"><a href="news.php">Xəbərlər</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($article['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <article class="news-article">
                    <?php if ($article['featured_image']): ?>
                    <img src="uploads/news/<?= htmlspecialchars($article['featured_image']) ?>" 
                         class="img-fluid mb-4 rounded" 
                         alt="<?= htmlspecialchars($article['title']) ?>">
                    <?php endif; ?>
                    
                    <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                    
                    <div class="article-meta mb-4">
                        <span><i class="bi bi-calendar3"></i> <?= date('d.m.Y H:i', strtotime($article['created_at'])) ?></span>
                        <span><i class="bi bi-person"></i> <?= htmlspecialchars($article['author_name']) ?></span>
                        <span><i class="bi bi-eye"></i> <?= $article['view_count'] ?> baxış</span>
                    </div>
                    
                    <div class="article-content">
                        <?= $article['content'] ?>
                    </div>
                    
                    <!-- Share Buttons -->
                    <div class="share-buttons mt-4 pt-4 border-top">
                        <h5>Paylaş:</h5>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/news-detail.php?id=' . $article['id']) ?>" 
                               target="_blank" 
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-facebook"></i> Facebook
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' ' . SITE_URL . '/news-detail.php?id=' . $article['id']) ?>" 
                               target="_blank" 
                               class="btn btn-success btn-sm">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode(SITE_URL . '/news-detail.php?id=' . $article['id']) ?>" 
                               target="_blank" 
                               class="btn btn-info btn-sm">
                                <i class="bi bi-twitter"></i> Twitter
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            
            <div class="col-lg-4">
                <!-- Related News -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Digər Xəbərlər</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($relatedNews as $related): ?>
                        <div class="related-news-item mb-3">
                            <div class="row g-2">
                                <?php if ($related['featured_image']): ?>
                                <div class="col-4">
                                    <img src="uploads/news/<?= htmlspecialchars($related['featured_image']) ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?= htmlspecialchars($related['title']) ?>">
                                </div>
                                <div class="col-8">
                                <?php else: ?>
                                <div class="col-12">
                                <?php endif; ?>
                                    <h6 class="mb-1">
                                        <a href="news-detail.php?id=<?= $related['id'] ?>&slug=<?= $related['slug'] ?>">
                                            <?= htmlspecialchars($related['title']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <?= date('d.m.Y', strtotime($related['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Contact Card -->
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Sualınız var?</h5>
                        <p>Bizimlə əlaqə saxlayın</p>
                        <a href="tel:+994123456789" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-telephone"></i> Zəng Et
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-envelope"></i> Mesaj Göndər
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.article-title {
    font-size: 2rem;
    color: #333;
    margin-bottom: 15px;
}

.article-meta {
    color: #6c757d;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.article-meta i {
    margin-right: 5px;
}

.article-content {
    font-size: 1.1rem;
    line-height: 1.8;
}

.article-content p {
    margin-bottom: 1.5rem;
}

.article-content img {
    max-width: 100%;
    height: auto;
    margin: 20px 0;
}

.related-news-item h6 a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s;
}

.related-news-item h6 a:hover {
    color: #1a936f;
}
</style>

<?php require_once 'includes/footer.php'; ?>