# Worktree Context

This directory was created by `worktree create naire-engraving` as a working worktree.

- **Task name**: naire-engraving
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/naire-engraving
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/naire-engraving`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/naire-engraving`) to verify changes.

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

# 実装計画: 名入れ機能（ノベルティなど）

## 概要

商品購入時にお客様が名入れテキストを入力できる機能。商品ごとに名入れ対応/非対応を設定可能。名入れ情報はカート→注文確認→受注詳細まで一貫して保持・表示される。

## 設計方針

CartItem の `__sleep()` は `['product_class_id', 'price', 'quantity']` のみ返すため、Traitでプロパティを追加してもセッション経由で失われる。名入れ情報はセッションに別キーで保持し、購入確定時に NaireInfo エンティティとして永続化する。

## 実装チェックリスト

### 1. 商品マスタ: 名入れ対応フラグ追加

- [ ] `app/Customize/Entity/ProductTrait.php` を新規作成
  - `@EntityExtension("Eccube\Entity\Product")` アノテーション
  - `naire_enabled` (boolean, default false)
  - `@FormAppend` アノテーションで管理画面に自動追加
- [ ] `bin/console eccube:generate:proxies` 実行
- [ ] マイグレーション作成（`dtb_product` に `naire_enabled` カラム追加）

### 2. 名入れ情報エンティティ

- [ ] `app/Customize/Entity/NaireInfo.php` を新規作成
  - テーブル: `dtb_naire_info`
  - カラム: id, naire_text(string,max255), create_date, update_date
  - OrderItem への ManyToOne リレーション
- [ ] `app/Customize/Entity/OrderItemTrait.php` を新規作成
  - `@EntityExtension("Eccube\Entity\OrderItem")`
  - NaireInfo への OneToOne リレーション
- [ ] `app/Customize/Repository/NaireInfoRepository.php` を新規作成
- [ ] プロキシ再生成 & マイグレーション

### 3. カートフォーム拡張

- [ ] `app/Customize/Form/Extension/AddCartTypeExtension.php` を新規作成
  - `AddCartType` を拡張、`naire_text` フィールド追加（TextType, mapped:false, required:false）
  - PRE_SET_DATA で `naire_enabled` が false の場合はフィールド除去
  - maxlength: 255 バリデーション

### 4. カート追加時のセッション保存

- [ ] `app/Customize/EventListener/AddCartNaireListener.php` を新規作成
  - `FRONT_PRODUCT_CART_ADD_COMPLETE` イベントをリッスン
  - セッションに `naire_info` キーで `[product_class_id => naire_text]` 保存

### 5. 購入確定時の永続化

- [ ] `app/Customize/Service/PurchaseFlow/Processor/NairePurchaseProcessor.php` を新規作成
  - `PurchaseProcessor` 実装
  - `prepare()`: セッションの名入れ情報を NaireInfo として作成・persist
  - `commit()`: セッションクリア
  - `rollback()`: NaireInfo 削除
- [ ] `app/config/eccube/packages/purchaseflow.yaml` に登録

### 6. テンプレート

- [ ] `app/template/default/Product/detail.twig` をオーバーライド
  - `{% if form.naire_text is defined %}` で名入れ入力欄を表示
- [ ] `app/template/default/Cart/index.twig` をオーバーライド
  - セッションから名入れ情報を取得して表示
- [ ] `app/template/default/Shopping/confirm.twig` をオーバーライド
  - `orderItem.NaireInfo` で名入れテキスト表示

### 7. 管理画面の受注詳細

- [ ] `app/Customize/Form/Extension/Admin/OrderItemTypeExtension.php` を新規作成
  - 受注明細に名入れ情報を読み取り専用で表示

## 関連ファイル（参照のみ）

- `src/Eccube/Entity/CartItem.php` - __sleep() によるセッションシリアライズ制約（92行目）
- `src/Eccube/Form/Type/AddCartType.php` - カートフォーム拡張の基盤
- `src/Eccube/Service/CartService.php` - addProduct/mergeCartItems のカート処理フロー
- `src/Eccube/Form/Extension/DoctrineOrmExtension.php` - @FormAppend による自動フォーム拡張
- `src/Eccube/Entity/OrderItem.php` - OrderItem エンティティ拡張先

## 注意事項

- CartItem の `__sleep()` 制約により、名入れ情報はCartItemに直接持たせられない
- CartItemComparator は商品規格IDのみで同一判定。同じ商品で異なる名入れは最後の入力が有効
- `@FormAppend` でProductの管理画面フォームに自動で名入れ対応チェックボックスが追加される
