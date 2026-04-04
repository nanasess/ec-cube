# Worktree Context

This directory was created by `worktree create suggest-feature` as a working worktree.

- **Task name**: suggest-feature
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/suggest-feature
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/suggest-feature`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/suggest-feature`) to verify changes.

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

# 実装計画: フロント画面のサジェスト機能

## 概要

フロント画面のヘッダー検索欄にオートコンプリート（サジェスト）機能を追加する。キーワード入力に応じて商品候補をドロップダウン表示し、クリックで商品詳細に遷移できるようにする。

## 実装チェックリスト

### 1. サジェスト用APIコントローラの作成

- [ ] `app/Customize/Controller/ProductSuggestController.php` を新規作成
  - `AbstractController` を継承
  - Route: `GET /products/suggest` (name: `product_suggest`)
  - パラメータ: `keyword` (string), `category_id` (int, optional)
  - `ProductRepository::getQueryBuilderBySearchData()` を再利用してクエリ構築
  - `setMaxResults(10)` で最大10件に制限
  - JSONレスポンス: `[{id, name, price, image, url}]`
  - 空キーワード or 2文字未満は空配列を返す
  - `BaseInfo::isOptionNostockHidden()` で在庫なし非表示に対応

### 2. サジェスト用JavaScriptの実装

- [ ] `html/user_data/assets/js/customize.js` に追記
  - `.search-name` 入力欄に `input` イベントリスナーをバインド
  - 300msデバウンス処理
  - `$.ajax()` で `/products/suggest?keyword=xxx` にGETリクエスト
  - カテゴリ選択中なら `category_id` も送信
  - 検索結果をドロップダウンリストとして表示
  - 上下キーで候補移動、Enterで選択、ESCで閉じる
  - フォーカス外れで非表示（mousedown対策で遅延クローズ）
  - XHRリクエストの abort 制御

### 3. サジェスト用CSSの実装

- [ ] `html/user_data/assets/css/customize.css` に追記
  - `.ec-headerSearch__keyword` に `position: relative`
  - `.ec-suggest` を `position: absolute; z-index: 9999`
  - ホバー/キーボード選択時のハイライト
  - レスポンシブ対応（SP時は幅を画面幅に合わせる）

### 4. テンプレートカスタマイズ（任意）

- [ ] `app/template/default/Block/search_product.twig` にオーバーライド
  - `autocomplete="off"` を検索入力欄に追加
  - `data-suggest-url="{{ path('product_suggest') }}"` を追加

## 関連ファイル（参照のみ）

- `src/Eccube/Controller/ProductController.php` - 商品一覧コントローラ
- `src/Eccube/Controller/Block/SearchProductController.php` - 検索ブロックコントローラ
- `src/Eccube/Repository/ProductRepository.php` - `getQueryBuilderBySearchData()`
- `src/Eccube/Resource/template/default/Block/search_product.twig` - 検索ブロックテンプレート
- `src/Eccube/Form/Type/SearchProductBlockType.php` - 検索フォーム型

## 注意事項

- XSS対策: JSでHTML生成時は `text()` メソッドを使い商品名等をエスケープ
- パフォーマンス: LIKE検索のため `setMaxResults(10)` が重要
- SP対応: ドロワー内検索フォームにも `.search-name` クラスで統一バインド
