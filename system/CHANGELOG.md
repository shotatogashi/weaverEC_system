# 変更履歴

## [1.0.0] - 2025-03-16

### 追加

- **app/orders ディレクトリ** - セキュリティ強化版の新規作成（既存コードは変更なし）

### 重大なセキュリティ修正（app/orders）

- **認証情報のハードコード対策**: 楽天APIシークレットキー・管理画面認証を環境変数（.env）から読み込み
- **SSL証明書検証の有効化**: `lib.php` の `CURLOPT_SSL_VERIFYPEER` を true に変更
- **CSRF対策**: `update_license.php` にトークン検証を追加
- **アクセス制御**: ライセンス更新フォームにセッション認証・30分タイムアウトを追加

### 中程度のセキュリティ修正（app/orders）

- **エラー表示の制御**: `display_errors` を環境変数（DEBUG_DISPLAY_ERRORS, APP_ENV）で制御、本番は無効
- **デバッグモードの制御**: `debug_flg` を環境変数（DEBUG_FLG, APP_ENV）で制御、本番は無効
- **XSS対策**: 動的出力に `htmlspecialchars` を適用（index.php, order_book.php, preprocess.php, lib.php, logout.php, last_month.php）

### 修正

- **.env パス**: `APP_ORDERS_PARENT` を `dirname(__DIR__, 2)` に変更し、プロジェクトルート（www/）を正しく参照するように修正

### 新規ファイル

- `app/orders/setting.php` - 環境変数対応の設定
- `app/orders/lib.php` - SSL検証有効化済み
- `app/orders/update_license.php` - CSRF・セッション認証対応
- `app/orders/index.php`, `order_book.php`, `logout.php`
- `app/orders/inc/preprocess.php`, `inc/last_month.php`
- `.env.example` - 環境変数のテンプレート
- `.env` - 既存コードから生成（.gitignore で除外）

### .gitignore 追加

- `.env`, `.env.local`, `.env.*.local`
- `app/orders/token*.json`, `app/orders/data/license_key.txt`
- `.vscode/sftp.json`
