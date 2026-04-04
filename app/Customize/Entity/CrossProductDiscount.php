<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Product;

/**
 * セット割引ルール
 *
 * 商品Aがカートにある場合に商品Bに割引を適用するルールを管理する。
 * 例: 花嫁衣装（trigger）をカートに入れると紋付（target）が半額になる。
 *
 * @ORM\Table(name="dtb_cross_product_discount")
 * @ORM\Entity(repositoryClass="Customize\Repository\CrossProductDiscountRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class CrossProductDiscount extends AbstractEntity
{
    /**
     * 定額割引
     */
    public const DISCOUNT_TYPE_FIXED = 1;

    /**
     * 定率割引（パーセント）
     */
    public const DISCOUNT_TYPE_PERCENT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * トリガー商品（この商品がカートにあると割引発動）
     *
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="trigger_product_id", referencedColumnName="id", nullable=false)
     */
    private $triggerProduct;

    /**
     * ターゲット商品（割引対象となる商品）
     *
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="target_product_id", referencedColumnName="id", nullable=false)
     */
    private $targetProduct;

    /**
     * 割引種別（1=定額, 2=定率）
     *
     * @var int
     *
     * @ORM\Column(name="discount_type", type="smallint", options={"unsigned":true, "default":1})
     */
    private $discountType = self::DISCOUNT_TYPE_FIXED;

    /**
     * 割引値（定額の場合は円、定率の場合は%）
     *
     * @var string
     *
     * @ORM\Column(name="discount_value", type="decimal", precision=12, scale=2)
     */
    private $discountValue;

    /**
     * 有効フラグ
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default":true})
     */
    private $enabled = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $updateDate;

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $now = new \DateTime();
        $this->createDate = $now;
        $this->updateDate = $now;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updateDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTriggerProduct(): ?Product
    {
        return $this->triggerProduct;
    }

    public function setTriggerProduct(?Product $triggerProduct): self
    {
        $this->triggerProduct = $triggerProduct;

        return $this;
    }

    public function getTargetProduct(): ?Product
    {
        return $this->targetProduct;
    }

    public function setTargetProduct(?Product $targetProduct): self
    {
        $this->targetProduct = $targetProduct;

        return $this;
    }

    public function getDiscountType(): int
    {
        return $this->discountType;
    }

    public function setDiscountType(int $discountType): self
    {
        $this->discountType = $discountType;

        return $this;
    }

    public function getDiscountValue(): ?string
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?string $discountValue): self
    {
        $this->discountValue = $discountValue;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * 割引種別のラベルを返す
     */
    public function getDiscountTypeLabel(): string
    {
        return match ($this->discountType) {
            self::DISCOUNT_TYPE_FIXED => '定額',
            self::DISCOUNT_TYPE_PERCENT => '定率（%）',
            default => '不明',
        };
    }

    /**
     * 割引の説明文を返す（例: 「50%OFF」「500円引き」）
     */
    public function getDiscountDescription(): string
    {
        if ($this->discountType === self::DISCOUNT_TYPE_PERCENT) {
            return rtrim(rtrim($this->discountValue, '0'), '.') . '%OFF';
        }

        return number_format((float) $this->discountValue) . '円引き';
    }
}
