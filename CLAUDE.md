# Worktree Context

This directory was created by `worktree create sale-price` as a working worktree.

- **Task name**: sale-price
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/sale-price
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/sale-price`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/sale-price`) to verify changes.

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

# 実装計画: セール価格の期間限定価格設定

## 概要

カテゴリ単位でセール価格（割引率 or 固定値引き）を期間指定して一括適用する機能。管理画面のボタン一つでセール適用/解除を行う。ProductClassの `price02` は直接書き換えず、新規テーブルでセール定義を管理し動的に計算する。

## 実装チェックリスト

### Phase 1: エンティティ・DB

- [ ] `app/Customize/Entity/SaleConfig.php` を新規作成
  - テーブル: `dtb_sale_config`
  - カラム: id, name, discount_type(1=割引率%/2=固定値引き円), discount_value, start_date, end_date, enabled, create_date, update_date
  - Category と ManyToMany（中間テーブル `dtb_sale_config_category`）
  - `isActive(): bool` - enabled && 期間内か判定
  - `calcSalePrice(string $originalPrice): string` - 割引後価格計算
- [ ] `app/Customize/Entity/ProductClassTrait.php` を新規作成
  - `@EntityExtension("Eccube\Entity\ProductClass")`
  - 非永続フィールド: `sale_price`, `appliedSaleConfig`, `isOnSale()`, `getEffectivePrice02()`
- [ ] `app/DoctrineMigrations/VersionXXXX.php` マイグレーション作成
- [ ] `bin/console eccube:generate:proxies` 実行

### Phase 2: リポジトリ・サービス

- [ ] `app/Customize/Repository/SaleConfigRepository.php` を新規作成
  - `findActiveSales()`, `findActiveSaleForProductClass()`
- [ ] `app/Customize/Service/SalePriceService.php` を新規作成
  - `getSalePrice(ProductClass $pc): ?string`
  - `getSalePriceAt(ProductClass $pc, \DateTimeInterface $date): ?string` - 指定日時点のセール価格（受注編集時用）
  - リクエストスコープキャッシュでN+1問題を回避

### Phase 3: Doctrine EventSubscriber

- [ ] `app/Customize/EventSubscriber/SalePriceEventSubscriber.php` を新規作成
  - Doctrine `postLoad` イベントで ProductClass にセール価格を自動セット

### Phase 4: PurchaseFlow

- [ ] `app/Customize/Service/PurchaseFlow/Processor/SalePricePreprocessor.php` を新規作成
  - `ItemHolderPreprocessor` 実装。TaxProcessorより前（priority: 1100）でセール価格適用
  - **フロント購入フロー時**: 現在のセール価格を適用
  - **管理画面受注編集時**: OrderItemに記録済みの価格を尊重し、Preprocessorでは上書きしない（後述の受注編集対応を参照）
- [ ] `app/Customize/Resource/config/services.yaml` にタグ登録

### Phase 5: 管理画面

- [ ] `app/Customize/Form/Type/Admin/SaleConfigType.php` を新規作成
- [ ] `app/Customize/Controller/Admin/SaleConfigController.php` を新規作成
  - `EccubeNav` 実装（商品管理 > セール管理）
  - CRUD + 有効/無効トグル
- [ ] `app/template/admin/Product/sale_index.twig` - セール一覧
- [ ] `app/template/admin/Product/sale_edit.twig` - セール編集

### Phase 6: フロント画面

- [ ] `app/template/default/Product/detail.twig` をオーバーライド
  - 通常価格を取り消し線 + セール価格を赤字表示
- [ ] `app/template/default/Product/list.twig` をオーバーライド
- [ ] `app/Customize/Twig/Extension/SalePriceExtension.php` - class_categories_as_json のセール価格対応

### Phase 7: 返品・キャンセル・受注編集への対応

#### 返品・キャンセル

- 返品・キャンセル自体は**追加対応不要**
- OrderItem にはセール価格が既に記録されているため、そのまま返品/キャンセル処理される
- ポイントやクーポンのような「付与の巻き戻し」は発生しない（セール価格は単に注文時の価格）

#### セール終了後の受注編集（重要）

