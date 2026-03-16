# app/orders - セキュリティ強化版

既存コードの重大なセキュリティ問題を解決した新バージョンです。

## 解決した問題

### 重大
1. **認証情報のハードコード** → 環境変数（.env）から読み込み
2. **SSL証明書検証の無効化** → 有効化（CURLOPT_SSL_VERIFYPEER=true）
3. **CSRF対策の欠如** → トークンによる検証を追加
4. **アクセス制御の欠如** → セッションによる認証・タイムアウト（30分）

### 中程度
5. **エラー表示の有効化** → 環境変数（DEBUG_DISPLAY_ERRORS, APP_ENV）で制御、本番は無効
6. **デバッグモードの有効化** → 環境変数（DEBUG_FLG, APP_ENV）で制御、本番は無効
7. **XSS対策の不足** → 動的出力に htmlspecialchars を適用

## セットアップ

1. プロジェクトルートに `.env` を作成（`.env.example` をコピー）

```bash
cp .env.example .env
```

2. `.env` を編集し、以下を設定

```
RAKUTEN_SECRET_KEY=SP244394_xxxxxxxx
ADMIN_USERNAME=weaver
ADMIN_PASSWORD=あなたのパスワード
```

3. `app/orders/data/license_key.txt` に楽天ライセンスキーを配置  
   または `.env` に `RAKUTEN_LICENSE_KEY=` を設定

4. Google Cloud Console の「承認済みのリダイレクト URI」に以下を追加

```
https://your-domain.com/app/orders/
https://your-domain.com/app/orders/order_book.php
```

## アクセス

- サンプル注文処理: `/app/orders/` または `/app/orders/index.php`
- 通常注文処理: `/app/orders/order_book.php`
- ライセンス更新: `/app/orders/update_license.php`

## 注意

- `vendor/`、`client_secret.json`、`remarks.txt` はプロジェクトルートのものを共有して使用します
- トークンファイル（token.json 等）は `app/orders/` 配下に別途作成されます
