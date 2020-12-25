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

namespace Eccube\Repository\Master;

use Eccube\Entity\Master\OrderStatus;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Eccube\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OrderStatusRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderStatusRepository extends AbstractRepository
{
    /**
     * OrderStatusRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderStatus::class);
    }

    /**
     * NOT IN で検索する.
     *
     * TODO Abstract メソッドにしたい
     *
     * @param array $criteria
     * @param array $orderBy
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     *
     * @see EntityRepository::findBy()
     */
    public function findNotContainsBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('o');

        foreach ($criteria as $col => $val) {
            $qb->andWhere($qb->expr()->notIn('o.'.$col, ':'.$col))
                ->setParameter($col, (array) $val);
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                if (array_values($orderBy) === $orderBy) { // 配列 or 連想配列
                    $sort = $order;
                    $order = 'ASC';
                }
                $qb->orderBy('o.'.$sort, $order);
            }
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * find All Array
     *
     * @return array
     */
    public function findAllArray()
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT os FROM Eccube\Entity\Master\OrderStatus os INDEX BY os.id ORDER BY os.sort_no ASC')
        ;
        $result = $query
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        return $result;
    }
}