- [ ] `SalePricePreprocessor` で管理画面とフロントを区別する
  - `PurchaseContext::isAdminOrder()` や `RequestStack` でコンテキストを判定
  - **管理画面受注編集時**: セール価格の自動適用を**スキップ**する
  - 理由: セール終了後に受注編集すると、セール価格が消えて通常価格に戻ってしまうため
  - 管理者が意図的に価格変更する場合は、手動でOrderItemの金額を編集する

- [ ] `SalePriceEventSubscriber` (Doctrine postLoad) での対応
  - 管理画面の受注編集コンテキストでは、ProductClass のセール価格セットを**行わない**か、セール終了後は通常価格をセットする
  - これにより `PriceChangeValidator` が「価格が変更されました」と誤判定するのを防ぐ

#### PriceChangeValidator との整合

- [ ] `PriceChangeValidator` への対応方針
  - フロント購入時: ProductClass に `postLoad` でセール価格がセットされているため、OrderItem の price とProductClass の price02（=セール価格）が一致し、警告は出ない
  - セール終了後の受注編集時: Preprocessor がスキップするため、OrderItem の price（セール時の価格）がそのまま維持される。PriceChangeValidator は「ProductClass.price02（通常価格）≠ OrderItem.price（セール価格）」を検出して警告を出す可能性がある
  - **対応案A**: PriceChangeValidator をサービスデコレーションで拡張し、セール履歴を持つOrderItemの場合はスキップ
  - **対応案B**: OrderItem にセール適用フラグ（or セールID）を記録し、PriceChangeValidator で参照
  - **対応案C（推奨）**: 受注編集時の価格変更警告は管理者への通知として有用なため、あえてそのまま残す。管理者が確認の上で保存する運用とする

#### OrderItemへのセール情報記録

- [ ] `app/Customize/Entity/OrderItemTrait.php` を新規作成（任意だが推奨）
  - `@EntityExtension("Eccube\Entity\OrderItem")`
  - `sale_config_id` (nullable int) - 適用されたセールのID
  - `original_price` (nullable decimal) - セール適用前の元の販売価格
  - これにより、受注明細からどのセールが適用されたか、元の価格がいくらだったかを追跡可能
  - 返品時の返金額計算、売上分析、セール効果測定にも活用できる
- [ ] プロキシ再生成 & マイグレーション追加

## 関連ファイル（参照のみ）

- `src/Eccube/Entity/ProductClass.php` - price02 フィールド
- `src/Eccube/Doctrine/EventSubscriber/TaxRuleEventSubscriber.php` - postLoad パターン参考
- `src/Eccube/Service/PurchaseFlow/Processor/PriceChangeValidator.php` - 価格変更検知バリデータ
- `src/Eccube/Controller/Admin/Order/EditController.php` - 受注編集時のPurchaseFlow呼び出し
- `app/config/eccube/packages/purchaseflow.yaml` - PurchaseFlow設定

## 受注ライフサイクルとセール価格

```
[フロント購入時（セール中）]
  → postLoad: ProductClass.sale_price = セール価格
  → SalePricePreprocessor: OrderItem.price = セール価格を適用
  → OrderItem に sale_config_id, original_price を記録
  → 注文確定: OrderItem.price にセール価格が永続化される

[返品/キャンセル]
  → OrderItem に記録されたセール価格のまま処理される
  → 追加対応不要

[セール終了後に管理画面で受注編集]
  → SalePricePreprocessor: 管理画面コンテキストではスキップ
  → OrderItem.price はセール時の価格のまま維持
  → PriceChangeValidator が警告を出す可能性あり（通常価格≠セール価格）
  → 管理者が確認の上で保存（運用で対応）

[セール中に管理画面で受注編集]
  → SalePricePreprocessor: 管理画面コンテキストではスキップ
  → 管理者が手動で変更した価格を尊重
```

## 注意事項

- price02 を直接書き換えない設計。セール終了時に自動で元の価格に戻る
- 複数セール重複時は最も割引額が大きいものを適用
- 管理画面受注編集時はセール価格の自動適用をスキップして管理者の裁量を尊重
- OrderItem に `sale_config_id` / `original_price` を記録して追跡可能性を確保
