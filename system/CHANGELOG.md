# 変更履歴

## [1.0.2] - 2025-03-17

### 追加（app/orders 認証強化）

- **ログイン認証の追加** (`login.php`, `inc/auth.php`): 全ページへのアクセスにログインを必須化
- **管理者/一般ユーザの役割分離**: 管理者（ADMIN_USERNAME/PASSWORD）は update_license.php のみ、一般ユーザ（USER_USERNAME/PASSWORD）は index.php, order_book.php, sample_order_book.php にアクセス可能（管理者は全ページにアクセス可能）
- **セッションタイムアウト**: 1日（86400秒）で自動ログアウト
- **リダイレクト先の検証** (`login.php`): 許可リスト（index.php, order_book.php, sample_order_book.php, update_license.php）以外へのリダイレクトを防止

### 修正（app/orders）

- **index.php, order_book.php, sample_order_book.php**: `require_user_auth()` を追加し、未認証時はログイン画面へリダイレクト

### 新規ファイル

- `app/orders/login.php` - ログイン画面
- `app/orders/inc/auth.php` - 共通認証モジュール（require_admin_auth, require_user_auth）

---

## [1.0.1] - 2025-03-17

### 修正（app/orders 注文処理システムの安定性強化）

#### 高優先

- **楽天APIレスポンスの null チェック** (`lib.php`): API 応答が不正・null の場合のエラーハンドリングを追加
- **convert_order_info / convert_sample_order_info の null・空配列チェック** (`lib.php`): `PackageModelList`、`ItemModelList`、`SkuModelList`、`OrdererModel`、`PointModel` の存在チェックを追加。未定義の決済コード・remarks の null 対策
- **ファイル作成・削除のトランザクション的扱い** (`order_book.php`): 新規ファイル2件の作成が両方成功した場合のみ既存ファイルを削除。作成失敗時は既存ファイルを変更しない
- **preprocess.php の例外処理** (`inc/preprocess.php`): 401 以外の Google API 例外発生時に `exit` を追加し、続行による二重エラーを防止

#### 中優先

- **CSV 改行コードの正規化** (`order_book.php`): `\r\n` / `\r` を `\n` に統一し、処理済み判定のずれを防止
- **month パラメータの検証** (`inc/last_month.php`): `$_GET['month']` を整数化し、-12〜12 の範囲のみ許可
- **create_file / delete の例外チェック** (`order_book.php`): ファイル作成・削除の try-catch を追加し、エラー時に適切なメッセージを表示

---

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
