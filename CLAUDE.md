# Worktree Context

This directory was created by `worktree create referral-coupon` as a working worktree.

- **Task name**: referral-coupon
- **Working directory**: /home/nanasess/git-repos/ec-cube.worktrees/referral-coupon
- **Project root (source)**: /home/nanasess/git-repos/ec-cube

> **Important**: All code changes must be made within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/referral-coupon`).
> Do not modify the project root (`/home/nanasess/git-repos/ec-cube`) directly.

## Testing

Run `docker compose up` or other commands within this directory (`/home/nanasess/git-repos/ec-cube.worktrees/referral-coupon`) to verify changes.

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

# 実装計画: 紹介クーポン機能

## 概要

購入完了時に紹介クーポンコードを発行し、被紹介者がそのクーポンを使用して購入完了したら、紹介者にポイント付与、被紹介者には注文時の値引きを適用する。

## 実装チェックリスト

### Phase 1: エンティティ・DB

- [ ] `app/Customize/Entity/ReferralCoupon.php` を新規作成
  - テーブル: `dtb_referral_coupon`
  - カラム: id, coupon_code(unique,12桁), referrer_id(FK→Customer), referee_id(nullable,FK→Customer), referee_order_id(nullable,FK→Order), discount_amount, referrer_point, referrer_point_granted(boolean,default:false ※ポイント付与済みフラグ), status(0:未使用/1:使用済/2:期限切れ/3:無効/4:返品取消), expires_at, used_at, create_date, update_date
- [ ] `app/Customize/Entity/ReferralConfig.php` を新規作成
  - テーブル: `dtb_referral_config`（値引額、付与ポイント、有効日数、上限枚数、有効/無効）
- [ ] `app/Customize/Repository/ReferralCouponRepository.php` を新規作成
  - `findByOrder(Order $order): ?ReferralCoupon` - 注文に紐づくクーポン取得
- [ ] `app/Customize/Repository/ReferralConfigRepository.php` を新規作成
- [ ] `app/DoctrineMigrations/VersionXXXX.php` マイグレーション作成

### Phase 2: 購入フロー（PurchaseFlow）

- [ ] `app/Customize/Service/PurchaseFlow/Processor/ReferralCouponDiscountProcessor.php` を新規作成
  - `DiscountProcessor` を実装。クーポン適用時に `OrderItemType::DISCOUNT` 明細を追加
  - 割引額は注文の数量や金額に依存しない**固定額**（ReferralConfigの設定値）
  - ただし注文合計が割引額より小さい場合は注文合計を上限とする
- [ ] `app/Customize/Service/PurchaseFlow/Processor/ReferralCouponPurchaseProcessor.php` を新規作成
  - `PurchaseProcessor` を実装
  - `prepare()`: クーポンステータスを「使用済」に変更、被紹介者・注文情報をクーポンに記録
  - `commit()`: 紹介者のポイント加算、`referrer_point_granted = true` に更新、紹介者へメール通知
  - `rollback()`: prepare の変更を元に戻す（クーポンを未使用に復元、被紹介者情報クリア）
- [ ] `app/Customize/Resource/config/services.yaml` にタグ登録
  - discount.processor: priority 900, purchase.processor: priority 650

### Phase 3: UI（購入画面）

- [ ] `app/Customize/Form/Extension/OrderTypeReferralCouponExtension.php` を新規作成
  - OrderType に `referral_coupon_code` フィールド追加（mapped: false）
- [ ] `app/Customize/Controller/ReferralCouponController.php` を新規作成
  - POST `/shopping/referral_coupon` - クーポン適用（セッション保存）
  - POST `/shopping/referral_coupon/remove` - クーポン解除

### Phase 4: クーポン発行

- [ ] `app/Customize/EventSubscriber/ReferralCouponEventSubscriber.php` を新規作成
  - `FRONT_SHOPPING_COMPLETE_INITIALIZE` イベントで購入完了時にクーポン自動発行

### Phase 5: 返品・キャンセル・受注編集への対応

- [ ] `app/Customize/EventSubscriber/ReferralCouponOrderStateSubscriber.php` を新規作成
  - `EventSubscriberInterface` を実装
  - 以下のワークフローイベントを購読:

  **返品時 (`workflow.order.transition.return`):**
  - 被紹介者の注文が返品された場合:
    1. `ReferralCoupon` を注文IDで検索
    2. 紹介者に付与済みのポイントを回収（`referrer_point_granted` が true の場合のみ）
       - `$referrer->setPoint($referrer->getPoint() - $coupon->getReferrerPoint())`
    3. クーポンステータスを `4:返品取消` に更新
    4. `referrer_point_granted = false` に戻す
    5. ※クーポン自体を「未使用」に戻すかどうかは運用判断（計画では戻さない=再利用不可）

  **キャンセル時 (`workflow.order.transition.cancel`):**
  - 返品時と同じ処理。紹介者ポイント回収 + クーポンステータスを `4:返品取消` に更新

  **返品取消時 (`workflow.order.transition.cancel_return`):**
  - 返品を取り消して元に戻す場合:
    1. 紹介者にポイントを再付与
    2. クーポンステータスを `1:使用済` に戻す
    3. `referrer_point_granted = true` に更新

- [ ] 管理画面での受注編集時の考慮:
  - `EditController` は編集時に `PurchaseFlow::validate()` を呼ぶため、`DiscountProcessor` の `removeDiscountItem` → `addDiscountItem` が再実行される
  - **管理者が割引明細を手動削除した場合**: `addDiscountItem` でセッションにクーポン情報がないため再追加されない（管理画面編集時はセッションを参照しない設計とする）
  - **管理者が割引金額を手動変更した場合**: `processor_name` で識別し、クーポン割引明細の金額変更は許可する（管理者の裁量）
  - **数量変更**: クーポン割引は注文全体への固定額割引のため、商品数量変更の影響を受けない

### Phase 6: メール通知

- [ ] `app/Customize/Service/ReferralCouponMailService.php` を新規作成
  - `sendCouponIssuedMail()` - 購入者へクーポン発行通知
  - `sendReferrerRewardMail()` - 紹介者へポイント付与通知
  - `sendReferrerPointRevokedMail()` - 紹介者へポイント回収通知（返品/キャンセル時）
- [ ] `app/Customize/Resource/template/mail/referral_coupon_issued.twig` - クーポン発行通知
- [ ] `app/Customize/Resource/template/mail/referral_reward_notify.twig` - 紹介報酬通知
- [ ] `app/Customize/Resource/template/mail/referral_point_revoked.twig` - ポイント回収通知

### Phase 7: マイページ

- [ ] `app/Customize/Controller/Mypage/ReferralCouponController.php` を新規作成
  - GET `/mypage/referral_coupon` - 自分のクーポン一覧
- [ ] `app/template/default/Mypage/referral_coupon.twig` を新規作成

### Phase 8: 管理画面

- [ ] `app/Customize/Controller/Admin/ReferralCouponController.php` - クーポン管理・検索・ステータス変更
- [ ] `app/Customize/Form/Type/Admin/ReferralConfigType.php` - 設定フォーム

## 関連ファイル（参照のみ）

- `src/Eccube/Service/PurchaseFlow/Processor/PointProcessor.php` - DiscountProcessor実装パターン参考
- `src/Eccube/Service/PointHelper.php` - ポイント操作ヘルパー（rollback処理の参考）
- `src/Eccube/Service/OrderStateMachine.php` - ステータス遷移とイベント定義
  - `rollbackUsePoint()` / `rollbackAddPoint()` のパターンを参考にクーポンポイント回収を実装
- `src/Eccube/Controller/Admin/Order/EditController.php` - 受注編集時のPurchaseFlow呼び出しフロー
- `app/config/eccube/packages/purchaseflow.yaml` - PurchaseFlow設定

## 受注ライフサイクルとクーポンの状態遷移

```
[被紹介者が購入]
  → PurchaseProcessor.prepare(): クーポン=使用済, 被紹介者記録
  → PurchaseProcessor.commit():  紹介者にポイント付与, granted=true

[被紹介者の注文が返品/キャンセル]
  → OrderStateSubscriber: 紹介者ポイント回収, クーポン=返品取消, granted=false

[返品取消（元に戻す）]
  → OrderStateSubscriber: 紹介者ポイント再付与, クーポン=使用済, granted=true

[管理画面で受注編集]
  → DiscountProcessor が再実行されるが、管理画面ではセッション参照しないため
    既存の割引明細は管理者の変更を尊重する
```

## 注意事項

- クーポンコード生成は `random_bytes()` + Base62で推測困難なコードを生成
- 自分自身が発行したクーポンは使用不可とするバリデーション
- 同一会員が複数の紹介クーポンを同時使用できないよう制限
- `referrer_point_granted` フラグで二重付与・二重回収を防止
- 返品/キャンセル時のポイント回収でポイントが負になる場合は0に丸める（ポイントを消費済みの場合）
- 返品取消済みクーポンは再利用不可（別のクーポンを発行する運用）
