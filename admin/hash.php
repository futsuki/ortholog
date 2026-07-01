<?php
require_once __DIR__ . '/../functions.php';
require_login();

$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
}

$pageTitle = 'パスワードハッシュ計算';
require __DIR__ . '/../template/header.php';
?>

<div class="admin-header">
    <h1>パスワードハッシュ計算</h1>
    <a href="./" class="btn btn-sm">管理画面に戻る</a>
</div>

<div style="background:#fff;border:2px solid #e0e0e0;border-radius:6px;padding:24px;">
    <form method="post">
        <div class="form-group">
            <label for="password">新しいパスワード</label>
            <input type="text" id="password" name="password" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary">ハッシュを生成</button>
    </form>

    <?php if ($hash): ?>
        <div style="margin-top:24px;padding:16px;background:#f8f9fa;border-radius:4px;">
            <p style="margin-bottom:8px;font-weight:600;">生成されたハッシュ:</p>
            <code style="word-break:break-all;font-size:0.9rem;"><?= h($hash) ?></code>
            <p style="margin-top:16px;font-size:0.85rem;color:#666;">
                この値を <code>config.php</code> の <code>ADMIN_PASSWORD</code> に設定してください。
            </p>
        </div>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../template/footer.php';
