<?php

/*
 * 名入れ機能: 受注明細エンティティ拡張
 *
 * OrderItem に名入れテキストを保持する naire_text カラムを追加する。
 * （旧実装の NaireInfo OneToOne リレーション + dtb_naire_info テーブルを置き換え）
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
     * 名入れテキスト
     *
     * @var string|null
     *
     * @ORM\Column(name="naire_text", type="string", length=255, nullable=true)
     */
    private ?string $naire_text = null;

    /**
     * @return string|null
     */
    public function getNaireText(): ?string
    {
        return $this->naire_text;
    }

    /**
     * @param string|null $naireText
     *
     * @return $this
     */
    public function setNaireText(?string $naireText): self
    {
        $this->naire_text = $naireText;

        return $this;
    }
}
