<?php

/*
 * 名入れ機能: 商品エンティティ拡張
 *
 * 商品ごとに名入れ対応/非対応を設定するためのトレイト。
 * @FormAppend により管理画面の商品編集フォームに自動でチェックボックスが追加される。
 */

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation\FormAppend;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * 名入れ対応フラグ
     *
     * @var bool|null
     *
     * @ORM\Column(name="naire_enabled", type="boolean", nullable=true, options={"default":false})
     *
     * @FormAppend(
     *     auto_render=true,
     *     type="\Symfony\Component\Form\Extension\Core\Type\CheckboxType",
     *     options={
     *         "required": false,
     *         "label": "名入れ対応",
     *         "attr": {"class": "form-check-input"}
     *     }
     * )
     */
    private ?bool $naire_enabled = false;

    /**
     * @return bool|null
     */
    public function isNaireEnabled(): ?bool
    {
        return $this->naire_enabled;
    }

    /**
     * @return bool|null
     */
    public function getNaireEnabled(): ?bool
    {
        return $this->naire_enabled;
    }

    /**
     * @param bool|null $naireEnabled
     *
     * @return $this
     */
    public function setNaireEnabled(?bool $naireEnabled): self
    {
        $this->naire_enabled = $naireEnabled;

        return $this;
    }
}
