<?php

namespace Customize\Repository;

use Customize\Entity\ReferralConfig;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;

/**
 * 紹介クーポン設定リポジトリ.
 */
class ReferralConfigRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferralConfig::class);
    }

    /**
     * 設定を取得する（1レコードのみ）.
     * 存在しない場合はデフォルト値で新規作成する.
     */
    public function get(): ReferralConfig
    {
        $config = $this->find(1);
        if ($config === null) {
            $config = new ReferralConfig();
            $em = $this->getEntityManager();
            $em->persist($config);
            $em->flush();
        }

        return $config;
    }
}
