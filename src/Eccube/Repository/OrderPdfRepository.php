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

use Eccube\Entity\Member;
use Eccube\Entity\OrderPdf;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OrderPdfRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderPdfRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderPdf::class);
    }

    /**
     * Save admin history.
     *
     * @param array $arrData
     *
     * @return bool
     */
    public function save($arrData)
    {
        /**
         * @var Member
         */
        $Member = $arrData['admin'];

        $OrderPdf = $this->find($Member);
        if (!$OrderPdf) {
            $OrderPdf = new OrderPdf();
        }

        $OrderPdf->setMemberId($Member->getId())
            ->setTitle($arrData['title'])
            ->setMessage1($arrData['message1'])
            ->setMessage2($arrData['message2'])
            ->setMessage3($arrData['message3'])
            ->setNote1($arrData['note1'])
            ->setNote2($arrData['note2'])
            ->setNote3($arrData['note3'])
            ->setVisible(true);
        $this->getEntityManager()->persist($OrderPdf);
        $this->getEntityManager()->flush($OrderPdf);

        return true;
    }
}
