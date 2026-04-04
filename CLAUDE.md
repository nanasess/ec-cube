# Worktree Context

This directory was created by `worktree create private-product` as a working worktree.

- **Task name**: private-product
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/private-product
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/private-product`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/private-product`) to verify changes.

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

# 実装計画: 特定のお客様専用URLの商品ページ（限定公開）

## 概要

商品を「非公開」のままにしつつ、秘密トークン付きURLを知っている顧客だけがその商品ページを閲覧・購入できる仕組みを構築する。

## 実装チェックリスト

### 1. エンティティ: PrivateLink の作成

- [ ] `app/Customize/Entity/PrivateLink.php` を新規作成
  - テーブル: `dtb_private_link`
  - カラム: id, product_id(FK→Product), customer_id(nullable,FK→Customer), token(unique,64文字), expires_at(nullable), is_active(default:true), create_date, update_date
  - customer_idがNULL=トークンで誰でも閲覧可、値あり=特定顧客のみ
  - トークン生成: `bin2hex(random_bytes(32))`

### 2. リポジトリ

- [ ] `app/Customize/Repository/PrivateLinkRepository.php` を新規作成
  - `findValidByToken(string $token): ?PrivateLink` - トークン検索（有効期限・is_active確認）
  - `findByProduct(Product $product): array`
  - `generateUniqueToken(): string`

### 3. DBマイグレーション

- [ ] `app/DoctrineMigrations/VersionXXXX.php` マイグレーション作成

### 4. フロント用コントローラ

- [ ] `app/Customize/Controller/PrivateLinkController.php` を新規作成
  - `GET /products/private/{token}` (name: `product_private_link`) - 商品詳細表示
  - `POST /products/private/{token}/add_cart` (name: `product_private_link_add_cart`) - カート追加
  - トークン検証 → 無効なら404、customer_id設定ありなら顧客一致チェック
  - 非公開商品でもトークン経由なら表示許可（既存 `checkVisibility()` をバイパス）
  - 既存の `Product/detail.twig` テンプレートを再利用

### 5. 管理画面コントローラ

- [ ] `app/Customize/Controller/Admin/PrivateLinkController.php` を新規作成
  - `GET /%eccube_admin_route%/product/{id}/private_link` - リンク一覧
  - `POST` - 新規トークン発行（顧客指定・有効期限設定）
  - `DELETE` - リンク無効化

### 6. フォームタイプ

- [ ] `app/Customize/Form/Type/Admin/PrivateLinkType.php` を新規作成
  - Customer(EntityType,nullable), expires_at(DateTimeType,nullable), is_active(CheckboxType)

### 7. テンプレート

- [ ] `app/template/admin/Product/private_link.twig` - 限定公開リンク管理画面
- [ ] 商品編集画面へのUI挿入（EventSubscriber or テンプレートオーバーライド）

## 関連ファイル（参照のみ）

- `src/Eccube/Controller/ProductController.php` - `checkVisibility()` (503-519行目)
- `src/Eccube/Resource/template/admin/Product/product.twig` - 管理画面導線挿入箇所（920行目付近）

## 注意事項

- トークンは暗号論的に安全な乱数（`random_bytes(32)`）で生成
- 有効期限切れリンクはDB削除せず無効扱い
- Rate Limiterでブルートフォース対策を検討
