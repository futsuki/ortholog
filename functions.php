<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Parsedown.php';

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function load_articles() {
    $cacheFile = CACHE_DIR . '/meta.json';

    $cache = @json_decode(@file_get_contents($cacheFile), true);
    if ($cache && isset($cache['max_mtime'])) {
        $files = glob(ARTICLES_DIR . '/*.md');
        $currentMaxMtime = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $currentMaxMtime) {
                $currentMaxMtime = $mtime;
            }
        }
        if ($currentMaxMtime <= $cache['max_mtime'] && count($files) === count($cache['articles'])) {
            return $cache['articles'];
        }
    }

    $files = glob(ARTICLES_DIR . '/*.md');
    $articles = [];
    $maxMtime = 0;

    foreach ($files as $file) {
        $mtime = filemtime($file);
        if ($mtime > $maxMtime) {
            $maxMtime = $mtime;
        }
        $slug = basename($file, '.md');
        $content = file_get_contents($file);
        $meta = parse_frontmatter($content);
        $bodyText = $meta['body'] ?? $content;
        $articles[] = [
            'slug' => $slug,
            'title' => $meta['title'] ?? $slug,
            'date' => $meta['date'] ?? date('Y-m-d', $mtime),
            'categories' => parse_categories($meta['category'] ?? ''),
            'excerpt' => mb_substr(trim(preg_replace('/[#*>`\[\]\(\)!_~\|]/', '', $bodyText)), 0, 200),
            'file' => $file,
            'mtime' => $mtime,
        ];
    }

    usort($articles, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    @file_put_contents($cacheFile, json_encode([
        'articles' => $articles,
        'max_mtime' => $maxMtime,
    ], JSON_UNESCAPED_UNICODE));

    return $articles;
}

function parse_frontmatter($content) {
    $meta = ['body' => $content];
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $m)) {
        $front = $m[1];
        $meta['body'] = $m[2];
        foreach (explode("\n", $front) as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $kv)) {
                $meta[$kv[1]] = trim($kv[2]);
            }
        }
    }
    return $meta;
}

function parse_categories($category) {
    if (empty($category)) {
        return [];
    }
    return array_map('trim', explode(',', $category));
}

function get_article($slug) {
    $file = ARTICLES_DIR . '/' . basename($slug) . '.md';
    if (!file_exists($file)) {
        return null;
    }
    $mtime = filemtime($file);
    $cacheFile = CACHE_DIR . '/' . basename($slug) . '.html';

    if (file_exists($cacheFile) && filemtime($cacheFile) >= $mtime) {
        $body = file_get_contents($cacheFile);
        $content = file_get_contents($file);
    } else {
        $content = file_get_contents($file);
        $meta = parse_frontmatter($content);
        $parsedown = new Parsedown();
        $body = $parsedown->text($meta['body'] ?? $content);
        @file_put_contents($cacheFile, $body);
    }

    $meta = parse_frontmatter($content);

    return [
        'slug' => $slug,
        'title' => $meta['title'] ?? $slug,
        'date' => $meta['date'] ?? date('Y-m-d', $mtime),
        'categories' => parse_categories($meta['category'] ?? ''),
        'body' => $body,
        'file' => $file,
        'mtime' => $mtime,
    ];
}

function delete_article($slug) {
    $file = ARTICLES_DIR . '/' . basename($slug) . '.md';
    if (file_exists($file)) {
        $result = unlink($file);
        @unlink(CACHE_DIR . '/meta.json');
        @unlink(CACHE_DIR . '/' . basename($slug) . '.html');
        return $result;
    }
    return false;
}

function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function redirect($path) {
    header('Location: ' . SITE_URL . $path);
    exit;
}
