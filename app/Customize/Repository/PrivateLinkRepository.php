<?php

/*
 * 限定公開リンク リポジトリ
 */

namespace Customize\Repository;

use Customize\Entity\PrivateLink;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Entity\Product;
use Eccube\Repository\AbstractRepository;

/**
 * PrivateLinkRepository
 */
class PrivateLinkRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrivateLink::class);
    }

    /**
     * 有効なトークンで限定公開リンクを検索
     *
     * @param string $token
     *
     * @return PrivateLink|null
     */
    public function findValidByToken(string $token): ?PrivateLink
    {
        $PrivateLink = $this->findOneBy(['token' => $token]);

        if ($PrivateLink === null) {
            return null;
        }

        // 有効判定（is_active & 有効期限）
        if (!$PrivateLink->isValid()) {
            return null;
        }

        return $PrivateLink;
    }

    /**
     * 商品に紐づく限定公開リンクを取得
     *
     * @param Product $Product
     *
     * @return PrivateLink[]
     */
    public function findByProduct(Product $Product): array
    {
        return $this->findBy(
            ['Product' => $Product],
            ['create_date' => 'DESC']
        );
    }

    /**
     * 一意なトークンを生成
     *
     * @return string 64文字の16進数文字列
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while ($this->findOneBy(['token' => $token]) !== null);

        return $token;
    }
}
