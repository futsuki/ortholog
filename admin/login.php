<?php
require_once __DIR__ . '/../functions.php';

if (is_logged_in()) {
    redirect('/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (password_verify($password, ADMIN_PASSWORD)) {
        $_SESSION['admin_logged_in'] = true;
        redirect('/admin/');
    } else {
        $error = 'パスワードが正しくありません。';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/../template/header.php';
?>

<div class="login-box">
    <h1>管理者ログイン</h1>
    <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">ログイン</button>
    </form>
</div>

<?php
require __DIR__ . '/../template/footer.php';
