<?php

/*
 * 名入れ機能: 受注明細エンティティ拡張
 *
 * OrderItem に名入れ情報 (NaireInfo) への OneToOne リレーションを追加する。
 */

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{
    /**
     * 名入れ情報
     *
     * @var NaireInfo|null
     *
     * @ORM\OneToOne(targetEntity="Customize\Entity\NaireInfo", mappedBy="OrderItem", cascade={"persist", "remove"})
     */
    private ?NaireInfo $NaireInfo = null;

    /**
     * @return NaireInfo|null
     */
    public function getNaireInfo(): ?NaireInfo
    {
        return $this->NaireInfo;
    }

    /**
     * @param NaireInfo|null $naireInfo
     *
     * @return $this
     */
    public function setNaireInfo(?NaireInfo $naireInfo): self
    {
        $this->NaireInfo = $naireInfo;

        return $this;
    }
}
