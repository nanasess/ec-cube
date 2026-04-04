<?php

/*
 * 商品検索サジェスト（オートコンプリート）API コントローラ
 *
 * フロント画面のヘッダー検索欄で入力中のキーワードに基づき、
 * 商品候補をJSON形式で返却する。
 */

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductSuggestController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var BaseInfo
     */
    private $BaseInfo;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BaseInfoRepository $baseInfoRepository,
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->BaseInfo = $baseInfoRepository->get();
    }

    /**
     * 商品サジェストAPI
     *
     * キーワードに一致する商品を最大10件返却する。
     * 2文字未満のキーワードは空配列を返す。
     *
     * @Route("/products/suggest", name="product_suggest", methods={"GET"})
     */
    public function index(Request $request): JsonResponse
    {
        // 在庫なし商品の非表示フィルタを有効化
        if ($this->BaseInfo->isOptionNostockHidden()) {
            $this->entityManager->getFilters()->enable('option_nostock_hidden');
        }

        $keyword = trim($request->query->get('keyword', ''));

        // 2文字未満は空配列を返す
        if (mb_strlen($keyword) < 2) {
            return $this->json([]);
        }

        // 検索条件を構築
        $searchData = [
            'name' => $keyword,
        ];

        // カテゴリIDが指定されている場合
        $categoryId = $request->query->getInt('category_id', 0);
        if ($categoryId > 0) {
            $Category = $this->categoryRepository->find($categoryId);
            if ($Category !== null) {
                $searchData['category_id'] = $Category;
            }
        }

        // ProductRepositoryの検索クエリを再利用
        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        $qb->setMaxResults(10);

        $Products = $qb->getQuery()->getResult();

        // JSON用のデータ配列を構築
        $data = [];
        foreach ($Products as $Product) {
            // メイン画像のファイル名を取得
            $mainImage = $Product->getMainListImage();
            $imageUrl = null;
            if ($mainImage !== null) {
                // save_imageパッケージのベースパスを使って画像URLを構築
                $imageUrl = $request->getSchemeAndHttpHost() . '/html/upload/save_image/' . $mainImage;
            } else {
                $imageUrl = $request->getSchemeAndHttpHost() . '/html/upload/save_image/no_image_product.png';
            }

            // 販売価格（税込）の最小値を取得
            $price = $Product->getPrice02IncTaxMin();

            $data[] = [
                'id' => $Product->getId(),
                'name' => $Product->getName(),
                'price' => $price !== null ? number_format($price) : null,
                'image' => $imageUrl,
                'url' => $this->generateUrl('product_detail', ['id' => $Product->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $this->json($data);
    }
}
