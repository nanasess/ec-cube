# Worktree Context

This directory was created by `worktree create out-of-stock-type` as a working worktree.

- **Task name**: out-of-stock-type
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/out-of-stock-type
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/out-of-stock-type`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/out-of-stock-type`) to verify changes.

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

# 実装計画: フロント画面の品切れ表示を欠品と廃盤に分ける

## 概要

現在「ただいま品切れ中です。」と一律表示されている品切れ表示を、「欠品（一時的な在庫切れ）」と「廃盤（恒久的に販売終了）」に分けて表示する。

## 設計方針

ProductClass に `out_of_stock_type` カラムを追加する方式。Entity Trait + FormExtension でコア変更不要。

品切れ種別: null/0=未設定（従来互換:欠品扱い）、1=欠品、2=廃盤

## 実装チェックリスト

### 1. Entity拡張（Trait）の作成

- [ ] `app/Customize/Entity/ProductClassTrait.php` を新規作成
  - `@EntityExtension("Eccube\Entity\ProductClass")` アノテーション
  - `out_of_stock_type` カラム（nullable smallint）
  - 定数: `OUT_OF_STOCK_TYPE_SHORTAGE = 1`, `OUT_OF_STOCK_TYPE_DISCONTINUED = 2`
- [ ] `app/Customize/Entity/ProductTrait.php` を新規作成
  - `@EntityExtension("Eccube\Entity\Product")` アノテーション
  - `getOutOfStockType(): ?int` - 全規格を走査し品切れ種別を集約（全廃盤→廃盤、1つでも欠品→欠品）

### 2. プロキシ生成 & DBマイグレーション

- [ ] `bin/console eccube:generate:proxies` を実行
- [ ] `app/DoctrineMigrations/VersionXXXX.php` マイグレーション作成
  - `dtb_product_class` に `out_of_stock_type` (SMALLINT, nullable) 追加
- [ ] `bin/console doctrine:migrations:migrate` を実行

### 3. 管理画面フォーム拡張

- [ ] `app/Customize/Form/Extension/ProductClassExtension.php` を新規作成
  - `ProductClassEditType` を拡張、`out_of_stock_type` を ChoiceType で追加
- [ ] `app/Customize/Form/Extension/ProductExtension.php` を新規作成（規格なし商品用）

### 4. フロントテンプレートのオーバーライド

- [ ] `app/template/default/Product/detail.twig` をオーバーライド
  - 384行目付近の品切れ表示ブロックで `Product.outOfStockType` を判定して表示分岐
- [ ] `app/template/default/Product/list.twig` をオーバーライド
  - 204-209行目付近の品切れ表示を同様に修正

### 5. 翻訳メッセージの追加

- [ ] `app/Customize/Resource/locale/messages.ja.yaml` を作成
  - `front.product.out_of_stock_shortage`: ただいま欠品中です。
  - `front.product.discontinued`: この商品は廃盤です。

## 関連ファイル（参照のみ）

- `src/Eccube/Entity/ProductClass.php` - `getStockFind()` の在庫判定ロジック
- `src/Eccube/Entity/Product.php` - `_calc()` と `getStockFind()` による商品レベルの在庫集約
- `src/Eccube/Resource/template/default/Product/detail.twig` - 品切れ表示（384-414行目）
- `src/Eccube/Resource/template/default/Product/list.twig` - 品切れ表示（176-210行目）
- `src/Eccube/Annotation/EntityExtension.php` - Entity拡張Traitのアノテーション

## 注意事項

- `Product._calc()` はコアメソッドでTraitオーバーライド不可。`getOutOfStockType()` は独立メソッドとして実装
- 規格選択ドロップダウンの「(品切れ中)」ラベル（116行目）はTwig Extension or JSで対応
- 廃盤の場合は構造化データ（JSON-LD）の `availability` を `Discontinued` に変更推奨
