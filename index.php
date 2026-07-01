<?php
require_once __DIR__ . '/functions.php';

$slug = $_GET['id'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));

if ($slug) {
    $article = get_article($slug);
    if (!$article) {
        http_response_code(404);
        $pageTitle = 'Not Found';
        require __DIR__ . '/template/header.php';
        echo '<p>記事が見つかりません。</p>';
        require __DIR__ . '/template/footer.php';
        exit;
    }
    $pageTitle = $article['title'];
    require __DIR__ . '/template/header.php';
    ?>
    <div class="back-link">
        <a href="<?= h(SITE_URL) ?>">&larr; 記事一覧に戻る</a>
    </div>
    <article>
        <h1><?= h($article['title']) ?></h1>
        <div class="meta">
            <time datetime="<?= h($article['date']) ?>"><?= h($article['date']) ?></time>
            <?php foreach ($article['categories'] as $cat): ?>
                <span class="cat"><?= h($cat) ?></span>
            <?php endforeach; ?>
        </div>
        <?= $article['body'] ?>
    </article>
    <div class="back-link">
        <a href="<?= h(SITE_URL) ?>">&larr; 記事一覧に戻る</a>
    </div>
    <?php
    require __DIR__ . '/template/footer.php';
    exit;
}

$allArticles = load_articles();
$total = count($allArticles);
$offset = ($page - 1) * ITEMS_PER_PAGE;
$articles = array_slice($allArticles, $offset, ITEMS_PER_PAGE);
$totalPages = max(1, (int)ceil($total / ITEMS_PER_PAGE));

$pageTitle = $page > 1 ? "Page {$page}" : '';

require __DIR__ . '/template/header.php';
?>

<?php if (empty($articles)): ?>
    <p>まだ記事がありません。</p>
<?php else: ?>
    <ul class="article-list">
        <?php foreach ($articles as $article): ?>
            <li class="article-item">
                <a href="<?= h(SITE_URL) ?>?id=<?= urlencode($article['slug']) ?>" class="article-link">
                    <h2><?= h($article['title']) ?></h2>
                    <div class="meta">
                        <time datetime="<?= h($article['date']) ?>"><?= h($article['date']) ?></time>
                        <?php foreach ($article['categories'] as $cat): ?>
                            <span class="cat"><?= h($cat) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="excerpt"><?= h($article['excerpt']) ?></p>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= h(SITE_URL) ?>?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require __DIR__ . '/template/footer.php';
