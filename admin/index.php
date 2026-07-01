<?php
require_once __DIR__ . '/../functions.php';
require_login();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $slug = $_POST['delete'];
    if (delete_article($slug)) {
        $msg = '<div class="flash flash-success">記事を削除しました。</div>';
    } else {
        $msg = '<div class="flash flash-error">削除に失敗しました。</div>';
    }
}

if (isset($_GET['uploaded'])) {
    $msg = '<div class="flash flash-success">記事をアップロードしました。</div>';
}

if (isset($_GET['edited'])) {
    $msg = '<div class="flash flash-success">記事を保存しました。</div>';
}

$articles = load_articles();
$pageTitle = '管理画面';
require __DIR__ . '/../template/header.php';
?>

<div class="admin-header">
    <h1>管理画面</h1>
    <div>
        <a href="upload.php" class="btn btn-primary">新規記事アップロード</a>
    </div>
</div>

<?= $msg ?>

<?php if (empty($articles)): ?>
    <p>まだ記事がありません。</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>タイトル</th>
                <th>日付</th>
                <th>カテゴリ</th>
                <th>ファイル</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <a href="<?= h(SITE_URL) ?>?id=<?= urlencode($article['slug']) ?>" target="_blank">
                            <?= h($article['title']) ?>
                        </a>
                    </td>
                    <td><?= h($article['date']) ?></td>
                    <td><?= h(implode(', ', $article['categories'])) ?></td>
                    <td><code><?= h($article['slug']) ?>.md</code></td>
                    <td>
                        <a href="edit.php?id=<?= urlencode($article['slug']) ?>" class="btn btn-primary btn-sm">編集</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('この記事を削除しますか？');">
                            <button type="submit" name="delete" value="<?= h($article['slug']) ?>" class="btn btn-danger btn-sm">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top:48px;">
    <a href="hash.php" class="btn btn-sm">パスワード変更</a>
</div>
<div style="margin-top:12px;">
    <a href="logout.php" class="btn btn-sm">ログアウト</a>
</div>

<?php
require __DIR__ . '/../template/footer.php';
