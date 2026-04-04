<?php

namespace Customize\Twig\Extension;

use Customize\Service\SalePriceService;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Twig\Extension\EccubeExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * セール価格関連の Twig 拡張
 *
 * sale_class_categories_as_json でセール価格情報付きの規格JSONを提供する。
 * コアの class_categories_as_json はそのまま残し、
 * セール対応テンプレートではこちらの関数を使用する。
 */
class SalePriceExtension extends AbstractExtension
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var SalePriceService
     */
    private $salePriceService;

    public function __construct(EccubeConfig $eccubeConfig, SalePriceService $salePriceService)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->salePriceService = $salePriceService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sale_class_categories_as_json', [$this, 'getSaleClassCategoriesAsJson']),
        ];
    }

    /**
     * セール価格情報を含む規格別JSONを生成する
     *
     * コアの class_categories_as_json と同じ構造に、セール価格情報を追加して返す。
     * テンプレートでは class_categories_as_json の代わりにこの関数を使う。
     */
    public function getSaleClassCategoriesAsJson(Product $Product): string
    {
        $Product->_calc();
        $class_categories = [
            '__unselected' => [
                '__unselected' => [
                    'name' => trans('common.select'),
                    'product_class_id' => '',
                ],
            ],
        ];

        foreach ($Product->getProductClasses() as $ProductClass) {
            if (!$ProductClass->isVisible()) {
                continue;
            }

            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
                continue;
            }

            $class_category_id1 = $ClassCategory1 ? (string) $ClassCategory1->getId() : '__unselected2';
            $class_category_id2 = $ClassCategory2 ? (string) $ClassCategory2->getId() : '';
            $class_category_name2 = $ClassCategory2
                ? $ClassCategory2->getName() . ($ProductClass->getStockFind() ? '' : trans('front.product.out_of_stock_label'))
                : '';

            $entry = [
                'classcategory_id2' => $class_category_id2,
                'name' => $class_category_name2,
                'stock_find' => $ProductClass->getStockFind(),
                'price01' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01()),
                'price02' => number_format($ProductClass->getPrice02()),
                'price01_inc_tax' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01IncTax()),
                'price02_inc_tax' => number_format($ProductClass->getPrice02IncTax()),
                'price01_with_currency' => $ProductClass->getPrice01() === null ? '' : $this->formatPrice($ProductClass->getPrice01()),
                'price02_with_currency' => $this->formatPrice($ProductClass->getPrice02()),
                'price01_inc_tax_with_currency' => $ProductClass->getPrice01() === null ? '' : $this->formatPrice($ProductClass->getPrice01IncTax()),
                'price02_inc_tax_with_currency' => $this->formatPrice($ProductClass->getPrice02IncTax()),
                'product_class_id' => (string) $ProductClass->getId(),
                'product_code' => $ProductClass->getCode() === null ? '' : $ProductClass->getCode(),
                'sale_type' => (string) $ProductClass->getSaleType()->getId(),
            ];

            // セール価格情報を追加
            if ($ProductClass->isOnSale()) {
                $entry['sale_price'] = number_format($ProductClass->getSalePrice());
                $entry['sale_price_inc_tax'] = number_format($ProductClass->getSalePriceIncTax());
                $entry['sale_price_inc_tax_with_currency'] = $this->formatPrice($ProductClass->getSalePriceIncTax());
                $entry['is_on_sale'] = true;
            } else {
                $entry['is_on_sale'] = false;
            }

            $class_categories[$class_category_id1]['#'] = [
                'classcategory_id2' => '',
                'name' => trans('common.select'),
                'product_class_id' => '',
            ];
            $class_categories[$class_category_id1]['#' . $class_category_id2] = $entry;
        }

        return json_encode($class_categories);
    }

    /**
     * 価格をフォーマットする（コアの EccubeExtension::getPriceFilter 相当）
     */
    private function formatPrice($number): string
    {
        $currency = $this->eccubeConfig->get('currency');
        $locale = $this->eccubeConfig->get('locale');

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($number ?? 0, $currency);
    }
}
