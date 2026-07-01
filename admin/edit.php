<?php
require_once __DIR__ . '/../functions.php';
require_login();

$slug = $_GET['id'] ?? '';
$file = ARTICLES_DIR . '/' . basename($slug) . '.md';

if (!$slug || !file_exists($file)) {
    redirect('/admin/');
}

$content = file_get_contents($file);
$meta = parse_frontmatter($content);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['content'] ?? '';
    $newContent = str_replace("\r\n", "\n", $newContent);
    if (trim($newContent) === '') {
        $error = '内容が空です。';
    } else {
        if (file_put_contents($file, $newContent) !== false) {
            redirect('/admin/?edited=1');
        } else {
            $error = '保存に失敗しました。';
        }
    }
}

$pageTitle = '編集: ' . h($meta['title'] ?? $slug);
require __DIR__ . '/../template/header.php';
?>

<div class="admin-header">
    <h1>記事編集</h1>
    <a href="./" class="btn btn-sm">管理画面に戻る</a>
</div>

<?php if ($error): ?>
    <div class="flash flash-error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <label for="slug">スラッグ</label>
        <input type="text" id="slug" value="<?= h($slug) ?>.md" readonly disabled>
    </div>
    <div class="form-group">
        <label for="content">内容 (Markdown)</label>
        <textarea id="content" name="content" rows="24" style="width:100%;padding:12px;border:2px solid #ccc;border-radius:4px;font-family:'SF Mono',Monaco,'Cascadia Code',monospace;font-size:0.9rem;line-height:1.6;resize:vertical;"><?= h($content) ?></textarea>
    </div>
    <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-primary">保存</button>
        <a href="./" class="btn btn-sm" style="align-self:center;">キャンセル</a>
    </div>
</form>

<?php
require __DIR__ . '/../template/footer.php';
