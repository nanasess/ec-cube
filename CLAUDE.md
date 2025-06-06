# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

日本語で回答してください

## プロジェクト概要

EC-CUBEは日本の商習慣に特化したオープンソースのECプラットフォームです。Symfony 6.4ベースで構築され、高度なカスタマイズ性と拡張性を持っています。

## 開発環境のセットアップ

### 依存関係のインストール
```bash
# PHP依存関係
composer install

# JavaScript/CSS依存関係
npm ci
```

## よく使用するコマンド

### Docker 環境の利用

``` bash
# Docker 環境の起動(PostgreSQL)
export USER_ID=${UID} GROUP_ID=${GID}
docker compose -f docker-compose.yml -f docker-compose.pgsql.yml -f docker-compose.dev.yml up -d
```

#### フロント画面

http://localhost:8080

#### 管理画面

http://localhost:8080/admin

ID: admin
PASSWORD: password

### ビルドとアセット管理
```bash
# Sass/JavaScriptのビルド
npm run build

# 開発モード（ファイル監視）
npm run start

# JavaScriptのみビルド
npx webpack

# Docker環境でのビルド
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.nodejs.yml run --rm -T nodejs npm run build
```

### テストコマンド
```bash
# 全ユニットテスト実行
vendor/bin/phpunit

# 特定のテストメソッド実行
vendor/bin/phpunit --filter=<TestClass>::<TestMethod>

# E2Eテスト実行
vendor/bin/codecept run -d acceptance --env chrome <TestClass>::<TestMethod>

# コードスタイルチェック
vendor/bin/php-cs-fixer --config=.php-cs-fixer.dist.php --path-mode=intersection fix

# PHPStan静的解析
vendor/bin/phpstan analyse
```

### データベース操作
```bash
# データベース作成
bin/console doctrine:database:create

# スキーマ作成
bin/console doctrine:schema:create

# マイグレーション実行
bin/console doctrine:migrations:migrate

# テストデータ生成
bin/console eccube:fixtures:load

# ダミーデータ生成（テスト用）
bin/console eccube:fixtures:generate
```

### キャッシュクリア
```bash
bin/console cache:clear --no-warmup
bin/console cache:warmup
```

## アーキテクチャ概要

### ディレクトリ構造
- `src/Eccube/` - コアコード
  - `Controller/` - MVCコントローラー（管理画面は`Admin/`サブディレクトリ）
  - `Entity/` - Doctrineエンティティ（ドメインモデル）
  - `Repository/` - データアクセス層
  - `Service/` - ビジネスロジック
  - `Form/` - Symfonyフォーム定義
  - `Event/` - イベントクラス
  - `EventListener/` - イベントリスナー
- `app/` - アプリケーション設定
  - `Customize/` - カスタマイズコード
  - `Plugin/` - インストール済みプラグイン
  - `proxy/entity/` - エンティティプロキシ
- `html/` - 公開ディレクトリ（画像、CSS、JS）
- `var/` - キャッシュ、ログ、セッション

### 主要な設計パターン

1. **PurchaseFlow** - 購入フロー処理の中核
   - プロセッサーチェーンパターンで拡張可能
   - バリデーション、在庫確認、価格計算を管理

2. **イベントシステム** - Symfonyイベントディスパッチャー
   - プラグインやカスタマイズでの拡張ポイント
   - 主要イベント: `EccubeEvents::FRONT_*`、`EccubeEvents::ADMIN_*`

3. **サービス層** - ビジネスロジックの分離
   - `CartService` - カート操作
   - `OrderService` - 注文処理
   - `TaxRuleService` - 税計算（日本の消費税対応）

4. **プラグインシステム**
   - 独立したSymfonyバンドルとして実装
   - イベントフック、サービスタグで統合

### 開発時の注意点

- データベースはSQLite3（開発）、本番はPostgreSQL/MySQL
- ログは `var/log/dev/site-<YYYY-MM-DD>.log` に出力
- セッションベースのカート管理
- 多言語対応（`trans()`関数使用）
- 日本の商習慣対応（税計算、配送方法など）

### テスト環境

- PHPUnit - ユニットテスト（`tests/`ディレクトリ）
- Codeception - E2Eテスト（`codeception/`ディレクトリ）
- DAMA DoctrineTestBundle - テスト時のトランザクション管理

### コーディング規約

- PHP-CS-Fixer設定に従う（`.php-cs-fixer.dist.php`）
- Symfony標準に準拠
- ヘッダーコメント必須（ライセンス情報）
