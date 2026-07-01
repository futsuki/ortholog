<?php
require_once __DIR__ . '/../functions.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['article']) || $_FILES['article']['error'] !== UPLOAD_ERR_OK) {
        $error = 'ファイルのアップロードに失敗しました。';
    } else {
        $file = $_FILES['article'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'md') {
            $error = '.md ファイルのみアップロードできます。';
        } else {
            $slug = $_POST['slug'] ?? '';
            $slug = $slug ?: pathinfo($file['name'], PATHINFO_FILENAME);
            $slug = preg_replace('/[^a-zA-Z0-9_\-]/u', '-', $slug);
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = 'article-' . time();
            }

            $content = file_get_contents($file['tmp_name']);
            $content = str_replace("\r\n", "\n", $content);

            $dest = ARTICLES_DIR . '/' . $slug . '.md';
            if (file_put_contents($dest, $content) !== false) {
                redirect('/admin/?uploaded=1');
            } else {
                $error = 'ファイルの保存に失敗しました。';
            }
        }
    }
}

$pageTitle = '記事アップロード';
require __DIR__ . '/../template/header.php';
?>

<div class="admin-header">
    <h1>記事アップロード</h1>
    <a href="./" class="btn btn-sm">管理画面に戻る</a>
</div>

<?php if ($error): ?>
    <div class="flash flash-error"><?= h($error) ?></div>
<?php endif; ?>

<div style="background:#fff;border:2px solid #e0e0e0;border-radius:6px;padding:24px;">
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="slug">スラッグ (省略時はファイル名から自動生成)</label>
            <input type="text" id="slug" name="slug" placeholder="my-first-post">
        </div>
        <div class="form-group">
            <label for="article">Markdown ファイル (.md)</label>
            <input type="file" id="article" name="article" accept=".md" required>
        </div>
        <div style="background:#f8f9fa;padding:12px;border-radius:4px;margin-bottom:16px;font-size:0.9rem;">
            <strong>ファイルの書き方:</strong>
            <pre style="margin:8px 0 0;"><code>---
title: 記事のタイトル
date: 2026-07-01
category: カテゴリ1, カテゴリ2
---
ここに Markdown 形式で本文を書きます。

## 見出し

本文...</code></pre>
        </div>
        <button type="submit" class="btn btn-primary">アップロード</button>
    </form>
</div>

<?php
require __DIR__ . '/../template/footer.php';
