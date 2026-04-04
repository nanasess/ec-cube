<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * 紹介クーポン設定エンティティ.
 *
 * @ORM\Table(name="dtb_referral_config")
 * @ORM\Entity(repositoryClass="Customize\Repository\ReferralConfigRepository")
 */
class ReferralConfig extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * 被紹介者への値引額.
     *
     * @var int
     *
     * @ORM\Column(name="discount_amount", type="integer", options={"default":500})
     */
    private $discount_amount = 500;

    /**
     * 紹介者への付与ポイント.
     *
     * @var int
     *
     * @ORM\Column(name="referrer_point", type="integer", options={"default":500})
     */
    private $referrer_point = 500;

    /**
     * クーポン有効日数.
     *
     * @var int
     *
     * @ORM\Column(name="expiry_days", type="integer", options={"default":30})
     */
    private $expiry_days = 30;

    /**
     * 1会員あたりの発行上限枚数.
     *
     * @var int
     *
     * @ORM\Column(name="max_coupons_per_customer", type="integer", options={"default":10})
     */
    private $max_coupons_per_customer = 10;

    /**
     * 機能の有効/無効.
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default":true})
     */
    private $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscountAmount(): int
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(int $discount_amount): self
    {
        $this->discount_amount = $discount_amount;

        return $this;
    }

    public function getReferrerPoint(): int
    {
        return $this->referrer_point;
    }

    public function setReferrerPoint(int $referrer_point): self
    {
        $this->referrer_point = $referrer_point;

        return $this;
    }

    public function getExpiryDays(): int
    {
        return $this->expiry_days;
    }

    public function setExpiryDays(int $expiry_days): self
    {
        $this->expiry_days = $expiry_days;

        return $this;
    }

    public function getMaxCouponsPerCustomer(): int
    {
        return $this->max_coupons_per_customer;
    }

    public function setMaxCouponsPerCustomer(int $max_coupons_per_customer): self
    {
        $this->max_coupons_per_customer = $max_coupons_per_customer;

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
}
