<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Category;

/**
 * セール設定エンティティ
 *
 * カテゴリ単位でセール価格（割引率 or 固定値引き）を期間指定して一括適用する。
 *
 * @ORM\Table(name="dtb_sale_config")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\Entity(repositoryClass="Customize\Repository\SaleConfigRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SaleConfig extends AbstractEntity
{
    /** 割引タイプ: 割引率（%） */
    public const DISCOUNT_TYPE_RATE = 1;

    /** 割引タイプ: 固定値引き（円） */
    public const DISCOUNT_TYPE_AMOUNT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * セール名
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * 割引タイプ（1=割引率% / 2=固定値引き円）
     *
     * @var int
     *
     * @ORM\Column(name="discount_type", type="smallint")
     */
    private $discount_type;

    /**
     * 割引値（割引率の場合は%、固定値引きの場合は円）
     *
     * @var string
     *
     * @ORM\Column(name="discount_value", type="decimal", precision=12, scale=2)
     */
    private $discount_value;

    /**
     * セール開始日時
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="start_date", type="datetimetz", nullable=true)
     */
    private $start_date;

    /**
     * セール終了日時
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="end_date", type="datetimetz", nullable=true)
     */
    private $end_date;

    /**
     * 有効フラグ
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default":false})
     */
    private $enabled = false;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * 対象カテゴリ
     *
     * @var Collection|Category[]
     *
     * @ORM\ManyToMany(targetEntity="Eccube\Entity\Category")
     * @ORM\JoinTable(name="dtb_sale_config_category",
     *     joinColumns={@ORM\JoinColumn(name="sale_config_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id")}
     * )
     */
    private $Categories;

    public function __construct()
    {
        $this->Categories = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->create_date = $now;
        $this->update_date = $now;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->update_date = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDiscountType(): ?int
    {
        return $this->discount_type;
    }

    public function setDiscountType(int $discount_type): self
    {
        $this->discount_type = $discount_type;

        return $this;
    }

    public function getDiscountValue(): ?string
    {
        return $this->discount_value;
    }

    public function setDiscountValue(string $discount_value): self
    {
        $this->discount_value = $discount_value;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): self
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): self
    {
        $this->end_date = $end_date;

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

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->create_date;
    }

    public function setCreateDate(\DateTimeInterface $create_date): self
    {
        $this->create_date = $create_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTimeInterface $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->Categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->Categories->contains($category)) {
            $this->Categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->Categories->removeElement($category);

        return $this;
    }

    /**
     * セールが有効かつ期間内かを判定する
     *
     * @param \DateTimeInterface|null $now 判定基準日時（null の場合は現在日時）
     */
    public function isActive(?\DateTimeInterface $now = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable();

        if ($this->start_date !== null && $now < $this->start_date) {
            return false;
        }

        if ($this->end_date !== null && $now > $this->end_date) {
            return false;
        }

        return true;
    }

    /**
     * 割引後価格を計算する
     *
     * @param string $originalPrice 元の販売価格（税抜）
     *
     * @return string 割引後価格（税抜）。0未満にはならない
     */
    public function calcSalePrice(string $originalPrice): string
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_RATE) {
            // 割引率: 元価格 × (1 - 割引率/100)
            $rate = bcdiv($this->discount_value, '100', 10);
            $discount = bcmul($originalPrice, $rate, 0);
            $salePrice = bcsub($originalPrice, $discount, 0);
        } else {
            // 固定値引き: 元価格 - 値引き額
            $salePrice = bcsub($originalPrice, $this->discount_value, 0);
        }

        // 0未満にはしない
        if (bccomp($salePrice, '0', 0) < 0) {
            $salePrice = '0';
        }

        return $salePrice;
    }
}
