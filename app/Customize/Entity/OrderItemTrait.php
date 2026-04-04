<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * OrderItem へのセール情報記録用拡張
 *
 * セール適用時に sale_config_id と original_price を記録し、
 * 受注明細からのセール追跡を可能にする。
 *
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{
    /**
     * 適用されたセール設定ID
     *
     * @var int|null
     *
     * @ORM\Column(name="sale_config_id", type="integer", nullable=true)
     */
    private $sale_config_id;

    /**
     * セール適用前の元の販売価格（税抜）
     *
     * @var string|null
     *
     * @ORM\Column(name="original_price", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $original_price;

    /**
     * セール設定IDを取得
     */
    public function getSaleConfigId(): ?int
    {
        return $this->sale_config_id;
    }

    /**
     * セール設定IDをセット
     */
    public function setSaleConfigId(?int $sale_config_id): self
    {
        $this->sale_config_id = $sale_config_id;

        return $this;
    }

    /**
     * 元の販売価格（税抜）を取得
     */
    public function getOriginalPrice(): ?string
    {
        return $this->original_price;
    }

    /**
     * 元の販売価格（税抜）をセット
     */
    public function setOriginalPrice(?string $original_price): self
    {
        $this->original_price = $original_price;

        return $this;
    }
}
