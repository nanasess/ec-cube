<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Repository;

use Eccube\Entity\MailHistory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Common\Persistence\ManagerRegistry as RegistryInterface;

/**
 * MailHistoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MailHistoryRepository extends AbstractRepository
{
    /**
     * MailHistoryRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MailHistory::class);
    }

    /**
     * @param \Eccube\Entity\Customer
     * @param integer $id
     * @expectedException \Exception|NoResultException|NonUniqueResultException
     */
    public function getByCustomerAndId(\Eccube\Entity\Customer $Customer, $id)
    {
        $qb = $this->createQueryBuilder('mh')
            ->leftJoin('mh.Order', 'o')
            ->where('mh.id = :id AND o.Customer = :Customer');

        return $qb
            ->getQuery()
            ->setParameters([
                'id' => $id,
                'Customer' => $Customer,
            ])
            ->getSingleResult();
    }
}
