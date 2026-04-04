# Worktree Context

This directory was created by `worktree create geolocation` as a working worktree.

- **Task name**: geolocation
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/geolocation
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/geolocation`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/geolocation`) to verify changes.

---

# EC-CUBE Development Guide

## Project Overview

EC-CUBE is Japan's leading open-source e-commerce platform. This is the 4.3 branch, built on Symfony 6.4 and PHP 8.1+.

- **Repository**: https://github.com/EC-CUBE/ec-cube
- **Documentation**: https://doc4.ec-cube.net/
- **License**: GPL-2.0 / proprietary dual license

## Technology Stack

- **PHP**: 8.1 / 8.2 / 8.3
- **Framework**: Symfony 6.4 (full-stack)
- **ORM**: Doctrine ORM 2.x, DBAL 3.x
- **Template**: Twig 3.8
- **Database**: PostgreSQL 12+ or MySQL 8.4
- **Frontend**: Sass (SCSS), webpack, jQuery
- **Testing**: PHPUnit (via symfony/phpunit-bridge), Codeception (E2E)
- **Static Analysis**: PHPStan
- **Code Style**: PHP-CS-Fixer

## Directory Structure

```
src/Eccube/           # Core application code
  Controller/         # HTTP controllers (admin and front)
  Entity/             # Doctrine ORM entities
  Repository/         # Doctrine repositories
  Service/            # Business logic services
    PurchaseFlow/     # Order processing pipeline
  Form/               # Symfony form types and extensions
  Event/              # Event subscribers
  EventListener/      # Event listeners
  Twig/               # Twig extensions and functions
  Plugin/             # Plugin management system
  Command/            # Symfony console commands
  Resource/
    doctrine/         # ORM mapping files (XML)
    template/         # Core Twig templates
    config/           # Service definitions

app/
  Customize/          # Project-specific customizations (safe from upgrades)
    Controller/
    Entity/
    Form/Extension/
    Repository/
    Service/
    Twig/
    Resource/template/
  Plugin/             # Installed plugins
  config/eccube/      # Application configuration (packages, routes, services)
  template/           # Template overrides
  DoctrineMigrations/ # Database migrations
  proxy/entity/       # Auto-generated entity proxy classes

html/                 # Web root / public document root
  template/
    admin/assets/     # Admin panel assets (CSS, JS, images)
    default/assets/   # Storefront assets

tests/
  Eccube/Tests/       # PHPUnit tests
```

## Development Commands

### Installation

```bash
# Docker (recommended)
docker compose -f docker-compose.yml -f docker-compose.pgsql.yml up -d

# Composer
composer create-project ec-cube/ec-cube ec-cube "4.3.x-dev" --keep-vcs
bin/console eccube:install
```

### Testing

```bash
# Run all unit tests
bin/phpunit

# Run a specific test file
bin/phpunit tests/Eccube/Tests/Web/ShoppingControllerTest.php

# Run tests matching a filter
bin/phpunit --filter testCompleteWithLogin
```

### Static Analysis

```bash
vendor/bin/phpstan analyse src --level=1
```

### Code Style

```bash
# Check for violations
vendor/bin/php-cs-fixer fix --dry-run --diff

# Auto-fix
vendor/bin/php-cs-fixer fix
```

### Building Assets

```bash
npm ci
npm run build          # Build Sass and JavaScript

# Docker environment
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.nodejs.yml run --rm -T nodejs npm ci
docker compose -f docker-compose.yml -f docker-compose.dev.yml -f docker-compose.nodejs.yml run --rm -T nodejs npm run build
```

### Cache Management

```bash
bin/console cache:clear
bin/console cache:warmup
```

### Database

```bash
bin/console doctrine:schema:update --dump-sql   # Preview SQL changes
bin/console doctrine:migrations:diff            # Generate migration
bin/console doctrine:migrations:migrate         # Run migrations
```

## Architecture

### PurchaseFlow (Order Processing Pipeline)

PurchaseFlow is the core order processing engine located in `src/Eccube/Service/PurchaseFlow/`. It processes orders through a pipeline of:

1. **ItemPreprocessor / ItemHolderPreprocessor**: Prepare items (calculate delivery fees, payment charges)
2. **ItemValidator / ItemHolderValidator**: Validate items (check stock, sale limits, payment totals)
3. **ItemHolderPostValidator**: Final validation after all processing
4. **PurchaseProcessor**: Execute purchase (reduce stock, award points, generate order numbers)
5. **DiscountProcessor**: Apply discounts

