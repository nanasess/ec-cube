<?php

/*
 * 名入れ機能: NaireInfo リポジトリ
 */

namespace Customize\Repository;

use Customize\Entity\NaireInfo;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;

class NaireInfoRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NaireInfo::class);
    }
}
