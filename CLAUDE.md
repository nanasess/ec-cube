# Worktree Context

This directory was created by `worktree create cross-product-discount` as a working worktree.

- **Task name**: cross-product-discount
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/cross-product-discount
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/cross-product-discount`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/cross-product-discount`) to verify changes.

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

# 実装計画: Aの商品をカートに入れたらBの商品が割引になる機能（セット割引）

## 概要

同一注文内で商品Aと商品Bが両方カートに入っている場合、商品Bに自動的に割引を適用する。
例: 花嫁衣装のレンタルサービスで、花嫁衣装をカートに入れると紋付のレンタルが半額になる。

**重要**: 購入履歴ベースではなく、同一カート内の商品組み合わせによるリアルタイム割引。

## 実装チェックリスト

### Phase 1: エンティティ・DB

- [ ] `app/Customize/Entity/CrossProductDiscount.php` を新規作成
  - テーブル: `dtb_cross_product_discount`
  - カラム: id, trigger_product_id(FK→Product), target_product_id(FK→Product), discount_type(1=定額/2=定率), discount_value, enabled, create_date, update_date
  - trigger_product = カートに入っていることが条件となる商品（例: 花嫁衣装）
  - target_product = 割引対象となる商品（例: 紋付）
- [ ] `app/Customize/Repository/CrossProductDiscountRepository.php` を新規作成
  - `findEnabledRules(): array` - 有効なルール全件取得
  - `findEnabledRulesByProductIds(array $productIds): array` - カート内商品IDに関連するルールを取得
- [ ] `app/DoctrineMigrations/VersionXXXX.php` マイグレーション作成

### Phase 2: DiscountProcessor（カート内セット割引）

- [ ] `app/Customize/Service/PurchaseFlow/Processor/CrossProductDiscountProcessor.php` を新規作成
  - `DiscountProcessor` インターフェースを実装
  - `removeDiscountItem()`: processor_name が自クラスFQCNの明細を削除
  - `addDiscountItem()`: 以下のロジックで割引を適用
    1. カート/注文内の全商品明細（`OrderItemType::PRODUCT`）から商品IDリストを取得
    2. `CrossProductDiscountRepository` でそれらの商品IDに関連する有効ルールを取得
    3. 各ルールについて、trigger_product と target_product の**両方**がカート内に存在するか確認
    4. 両方存在する場合、target_product の明細の価格から割引額を計算
    5. `OrderItemType::DISCOUNT` の明細を追加（商品名に「セット割引: 紋付」等を表示）
  - 割引明細は `TaxType::NON_TAXABLE`（不課税）
  - 合計金額が0円以下にならないよう調整
  - trigger_product が複数個でも割引は1回のみ適用（重複割引防止）
- [ ] `app/Customize/Resource/config/services.yaml` にタグ登録
  - `eccube.discount.processor`, flow_type: `shopping` と `cart` の両方に登録（priority: 900）
  - **cartフローにも登録**することで、カート画面でもセット割引の金額が見える

### Phase 3: 管理画面

- [ ] `app/Customize/Controller/Admin/CrossProductDiscountController.php` を新規作成
  - `EccubeNav` 実装（商品管理 > セット割引設定）
  - CRUD操作（一覧/新規/編集/削除）
- [ ] `app/Customize/Form/Type/Admin/CrossProductDiscountType.php` を新規作成
  - triggerProduct: EntityType（商品選択）- 「この商品がカートにあると」
  - targetProduct: EntityType（商品選択）- 「この商品が割引される」
  - discountType: ChoiceType（定額/定率）
  - discountValue: NumberType（例: 50 = 50%引き or 50円引き）
  - enabled: CheckboxType
- [ ] `app/template/admin/Product/cross_product_discount.twig` を新規作成
  - セット割引ルール一覧と編集フォーム
  - わかりやすい表示: 「花嫁衣装 → 紋付 50%OFF」

### Phase 4: フロント画面での割引表示（任意）

- [ ] 商品詳細ページへのセット割引案内表示（EventSubscriber）
  - 例: 「花嫁衣装と一緒にご注文いただくと50%OFF！」
- [ ] カート画面での割引適用メッセージ
  - DiscountProcessor が cart フローにも登録されていれば、割引明細が自動表示される

## 関連ファイル（参照のみ）

- `src/Eccube/Service/PurchaseFlow/Processor/PointProcessor.php` - DiscountProcessor実装パターン
- `src/Eccube/Service/PointHelper.php` - removePointDiscountItem のパターン
- `app/config/eccube/packages/purchaseflow.yaml` - PurchaseFlow設定
- `src/Eccube/Entity/OrderItem.php` - OrderItemType::PRODUCT / DISCOUNT の判別

## 注意事項

- `OrderItem.processor_name` に `CrossProductDiscountProcessor::class` を設定して識別
- 会員/非会員の区別なく適用（カート内の商品組み合わせのみで判定）
- trigger_product をカートから削除した場合、次回の PurchaseFlow 実行時に割引明細も自動削除される（removeDiscountItem → addDiscountItem の再評価）
- 同一商品がtriggerとtargetの両方になるルールは作成不可とするバリデーション
- 複数ルールが同一 target_product に適用される場合は全て適用（重複適用可）
