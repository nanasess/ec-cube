# Worktree Context

This directory was created by `worktree create sales-report` as a working worktree.

- **Task name**: sales-report
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/sales-report
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/sales-report`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/sales-report`) to verify changes.

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

# 実装計画: 売上集計を発送日基準にする

## 概要

既存のダッシュボード売上集計は受注日（order_date）基準だが、発送日（shipping_date）基準の売上集計画面を新たに作成する。日別/月別/年別の集計、CSV出力に対応。

## 実装チェックリスト

### 1. コントローラとナビゲーション

- [ ] `app/Customize/Controller/Admin/SalesReportController.php` を新規作成
  - `AbstractController` を継承、`EccubeNav` インターフェースを実装
  - `getNav()` で「受注管理 > 売上集計」メニューを追加
  - `GET|POST /%eccube_admin_route%/sales_report` - 集計画面
  - `POST /%eccube_admin_route%/sales_report/csv` - CSV出力

### 2. フォームタイプ

- [ ] `app/Customize/Form/Type/Admin/SalesReportType.php` を新規作成
  - `date_type`: ChoiceType（出荷日 / お届け予定日）
  - `date_start` / `date_end`: DateType
  - `unit`: ChoiceType（日別/月別/年別）
  - `order_statuses`: EntityType（OrderStatus、複数選択）

### 3. 集計サービス

- [ ] `app/Customize/Service/SalesReportService.php` を新規作成
  - Shipping起点でOrderをJOINするQueryBuilder構築
  - `s.shipping_date` 基準の期間フィルタ
  - 1受注に複数出荷がある場合の重複排除（DISTINCT o.id でOrder単位集計）
  - 受注ステータスフィルタ（キャンセル/返品除外）
  - `shipping_date IS NOT NULL` で未発送を除外

### 4. テンプレート

- [ ] `app/template/admin/SalesReport/index.twig` を新規作成
  - `@admin/default_frame.twig` を継承
  - 検索フォーム、集計結果テーブル（日付/受注件数/売上合計）
  - Chart.js によるグラフ表示
  - CSV出力ボタン

### 5. CSV出力

- [ ] コントローラにCSV出力アクションを実装
  - `StreamedResponse` 使用
  - ヘッダ行: 日付, 受注件数, 売上合計
  - ファイル名: `sales_report_YYYYMMDD.csv`
  - 文字コード: SJIS（EC-CUBE慣例）

## 関連ファイル（参照のみ）

- `src/Eccube/Controller/Admin/AdminController.php` - 既存売上集計ロジック（getSalesByDay, getData）
- `src/Eccube/Entity/Shipping.php` - shipping_date / shipping_delivery_date フィールド
- `src/Eccube/Common/EccubeNav.php` - ナビゲーション拡張インターフェース
- `app/config/eccube/packages/eccube_nav.yaml` - 既存ナビ構造
- `src/Eccube/Service/CsvExportService.php` - CSV出力サービス参考

## 注意事項

- 1受注に複数出荷がある場合、`payment_total` が重複計上される。サブクエリまたは2段階クエリで重複排除が必要
- DQLではサブクエリのFROM句が使えないため、NativeQueryまたは2段階クエリで実装
- テンプレートは `app/template/admin/` に配置すれば `@admin` 名前空間で参照可能
