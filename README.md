# ortholog

PHP だけで動くシンプルな Markdown ブログです。記事は `articles/` 配下の `.md` ファイルとして管理し、一覧表示、記事詳細表示、管理画面からのアップロード・編集・削除に対応しています。

## 主な機能

- Markdown 記事の表示
- YAML 風フロントマターによるタイトル、日付、カテゴリ管理
- 記事一覧のページネーション
- 管理画面での記事アップロード、編集、削除
- パスワード認証付き管理画面
- Parsedown による Markdown から HTML への変換
- 記事メタデータと HTML のファイルキャッシュ

## 必要環境

- PHP 7.4 以上
- PHP 拡張: `mbstring`
- Web サーバー、または PHP のビルトインサーバー

Composer やデータベースは不要です。

## セットアップ

1. リポジトリを配置します。

```bash
git clone <repository-url>
cd ortholog
```

2. `config.php` を環境に合わせて編集します。

```php
define('SITE_TITLE', 'Simple Blog');
define('SITE_URL', 'http://localhost:8000');
define('ADMIN_PASSWORD', 'password_hash で生成したハッシュ');
define('ITEMS_PER_PAGE', 10);
```

3. `articles/` と `cache/` を Web サーバーの実行ユーザーが書き込めるようにします。

4. PHP のビルトインサーバーで起動する場合は、プロジェクトルートで実行します。

```bash
php -S localhost:8000
```

5. ブラウザで `http://localhost:8000` を開きます。

## 管理画面

管理画面は `/admin/` です。

初期パスワードは `password` です。公開環境で使う前に必ず変更してください。

パスワードを変更する手順は次の通りです。

1. `/admin/` にログインします。
2. 「パスワード変更」から新しいパスワードのハッシュを生成します。
3. 生成されたハッシュを `config.php` の `ADMIN_PASSWORD` に設定します。

## 記事の書き方

記事は `articles/<slug>.md` として保存します。`<slug>` は記事 URL の `?id=<slug>` に使われます。

```markdown
---
title: 記事のタイトル
date: 2026-07-01
category: カテゴリ1, カテゴリ2
---

ここに Markdown 形式で本文を書きます。

## 見出し

本文...
```

フロントマターの項目は次の通りです。

| 項目 | 説明 |
| --- | --- |
| `title` | 記事タイトル。未指定の場合はファイル名が使われます。 |
| `date` | 記事の日付。一覧はこの値の降順で並びます。 |
| `category` | カンマ区切りのカテゴリ一覧です。 |

管理画面から `.md` ファイルをアップロードすることもできます。スラッグを省略した場合はファイル名から自動生成されます。

## ディレクトリ構成

```text
.
├── admin/          # 管理画面
├── articles/       # Markdown 記事
├── cache/          # 生成済みメタデータと HTML キャッシュ
├── template/       # 共通ヘッダーとフッター
├── config.php      # サイト設定
├── functions.php   # 記事読み込み、認証、共通関数
├── index.php       # 公開側トップと記事詳細
└── Parsedown.php   # Markdown パーサー
```

## キャッシュ

`cache/` には記事一覧用の `meta.json` と記事本文の HTML キャッシュが生成されます。記事ファイルの更新時刻が変わると自動的に再生成されます。

表示がおかしい場合や強制的に再生成したい場合は、`cache/` の中身を削除してください。

## デプロイ時の注意

- `SITE_URL` を公開 URL に合わせてください。
- `ADMIN_PASSWORD` を初期値から変更してください。
- `articles/` と `cache/` に書き込み権限を付与してください。
- 必要に応じて Web サーバー側で HTTPS、Basic 認証、IP 制限などを設定してください。
- `cache/` は生成物なので Git 管理しない運用を推奨します。

## ライセンス

MIT License
