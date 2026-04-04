<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;

/**
 * 紹介クーポンエンティティ.
 *
 * @ORM\Table(name="dtb_referral_coupon", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="dtb_referral_coupon_code_idx", columns={"coupon_code"})
 * }, indexes={
 *     @ORM\Index(name="dtb_referral_coupon_referrer_idx", columns={"referrer_id"}),
 *     @ORM\Index(name="dtb_referral_coupon_status_idx", columns={"status"})
 * })
 * @ORM\Entity(repositoryClass="Customize\Repository\ReferralCouponRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ReferralCoupon extends AbstractEntity
{
    /** 未使用 */
    public const STATUS_UNUSED = 0;
    /** 使用済 */
    public const STATUS_USED = 1;
    /** 期限切れ */
    public const STATUS_EXPIRED = 2;
    /** 無効 */
    public const STATUS_DISABLED = 3;
    /** 返品取消 */
    public const STATUS_REVOKED = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * クーポンコード（12桁、推測困難なランダム文字列）.
     *
     * @var string
     *
     * @ORM\Column(name="coupon_code", type="string", length=12, unique=true)
     */
    private $coupon_code;

    /**
     * 紹介者（クーポン発行元の会員）.
     *
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="referrer_id", referencedColumnName="id", nullable=false)
     */
    private $Referrer;

    /**
     * 被紹介者（クーポン使用者）.
     *
     * @var Customer|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="referee_id", referencedColumnName="id", nullable=true)
     */
    private $Referee;

    /**
     * 被紹介者の注文.
     *
     * @var Order|null
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumn(name="referee_order_id", referencedColumnName="id", nullable=true)
     */
    private $RefereeOrder;

    /**
     * 値引き額.
     *
     * @var int
     *
     * @ORM\Column(name="discount_amount", type="integer")
     */
    private $discount_amount;

    /**
     * 紹介者へ付与するポイント.
     *
     * @var int
     *
     * @ORM\Column(name="referrer_point", type="integer")
     */
    private $referrer_point;

    /**
     * ポイント付与済みフラグ.
     *
     * @var bool
     *
     * @ORM\Column(name="referrer_point_granted", type="boolean", options={"default":false})
     */
    private $referrer_point_granted = false;

    /**
     * ステータス（0:未使用 / 1:使用済 / 2:期限切れ / 3:無効 / 4:返品取消）.
     *
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"default":0})
     */
    private $status = self::STATUS_UNUSED;

    /**
     * 有効期限.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="expires_at", type="datetimetz", nullable=true)
     */
    private $expires_at;

    /**
     * 使用日時.
     *
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="used_at", type="datetimetz", nullable=true)
     */
    private $used_at;

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
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->update_date = new \DateTime();
    }

    // --- Accessors ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCouponCode(): string
    {
        return $this->coupon_code;
    }

    public function setCouponCode(string $coupon_code): self
    {
        $this->coupon_code = $coupon_code;

        return $this;
    }

    public function getReferrer(): Customer
    {
        return $this->Referrer;
    }

    public function setReferrer(Customer $Referrer): self
    {
        $this->Referrer = $Referrer;

        return $this;
    }

    public function getReferee(): ?Customer
    {
        return $this->Referee;
    }

    public function setReferee(?Customer $Referee): self
    {
        $this->Referee = $Referee;

        return $this;
    }

    public function getRefereeOrder(): ?Order
    {
        return $this->RefereeOrder;
    }

    public function setRefereeOrder(?Order $RefereeOrder): self
    {
        $this->RefereeOrder = $RefereeOrder;

        return $this;
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

    public function isReferrerPointGranted(): bool
    {
        return $this->referrer_point_granted;
    }

    public function setReferrerPointGranted(bool $referrer_point_granted): self
    {
        $this->referrer_point_granted = $referrer_point_granted;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expires_at;
    }

    public function setExpiresAt(?\DateTimeInterface $expires_at): self
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->used_at;
    }

    public function setUsedAt(?\DateTimeInterface $used_at): self
    {
        $this->used_at = $used_at;

        return $this;
    }

    public function getCreateDate(): \DateTimeInterface
    {
        return $this->create_date;
    }

    public function setCreateDate(\DateTimeInterface $create_date): self
    {
        $this->create_date = $create_date;

        return $this;
    }

    public function getUpdateDate(): \DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTimeInterface $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * クーポンが使用可能かどうかを判定する.
     */
    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_UNUSED) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at < new \DateTime()) {
            return false;
        }

        return true;
    }

    /**
     * 推測困難なクーポンコードを生成する.
     * random_bytes() + Base62 で12桁のコードを生成.
     */
    public static function generateCouponCode(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $bytes = random_bytes(12);
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $chars[ord($bytes[$i]) % 62];
        }

        return $code;
    }

    /**
     * ステータスのラベルを取得する.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_UNUSED => '未使用',
            self::STATUS_USED => '使用済',
            self::STATUS_EXPIRED => '期限切れ',
            self::STATUS_DISABLED => '無効',
            self::STATUS_REVOKED => '返品取消',
            default => '不明',
        };
    }
}