Configuration is in `app/config/eccube/packages/purchaseflow.yaml`.

### Event System

EC-CUBE extends Symfony's EventDispatcher for customization:

- **Template Events**: Inject content into specific template locations (e.g., `Event/EccubeEvents.php`)
- **Controller Events**: Modify request/response in controller lifecycle
- **Entity Events**: Doctrine lifecycle callbacks

### Plugin System

Plugins are self-contained packages in `app/Plugin/{PluginCode}/`:

- Each plugin has `PluginManager.php` for install/uninstall/enable/disable lifecycle hooks
- Plugins can add entities, controllers, forms, templates, and event subscribers
- Plugin metadata is defined in `composer.json` within the plugin directory

### Customization via app/Customize

All project-specific code should go in `app/Customize/` to survive core upgrades:

- **Entity extensions**: Use Doctrine traits to add fields to existing entities
- **Form extensions**: Use Symfony FormTypeExtension to add fields to existing forms
- **Template overrides**: Place templates in `app/template/` to override core templates
- **Service overrides**: Use Symfony service decoration or compiler passes

### Entity Proxy System

EC-CUBE uses a proxy system for entities in `app/proxy/entity/`. When plugins or customizations add traits to entities, the proxy generator creates extended entity classes. Run `bin/console eccube:generate:proxies` to regenerate.

## Coding Conventions

- Follow PSR-12 coding style (enforced by PHP-CS-Fixer)
- Use PHP type declarations for parameters and return types
- Entity classes use Doctrine XML mapping (`src/Eccube/Resource/doctrine/`)
- Controllers extend `Eccube\Controller\AbstractController`
- Form types extend `Symfony\Component\Form\AbstractType`
- Repositories extend `Eccube\Repository\AbstractRepository`
- Use `@Route` annotations for routing
- Template files use `.twig` extension and follow Twig coding standards
- Admin templates are in `Resource/template/admin/`, storefront in `Resource/template/default/`

## Key Entities

- `Customer` — Registered customer
- `Product` / `ProductClass` — Products and their variations (size, color)
- `Order` / `OrderItem` — Orders and line items
- `Shipping` — Shipping information (multiple per order supported)
- `Cart` / `CartItem` — Shopping cart
- `Member` — Admin user
- `Plugin` — Installed plugin metadata
- `BaseInfo` — Store configuration (shop name, address, tax settings)

---

# 実装計画: フロント画面の商品ページでユーザーの位置情報(都道府県)を表示する

## 概要

商品詳細ページにユーザーのIPアドレスから推定した都道府県を表示する（例:「あなたは愛知県から閲覧しています」）。送料の見積もり表示にも活用する。

## 設計方針

サーバーサイドIPジオロケーション方式を採用。JavaScript Geolocation APIはユーザー許可ダイアログが出るためUXが悪い。GeoIP2（MaxMind GeoLite2）のPHPライブラリを使用する。

## 実装チェックリスト

### 1. GeoIP2ライブラリの導入

- [ ] `composer require geoip2/geoip2` を実行
- [ ] MaxMindからGeoLite2-City.mmdbをダウンロードし `app/Customize/Resource/geodata/GeoLite2-City.mmdb` に配置

### 2. GeoLocationService の作成

- [ ] `app/Customize/Service/GeoLocationService.php` を新規作成
  - IPアドレスから `Pref` エンティティを返す `getPrefFromIp(string $ip): ?Pref`
  - GeoLite2の英語都道府県名(例: "Aichi") → EC-CUBEの日本語名(例: "愛知県") のマッピング配列を定義
  - `PrefRepository::findOneBy(['name' => '愛知県'])` で Pref エンティティ取得
  - 例外時（ファイル未配置、ローカルIP等）は null を返しログ出力

### 3. EventSubscriber の作成

- [ ] `app/Customize/EventListener/ProductDetailEventSubscriber.php` を新規作成
  - `EventSubscriberInterface` を実装
  - `'Product/detail.twig'` テンプレートイベントをリッスン
  - `$request->getClientIp()` でIPを取得し、GeoLocationServiceで都道府県推定
  - `$event->setParameter('geo_pref', $pref)` でテンプレートに変数注入
  - `$event->setParameter('prefs', $prefRepository->findAll())` で都道府県一覧も注入
  - `$event->getSource()` を書き換えてスニペットテンプレートを挿入

