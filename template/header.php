<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?><?= h(SITE_TITLE) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.7; color: #333; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0; padding: 20px; }
        header { background: #fff; border-bottom: 2px solid #e0e0e0; padding: 16px 0; margin-bottom: 32px; }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.4rem; }
        header h1 a { color: #333; text-decoration: none; }
        nav a { color: #666; text-decoration: none; font-size: 0.9rem; margin-left: 16px; }
        nav a:hover { color: #333; }
        .article-list { list-style: none; }
        .article-item { background: #fff; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 16px; }
        .article-item:hover { border-color: #0066cc; }
        .article-link { display: block; padding: 20px; color: inherit; text-decoration: none; }
        .article-link h2 { font-size: 1.2rem; margin-bottom: 4px; }
        .article-link:hover h2 { color: #0066cc; }
        .article-link .meta { font-size: 0.85rem; color: #999; }
        .cat { display: inline-block; background: #e8f0fe; color: #1a73e8; padding: 1px 8px; border-radius: 3px; font-size: 0.8rem; margin-left: 6px; }
        article .cat { background: #e8f0fe; color: #1a73e8; padding: 2px 10px; border-radius: 3px; font-size: 0.85rem; margin-left: 8px; }
        .article-item .excerpt { margin-top: 8px; color: #555; font-size: 0.95rem; }
        article { background: #fff; padding: 32px; border: 2px solid #e0e0e0; border-radius: 6px; }
        article h1 { font-size: 1.8rem; margin-bottom: 8px; }
        article .meta { color: #999; font-size: 0.9rem; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #eee; }
        article h2 { font-size: 1.4rem; margin: 24px 0 12px; }
        article h3 { font-size: 1.2rem; margin: 20px 0 8px; }
        article p { margin-bottom: 16px; }
        article pre { background: #f4f4f5; padding: 16px; border-radius: 4px; overflow-x: auto; margin-bottom: 16px; }
        article code { font-family: "SF Mono", Monaco, "Cascadia Code", monospace; font-size: 0.9em; background: #f4f4f5; padding: 2px 6px; border-radius: 3px; }
        article pre code { font-size: 0.85em; background: none; padding: 0; border-radius: 0; }
        article blockquote { border-left: 6px solid #ccc; padding-left: 16px; color: #666; margin: 16px 0; }
        article img { max-width: 100%; }
        article table { margin-bottom: 48px; }
        article ul, article ol { margin: 0 0 16px 24px; }
        .pagination { text-align: center; margin-top: 24px; }
        .pagination a, .pagination span { display: inline-block; padding: 6px 14px; margin: 0 2px; border: 2px solid #ddd; border-radius: 4px; color: #333; text-decoration: none; font-size: 0.9rem; }
        .pagination span { background: #0066cc; color: #fff; border-color: #0066cc; }
        .pagination a:hover { background: #f0f0f0; }
        footer { padding: 32px 0; color: #999; font-size: 0.85rem; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 600; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="file"] { width: 100%; padding: 8px 12px; border: 2px solid #ccc; border-radius: 4px; font-size: 1rem; }
        .btn { display: inline-block; padding: 8px 20px; border: none; border-radius: 4px; font-size: 0.95rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #0066cc; color: #fff; }
        .btn-primary:hover { background: #0052a3; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 4px 12px; font-size: 0.85rem; }
        .flash { padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; }
        .flash-success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .flash-error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 10px 14px; border-bottom: 2px solid #e0e0e0; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; }
        .login-box { max-width: 400px; margin: 60px auto; background: #fff; padding: 32px; border: 2px solid #e0e0e0; border-radius: 6px; }
        .login-box h1 { font-size: 1.4rem; margin-bottom: 24px; text-align: center; }
        .admin-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 1.4rem; }
        .back-link { margin-bottom: 24px; }
        .back-link a { color: #0066cc; text-decoration: none; font-size: 0.95rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="<?= h(SITE_URL) ?>"><?= h(SITE_TITLE) ?></a></h1>
        </div>
    </header>
    <div class="container">
        <main>
