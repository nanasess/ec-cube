# EC-CUBE フックポイント (イベント) ガイド

このドキュメントは、EC-CUBEのカスタマイズで利用可能なフックポイント（イベント）の一覧と、その利用方法について説明します。

EC-CUBEはSymfonyの [EventDispatcherコンポーネント](https://symfony.com/doc/current/components/event_dispatcher.html) を利用しており、コアの処理に割り込んで独自の処理を追加・変更することが可能です。

## フックポイントの利用方法

フックポイントを利用するには、Symfonyの **Event Subscriber（イベントサブスクライバー）** を作成するのが最も一般的な方法です。

以下に、管理画面で商品が登録・更新された後 (`admin.product.edit.complete`) に独自の処理を実行する例を示します。

### 1. イベントサブスクライバークラスの作成

まず、`EventSubscriberInterface` を実装したクラスを作成します。この例では、`src/Eccube/Event/Subscriber` ディレクトリに作成します。

```php
<?php

namespace Eccube\Event\Subscriber;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyProductSubscriber implements EventSubscriberInterface
{
    /**
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE => 'onAdminProductEditComplete',
        ];
    }

    public function onAdminProductEditComplete(EventArgs $event)
    {
        // イベントからエンティティを取得
        $Product = $event->getArgument('Product');

        // ここに独自の処理を記述します
        // 例: 商品が更新されたことをログに出力する
        log_info('[CUSTOM LOG] Product updated.', ['id' => $Product->getId(), 'name' => $Product->getName()]);

        // $event->setArgument('Product', $Product); // エンティティを変更することも可能
    }
}
```

### 2. サービスとして登録

次に、作成したサブスクライバーをサービスとして登録します。プラグインやカスタマイズで `services.yaml` などの設定ファイルに追記します。`kernel.event_subscriber` タグを付けることで、Symfonyがイベントサブスクライバーとして認識します。

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    Eccube\Event\Subscriber\MyProductSubscriber:
        tags:
            - { name: 'kernel.event_subscriber' }
```

これで、管理画面で商品が作成または更新されるたびに、`onAdminProductEditComplete` メソッドが実行されるようになります。

---

## フックポイント一覧

以下は `src/Eccube/Event/EccubeEvents.php` に基づくフックポイントの一覧です。

### 管理画面 (Admin)

#### AdminController
*   `ADMIN_ADMIM_LOGIN_INITIALIZE`: `admin.admin.login.initialize`
*   `ADMIN_ADMIM_INDEX_INITIALIZE`: `admin.admin.index.initialize`
*   `ADMIN_ADMIM_INDEX_ORDER`: `admin.admin.index.order`
*   `ADMIN_ADMIM_INDEX_SALES`: `admin.admin.index.sales`
*   `ADMIN_ADMIM_INDEX_COMPLETE`: `admin.admin.index.complete`
*   `ADMIN_ADMIM_CHANGE_PASSWORD_INITIALIZE`: `admin.admin.change_password.initialize`
*   `ADMIN_ADMIN_CHANGE_PASSWORD_COMPLETE`: `admin.admin.change_password.complete`

#### Content/BlockController
*   `ADMIN_CONTENT_BLOCK_INDEX_COMPLETE`: `admin.content.block.index.complete`
*   `ADMIN_CONTENT_BLOCK_EDIT_INITIALIZE`: `admin.content.block.edit.initialize`
*   `ADMIN_CONTENT_BLOCK_EDIT_COMPLETE`: `admin.content.block.edit.complete`
*   `ADMIN_CONTENT_BLOCK_DELETE_COMPLETE`: `admin.content.block.delete.complete`

#### Content/LayoutController
*   `ADMIN_CONTENT_LAYOUT_INDEX_INITIALIZE`: `admin.content.layout.index.initialize`
*   `ADMIN_CONTENT_LAYOUT_INDEX_COMPLETE`: `admin.content.layout.index.complete`

#### Content/NewsController
*   `ADMIN_CONTENT_NEWS_INDEX_INITIALIZE`: `admin.content.news.index.initialize`
*   `ADMIN_CONTENT_NEWS_EDIT_INITIALIZE`: `admin.content.news.edit.initialize`
*   `ADMIN_CONTENT_NEWS_EDIT_COMPLETE`: `admin.content.news.edit.complete`
*   `ADMIN_CONTENT_NEWS_DELETE_COMPLETE`: `admin.content.news.delete.complete`

#### Content/PageController
*   `ADMIN_CONTENT_PAGE_INDEX_COMPLETE`: `admin.content.page.index.initialize`
*   `ADMIN_CONTENT_PAGE_EDIT_INITIALIZE`: `admin.content.page.edit.initialize`
*   `ADMIN_CONTENT_PAGE_EDIT_COMPLETE`: `admin.content.page.edit.complete`
*   `ADMIN_CONTENT_PAGE_DELETE_COMPLETE`: `admin.content.page.delete.complete`

#### Customer/CustomerController
*   `ADMIN_CUSTOMER_INDEX_INITIALIZE`: `admin.customer.index.initialize`
*   `ADMIN_CUSTOMER_INDEX_SEARCH`: `admin.customer.index.search`
*   `ADMIN_CUSTOMER_RESEND_COMPLETE`: `admin.customer.resend.complete`
*   `ADMIN_CUSTOMER_DELETE_COMPLETE`: `admin.customer.delete.complete`
*   `ADMIN_CUSTOMER_DELIVERY_DELETE_COMPLETE`: `admin.customer.delivery.delete.complete`
*   `ADMIN_CUSTOMER_CSV_EXPORT`: `admin.customer.csv.export`

#### Customer/CustomerEditController
*   `ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE`: `admin.customer.edit.index.initialize`
*   `ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE`: `admin.customer.edit.index.complete`
*   `ADMIN_CUSTOMER_DELIVERY_EDIT_INDEX_INITIALIZE`: `admin.customer.delivery.edit.index.initialize`
*   `ADMIN_CUSTOMER_DELIVERY_EDIT_INDEX_COMPLETE`: `admin.customer.delivery.edit.index.complete`

#### Order/EditController
*   `ADMIN_ORDER_EDIT_INDEX_INITIALIZE`: `admin.order.edit.index.initialize`
*   `ADMIN_ORDER_EDIT_INDEX_PROGRESS`: `admin.order.edit.index.progress`
*   `ADMIN_ORDER_EDIT_INDEX_COMPLETE`: `admin.order.edit.index.complete`
*   `ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_INITIALIZE`: `admin.order.edit.search.customer.initialize`
*   `ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_SEARCH`: `admin.order.edit.search.customer.search`
*   `ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_COMPLETE`: `admin.order.edit.search.customer.complete`
*   `ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_INITIALIZE`: `admin.order.edit.search.customer.by.id.initialize`
*   `ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_COMPLETE`: `admin.order.edit.search.customer.by.id.complete`
*   `ADMIN_ORDER_EDIT_SEARCH_PRODUCT_INITIALIZE`: `admin.order.edit.search.product.initialize`
*   `ADMIN_ORDER_EDIT_SEARCH_PRODUCT_SEARCH`: `admin.order.edit.search.product.search`
*   `ADMIN_ORDER_EDIT_SEARCH_PRODUCT_COMPLETE`: `admin.order.edit.search.product.complete`

#### Order/MailController
*   `ADMIN_ORDER_MAIL_INDEX_INITIALIZE`: `admin.order.mail.index.initialize`
*   `ADMIN_ORDER_MAIL_INDEX_CHANGE`: `admin.order.mail.index.change`
*   `ADMIN_ORDER_MAIL_INDEX_CONFIRM`: `admin.order.mail.index.confirm`
*   `ADMIN_ORDER_MAIL_INDEX_COMPLETE`: `admin.order.mail.index.complete`
*   `ADMIN_ORDER_MAIL_MAIL_ALL_INITIALIZE`: `admin.order.mail.mail.all.initialize`
*   `ADMIN_ORDER_MAIL_MAIL_ALL_CHANGE`: `admin.order.mail.mail.all.change`
*   `ADMIN_ORDER_MAIL_MAIL_ALL_CONFIRM`: `admin.order.mail.mail.all.confirm`
*   `ADMIN_ORDER_MAIL_MAIL_ALL_COMPLETE`: `admin.order.mail.mail.all.complete`

#### Order/OrderController
*   `ADMIN_ORDER_INDEX_INITIALIZE`: `admin.order.index.initialize`
*   `ADMIN_ORDER_INDEX_SEARCH`: `admin.order.index.search`
*   `ADMIN_ORDER_DELETE_COMPLETE`: `admin.order.delete.complete`
*   `ADMIN_ORDER_CSV_EXPORT_ORDER`: `admin.order.csv.export.order`
*   `ADMIN_ORDER_CSV_EXPORT_SHIPPING`: `admin.order.csv.export.shipping`

#### Shipping/ShippingController
*   `ADMIN_SHIPPING_INDEX_INITIALIZE`: `admin.shipping.index.initialize`
*   `ADMIN_SHIPPING_INDEX_SEARCH`: `admin.shipping.index.search`

#### Product/CategoryController
*   `ADMIN_PRODUCT_CATEGORY_INDEX_INITIALIZE`: `admin.product.category.index.initialize`
*   `ADMIN_PRODUCT_CATEGORY_INDEX_COMPLETE`: `admin.product.category.index.complete`
*   `ADMIN_PRODUCT_CATEGORY_DELETE_COMPLETE`: `admin.product.category.delete.complete`
*   `ADMIN_PRODUCT_CATEGORY_CSV_EXPORT`: `admin.product.category.csv.export`

#### Product/TagController
*   `ADMIN_PRODUCT_TAG_INDEX_INITIALIZE`: `admin.product.tag.index.initialize`
*   `ADMIN_PRODUCT_TAG_INDEX_COMPLETE`: `admin.product.tag.index.complete`
*   `ADMIN_PRODUCT_TAG_DELETE_COMPLETE`: `admin.product.tag.delete.complete`

#### Product/ClassCategoryController
*   `ADMIN_PRODUCT_CLASS_CATEGORY_INDEX_INITIALIZE`: `admin.product.class.category.index.initialize`
*   `ADMIN_PRODUCT_CLASS_CATEGORY_INDEX_COMPLETE`: `admin.product.class.category.index.complete`
*   `ADMIN_PRODUCT_CLASS_CATEGORY_DELETE_COMPLETE`: `admin.product.class.category.delete.complete`
*   `ADMIN_PRODUCT_CLASS_CATEGORY_CSV_EXPORT`: `admin.product.class.category.csv.export`

#### Product/ClassNameController
*   `ADMIN_PRODUCT_CLASS_NAME_INDEX_INITIALIZE`: `admin.product.class.name.index.initialize`
*   `ADMIN_PRODUCT_CLASS_NAME_INDEX_COMPLETE`: `admin.product.class.name.index.complete`
*   `ADMIN_PRODUCT_CLASS_NAME_DELETE_COMPLETE`: `admin.product.class.name.delete.complete`
*   `ADMIN_PRODUCT_CLASS_NAME_CSV_EXPORT`: `admin.product.class.name.csv.export`

#### Product/ProductClassController
*   `ADMIN_PRODUCT_PRODUCT_CLASS_INDEX_INITIALIZE`: `admin.product.product.class.index.initialize`
*   `ADMIN_PRODUCT_PRODUCT_CLASS_INDEX_CLASSES`: `admin.product.product.class.index.classes`
*   `ADMIN_PRODUCT_PRODUCT_CLASS_EDIT_INITIALIZE`: `admin.product.product.class.edit.initialize`
*   `ADMIN_PRODUCT_PRODUCT_CLASS_EDIT_COMPLETE`: `admin.product.product.class.edit.complete`
*   `ADMIN_PRODUCT_PRODUCT_CLASS_EDIT_UPDATE`: `admin.product.product.class.edit.update`
*   `ADMIN_PRODUCT_PRODUCT_CLASS_EDIT_DELETE`: `admin.product.product.class.edit.delete`

#### Product/ProductController
*   `ADMIN_PRODUCT_INDEX_INITIALIZE`: `admin.product.index.initialize`
*   `ADMIN_PRODUCT_INDEX_SEARCH`: `admin.product.index.search`
*   `ADMIN_PRODUCT_ADD_IMAGE_COMPLETE`: `admin.product.add.image.complete`
*   `ADMIN_PRODUCT_EDIT_INITIALIZE`: `admin.product.edit.initialize`
*   `ADMIN_PRODUCT_EDIT_SEARCH`: `admin.product.edit.search`
*   `ADMIN_PRODUCT_EDIT_COMPLETE`: `admin.product.edit.complete`
*   `ADMIN_PRODUCT_DELETE_COMPLETE`: `admin.product.delete.complete`
*   `ADMIN_PRODUCT_COPY_COMPLETE`: `admin.product.copy.complete`
*   `ADMIN_PRODUCT_DISPLAY_COMPLETE`: `admin.product.display.complete`
*   `ADMIN_PRODUCT_CSV_EXPORT`: `admin.product.csv.export`

#### Setting/Shop/CsvController
*   `ADMIN_SETTING_SHOP_CSV_INDEX_INITIALIZE`: `admin.setting.shop.csv.index.initialize`
*   `ADMIN_SETTING_SHOP_CSV_INDEX_COMPLETE`: `admin.setting.shop.csv.index.complete`

#### Setting/Shop/DeliveryController
*   `ADMIN_SETTING_SHOP_DELIVERY_INDEX_COMPLETE`: `admin.setting.shop.delivery.index.complete`
*   `ADMIN_SETTING_SHOP_DELIVERY_EDIT_INITIALIZE`: `admin.setting.shop.delivery.edit.initialize`
*   `ADMIN_SETTING_SHOP_DELIVERY_EDIT_COMPLETE`: `admin.setting.shop.delivery.edit.complete`
*   `ADMIN_SETTING_SHOP_DELIVERY_DELETE_COMPLETE`: `admin.setting.shop.delivery.delete.complete`
*   `ADMIN_SETTING_SHOP_DELIVERY_VISIBILITY_COMPLETE`: `admin.setting.shop.delivery.visibility.complete`

#### Setting/Shop/MailController
*   `ADMIN_SETTING_SHOP_MAIL_INDEX_INITIALIZE`: `admin.setting.shop.mail.index.initialize`
*   `ADMIN_SETTING_SHOP_MAIL_INDEX_COMPLETE`: `admin.setting.shop.mail.index.complete`
*   `ADMIN_SETTING_SHOP_MAIL_PREVIEW_COMPLETE`: `admin.setting.shop.mail.preview.complete`

#### Setting/Shop/PaymentController
*   `ADMIN_SETTING_SHOP_PAYMENT_INDEX_COMPLETE`: `admin.setting.shop.payment.index.complete`
*   `ADMIN_SETTING_SHOP_PAYMENT_EDIT_INITIALIZE`: `admin.setting.shop.payment.edit.initialize`
*   `ADMIN_SETTING_SHOP_PAYMENT_EDIT_COMPLETE`: `admin.setting.shop.payment.edit.complete`
*   `ADMIN_SETTING_SHOP_PAYMENT_IMAGE_ADD_COMPLETE`: `admin.setting.shop.payment.image.add.complete`
*   `ADMIN_SETTING_SHOP_PAYMENT_DELETE_COMPLETE`: `admin.setting.shop.payment.delete.complete`
*   `ADMIN_SETTING_SHOP_TRADE_LAW_INDEX_COMPLETE`: `admin.setting.shop.trade.law.index.complete`
*   `ADMIN_SETTING_SHOP_TRADE_LAW_POST_COMPLETE`: `admin.setting.shop.trade.law.post.complete`

#### Setting/Shop/ShopController
*   `ADMIN_SETTING_SHOP_SHOP_INDEX_INITIALIZE`: `admin.setting.shop.shop.index.initialize`
*   `ADMIN_SETTING_SHOP_SHOP_INDEX_COMPLETE`: `admin.setting.shop.shop.index.complete`

#### Setting/Shop/TaxRuleController
*   `ADMIN_SETTING_SHOP_TAX_RULE_INDEX_INITIALIZE`: `admin.setting.shop.tax.rule.index.initialize`
*   `ADMIN_SETTING_SHOP_TAX_RULE_INDEX_COMPLETE`: `admin.setting.shop.tax.rule.index.complete`
*   `ADMIN_SETTING_SHOP_TAX_RULE_DELETE_COMPLETE`: `admin.setting.shop.tax.rule.delete.complete`
*   `ADMIN_SETTING_SHOP_TAX_RULE_EDIT_PARAMETER_INITIALIZE`: `admin.setting.shop.tax.rule.edit.parameter.initialize`
*   `ADMIN_SETTING_SHOP_TAX_RULE_EDIT_PARAMETER_COMPLETE`: `admin.setting.shop.tax.rule.edit.parameter.complete`

#### Setting/System/AuthorityController
*   `ADMIN_SETTING_SYSTEM_AUTHORITY_INDEX_INITIALIZE`: `admin.setting.system.authority.index.initialize`
*   `ADMIN_SETTING_SYSTEM_AUTHORITY_INDEX_COMPLETE`: `admin.setting.system.authority.index.complete`

#### Setting/System/LogController
*   `ADMIN_SETTING_SYSTEM_LOG_INDEX_INITIALIZE`: `admin.setting.system.log.index.initialize`
*   `ADMIN_SETTING_SYSTEM_LOG_INDEX_COMPLETE`: `admin.setting.system.log.index.complete`

#### Setting/System/MasterdataController
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_INDEX_INITIALIZE`: `admin.setting.system.masterdata.index.initialize`
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_INDEX_FORM2_INITIALIZE`: `admin.setting.system.masterdata.index.form2.initialize`
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_INDEX_COMPLETE`: `admin.setting.system.masterdata.index.complete`
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_EDIT_INITIALIZE`: `admin.setting.system.masterdata.edit.initialize`
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_EDIT_FORM_INITIALIZE`: `admin.setting.system.masterdata.edit.form.initialize`
*   `ADMIN_SETTING_SYSTEM_MASTERDATA_EDIT_COMPLETE`: `admin.setting.system.masterdata.edit.complete`

#### Setting/System/MemberController
*   `ADMIN_SETTING_SYSTEM_MEMBER_INDEX_INITIALIZE`: `admin.setting.system.member.index.initialize`
*   `ADMIN_SETTING_SYSTEM_MEMBER_EDIT_INITIALIZE`: `admin.setting.system.member.edit.initialize`
*   `ADMIN_SETTING_SYSTEM_MEMBER_EDIT_COMPLETE`: `admin.setting.system.member.edit.complete`
*   `ADMIN_SETTING_SYSTEM_MEMBER_DELETE_INITIALIZE`: `admin.setting.system.member.delete.initialize`
*   `ADMIN_SETTING_SYSTEM_MEMBER_DELETE_COMPLETE`: `admin.setting.system.member.delete.complete`

---

### フロント画面 (Front)

#### Block/SearchProductController
*   `FRONT_BLOCK_SEARCH_PRODUCT_INDEX_INITIALIZE`: `front.block.search.product.index.initialize`

#### Mypage/ChangeController
*   `FRONT_MYPAGE_CHANGE_INDEX_INITIALIZE`: `front.mypage.change.index.initialize`
*   `FRONT_MYPAGE_CHANGE_INDEX_COMPLETE`: `front.mypage.change.index.complete`

#### Mypage/DeliveryController
*   `FRONT_MYPAGE_DELIVERY_EDIT_INITIALIZE`: `front.mypage.delivery.edit.initialize`
*   `FRONT_MYPAGE_DELIVERY_EDIT_COMPLETE`: `front.mypage.delivery.edit.complete`
*   `FRONT_MYPAGE_DELIVERY_DELETE_COMPLETE`: `front.mypage.delete.complete`

#### Mypage/MypageController
*   `FRONT_MYPAGE_MYPAGE_LOGIN_INITIALIZE`: `front.mypage.mypage.login.initialize`
*   `FRONT_MYPAGE_MYPAGE_INDEX_SEARCH`: `front.mypage.mypage.index.search`
*   `FRONT_MYPAGE_MYPAGE_HISTORY_INITIALIZE`: `front.mypage.mypage.history.initialize`
*   `FRONT_MYPAGE_MYPAGE_ORDER_INITIALIZE`: `front.mypage.mypage.order.initialize`
*   `FRONT_MYPAGE_MYPAGE_ORDER_COMPLETE`: `front.mypage.mypage.order.complete`
*   `FRONT_MYPAGE_MYPAGE_FAVORITE_SEARCH`: `front.mypage.mypage.favorite.search`
*   `FRONT_MYPAGE_MYPAGE_DELETE_INITIALIZE`: `front.mypage.mypage.delete.initialize`
*   `FRONT_MYPAGE_MYPAGE_DELETE_COMPLETE`: `front.mypage.mypage.delete.complete`

#### Mypage/WithdrawController
*   `FRONT_MYPAGE_WITHDRAW_INDEX_INITIALIZE`: `front.mypage.withdraw.index.initialize`
*   `FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE`: `front.mypage.withdraw.index.complete`

#### CartController
*   `FRONT_CART_INDEX_INITIALIZE`: `front.cart.index.initialize`
*   `FRONT_CART_INDEX_COMPLETE`: `front.cart.index.complete`
*   `FRONT_CART_ADD_INITIALIZE`: `front.cart.add.initialize`
*   `FRONT_CART_ADD_COMPLETE`: `front.cart.add.complete`
*   `FRONT_CART_ADD_EXCEPTION`: `front.cart.add.exception`
*   `FRONT_CART_UP_INITIALIZE`: `front.cart.up.initialize`
*   `FRONT_CART_UP_COMPLETE`: `front.cart.up.complete`
*   `FRONT_CART_UP_EXCEPTION`: `front.cart.up.exception`
*   `FRONT_CART_DOWN_INITIALIZE`: `front.cart.down.initialize`
*   `FRONT_CART_DOWN_COMPLETE`: `front.cart.down.complete`
*   `FRONT_CART_DOWN_EXCEPTION`: `front.cart.down.exception`
*   `FRONT_CART_REMOVE_INITIALIZE`: `front.cart.remove.initialize`
*   `FRONT_CART_REMOVE_COMPLETE`: `front.cart.remove.complete`
*   `FRONT_CART_BUYSTEP_INITIALIZE`: `front.cart.buystep.initialize`
*   `FRONT_CART_BUYSTEP_COMPLETE`: `front.cart.buystep.complete`

#### ContactController
*   `FRONT_CONTACT_INDEX_INITIALIZE`: `front.contact.index.initialize`
*   `FRONT_CONTACT_INDEX_COMPLETE`: `front.contact.index.complete`

#### EntryController
*   `FRONT_ENTRY_INDEX_INITIALIZE`: `front.entry.index.initialize`
*   `FRONT_ENTRY_INDEX_COMPLETE`: `front.entry.index.complete`
*   `FRONT_ENTRY_ACTIVATE_COMPLETE`: `front.entry.activate.complete`

#### ForgotController
*   `FRONT_FORGOT_INDEX_INITIALIZE`: `front.forgot.index.initialize`
*   `FRONT_FORGOT_INDEX_COMPLETE`: `front.forgot.index.complete`
*   `FRONT_FORGOT_RESET_COMPLETE`: `front.reset.index.complete`

#### ProductController
*   `FRONT_PRODUCT_INDEX_INITIALIZE`: `front.product.index.initialize`
*   `FRONT_PRODUCT_INDEX_SEARCH`: `front.product.index.search`
*   `FRONT_PRODUCT_INDEX_COMPLETE`: `front.product.index.complete`
*   `FRONT_PRODUCT_INDEX_DISP`: `front.product.index.disp`
*   `FRONT_PRODUCT_INDEX_ORDER`: `front.product.index.order`
*   `FRONT_PRODUCT_DETAIL_INITIALIZE`: `front.product.detail.initialize`
*   `FRONT_PRODUCT_DETAIL_FAVORITE`: `front.product.detail.favorite`
*   `FRONT_PRODUCT_DETAIL_COMPLETE`: `front.product.detail.complete`
*   `FRONT_PRODUCT_CART_ADD_INITIALIZE`: `front.product.cart.add.initialize`
*   `FRONT_PRODUCT_CART_ADD_COMPLETE`: `front.product.cart.add.complete`
*   `FRONT_PRODUCT_FAVORITE_ADD_INITIALIZE`: `front.product.favorite.add.initialize`
*   `FRONT_PRODUCT_FAVORITE_ADD_COMPLETE`: `front.product.favorite.add.complete`

#### ShoppingController
*   `FRONT_SHOPPING_INDEX_INITIALIZE`: `front.shopping.index.initialize`
*   `FRONT_SHOPPING_CONFIRM_INITIALIZE`: `front.shopping.confirm.initialize`
*   `FRONT_SHOPPING_CONFIRM_PROCESSING`: `front.shopping.confirm.processing`
*   `FRONT_SHOPPING_CONFIRM_COMPLETE`: `front.shopping.confirm.complete`
*   `FRONT_SHOPPING_COMPLETE_INITIALIZE`: `front.shopping.complete.initialize`
*   `FRONT_SHOPPING_DELIVERY_INITIALIZE`: `front.shopping.delivery.initialize`
*   `FRONT_SHOPPING_DELIVERY_COMPLETE`: `front.shopping.delivery.complete`
*   `FRONT_SHOPPING_PAYMENT_INITIALIZE`: `front.shopping.payment.initialize`
*   `FRONT_SHOPPING_PAYMENT_COMPLETE`: `front.shopping.payment.complete`
*   `FRONT_SHOPPING_SHIPPING_CHANGE_INITIALIZE`: `front.shopping.shipping.change.initialize`
*   `FRONT_SHOPPING_SHIPPING_COMPLETE`: `front.shopping.shipping.complete`
*   `FRONT_SHOPPING_SHIPPING_EDIT_CHANGE_INITIALIZE`: `front.shopping.shipping.edit.change.initialize`
*   `FRONT_SHOPPING_SHIPPING_EDIT_INITIALIZE`: `front.shopping.shipping.edit.initialize`
*   `FRONT_SHOPPING_SHIPPING_EDIT_COMPLETE`: `front.shopping.shipping.edit.complete`
*   `FRONT_SHOPPING_CUSTOMER_INITIALIZE`: `front.shopping.customer.initialize`
*   `FRONT_SHOPPING_LOGIN_INITIALIZE`: `front.shopping.login.initialize`
*   `FRONT_SHOPPING_NONMEMBER_INITIALIZE`: `front.shopping.nonmember.initialize`
*   `FRONT_SHOPPING_NONMEMBER_COMPLETE`: `front.shopping.nonmember.complete`
*   `FRONT_SHOPPING_SHIPPING_MULTIPLE_CHANGE_INITIALIZE`: `front.shopping.shipping.multiple.change.initialize`
*   `FRONT_SHOPPING_SHIPPING_MULTIPLE_INITIALIZE`: `front.shopping.shipping.multiple.initialize`
*   `FRONT_SHOPPING_SHIPPING_MULTIPLE_COMPLETE`: `front.shopping.shipping.multiple.complete`
*   `FRONT_SHOPPING_SHIPPING_MULTIPLE_EDIT_INITIALIZE`: `front.shopping.shipping.multiple.edit.initialize`
*   `FRONT_SHOPPING_SHIPPING_MULTIPLE_EDIT_COMPLETE`: `front.shopping.shipping.multiple.edit.complete`
*   `FRONT_SHOPPING_SHIPPING_ERROR_COMPLETE`: `front.shopping.shipping.error.complete`

#### UserDataController
*   `FRONT_USER_DATA_INDEX_INITIALIZE`: `front.user.data.index.initialize`

---

### メール (MailService)

*   `MAIL_CUSTOMER_CONFIRM`: `mail.customer.confirm`
*   `MAIL_CUSTOMER_COMPLETE`: `mail.customer.complete`
*   `MAIL_CUSTOMER_WITHDRAW`: `mail.customer.withdraw`
*   `MAIL_CONTACT`: `mail.contact`
*   `MAIL_ORDER`: `mail.order`
*   `MAIL_ADMIN_CUSTOMER_CONFIRM`: `mail.admin.customer.confirm`
*   `MAIL_ADMIN_ORDER`: `mail.admin.order`
*   `MAIL_PASSWORD_RESET`: `mail.password.reset`
*   `MAIL_PASSWORD_RESET_COMPLETE`: `mail.password.reset.complete`