### 4. 送料見積もりAPIコントローラの作成

- [ ] `app/Customize/Controller/ShippingEstimateController.php` を新規作成
  - Route: `GET /api/shipping_estimate/{pref_id}` (name: `shipping_estimate`)
  - `DeliveryFeeRepository::findBy(['Pref' => $Pref])` で送料一覧取得
  - JSONレスポンス: `{pref_name, fees: [{delivery_name, fee}]}`

### 5. テンプレートスニペットの作成

- [ ] `app/Customize/Resource/template/default/Product/detail_geo_location.twig` を新規作成
  - 都道府県推定結果の表示（「あなたは○○県から閲覧しています」）
  - 都道府県ドロップダウン（手動変更用、送料見積もりの送付先指定）
  - fetch APIで送料見積もりを取得・表示
  - 初期表示時に自動取得

### 6. Twig名前空間設定

- [ ] `app/config/eccube/packages/twig.yaml` に `@Customize` 名前空間の確認/追加

## 関連ファイル（参照のみ）

- `src/Eccube/Controller/ProductController.php` - 商品詳細アクション（222行目）
- `src/Eccube/Resource/template/default/Product/detail.twig` - 挿入位置（349行目 `{# 商品コード #}`）
- `src/Eccube/Entity/DeliveryFee.php` - Delivery + Pref の組み合わせで送料決定
- `src/Eccube/Event/TemplateEvent.php` - setParameter, getSource/setSource, addSnippet

## 注意事項

- GeoLite2-City.mmdb は約60MB、Gitに含めないこと
- ローカル開発では `127.0.0.1` でジオロケーション不可。環境変数 `ECCUBE_DEFAULT_PREF_ID=23` 等でデフォルト都道府県を設定する仕組みが必要
- `trusted_proxies` 設定の確認（プロキシ/CDN環境）

---

# 使用方法

## セットアップ

### 1. GeoIP2 ライブラリのインストール（任意）

```bash
composer require geoip2/geoip2
```

未インストールでも動作します（デフォルト都道府県にフォールバック）。

### 2. GeoLite2 データベースの配置（任意）

MaxMind のアカウント（無料）を作成し、GeoLite2-City.mmdb をダウンロードして配置:

```
app/Customize/Resource/geodata/GeoLite2-City.mmdb
```

未配置でも動作します（デフォルト都道府県にフォールバック）。

### 3. ローカル開発用のデフォルト都道府県設定

ローカル環境では IP がプライベートアドレスのため GeoIP が動作しません。
`.env` に以下を追加するとデフォルト都道府県が使われます:

```bash
# 愛知県 = 23
ECCUBE_DEFAULT_PREF_ID=23
```

都道府県IDの一覧は `mtb_pref` テーブルを参照（1=北海道, 13=東京都, 23=愛知県, 27=大阪府 等）。

### 4. キャッシュクリア

```bash
bin/console cache:clear
```

## 動作の仕組み

1. 商品詳細ページにアクセスすると `ProductDetailEventSubscriber` がテンプレートイベントをフック
2. `GeoLocationService` がクライアントIPから都道府県を推定
3. テンプレートに「**あなたは○○県から閲覧しています**」と表示
4. 都道府県ドロップダウンで手動変更も可能
5. 選択された都道府県に基づき、`/api/shipping_estimate/{pref_id}` APIを fetch で呼び出して送料見積もりを表示

## GeoLite2 なしでの動作

GeoIP2 ライブラリやデータベースファイルがなくてもサイトは正常に動作します:

- ライブラリ未インストール → デフォルト都道府県 or 表示なし
- DBファイル未配置 → 同上
- ローカルIP → 同上

`ECCUBE_DEFAULT_PREF_ID` を設定しておけば、GeoLite2 なしでも都道府県表示と送料見積もりの UI を確認できます。

## デモ手順

```bash
# .env にデフォルト都道府県を追加（愛知県）
echo "ECCUBE_DEFAULT_PREF_ID=23" >> .env

# キャッシュクリア
bin/console cache:clear

# ブラウザで商品詳細ページにアクセス
# → 「あなたは愛知県から閲覧しています」+ 送料見積もりが表示される
```
