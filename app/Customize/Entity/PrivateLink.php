<?php

/*
 * 限定公開リンク エンティティ
 *
 * 非公開商品を秘密トークン付きURLで特定顧客に公開する仕組み。
 */

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;

if (!class_exists(PrivateLink::class)) {
    /**
     * 限定公開リンク
     *
     * @ORM\Table(name="dtb_private_link", uniqueConstraints={
     *     @ORM\UniqueConstraint(name="dtb_private_link_token_idx", columns={"token"})
     * })
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\PrivateLinkRepository")
     */
    class PrivateLink extends AbstractEntity
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
         * 認証トークン（64文字の16進数文字列）
         *
         * @var string
         *
         * @ORM\Column(name="token", type="string", length=64)
         */
        private $token;

        /**
         * 有効期限（NULLの場合は無期限）
         *
         * @var \DateTimeInterface|null
         *
         * @ORM\Column(name="expires_at", type="datetimetz", nullable=true)
         */
        private $expires_at;

        /**
         * 有効フラグ
         *
         * @var bool
         *
         * @ORM\Column(name="is_active", type="boolean", options={"default":true})
         */
        private $is_active = true;

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
         * 対象商品
         *
         * @var Product
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
         * })
         */
        private $Product;

        /**
         * 対象顧客（NULLの場合はトークンさえあれば誰でも閲覧可）
         *
         * @var Customer|null
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true)
         * })
         */
        private $Customer;

        /**
         * @return int
         */
        public function getId(): int
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getToken(): string
        {
            return $this->token;
        }

        /**
         * @param string $token
         *
         * @return $this
         */
        public function setToken(string $token): self
        {
            $this->token = $token;

            return $this;
        }

        /**
         * @return \DateTimeInterface|null
         */
        public function getExpiresAt(): ?\DateTimeInterface
        {
            return $this->expires_at;
        }

        /**
         * @param \DateTimeInterface|null $expires_at
         *
         * @return $this
         */
        public function setExpiresAt(?\DateTimeInterface $expires_at): self
        {
            $this->expires_at = $expires_at;

            return $this;
        }

        /**
         * @return bool
         */
        public function isActive(): bool
        {
            return $this->is_active;
        }

        /**
         * @param bool $is_active
         *
         * @return $this
         */
        public function setIsActive(bool $is_active): self
        {
            $this->is_active = $is_active;

            return $this;
        }

        /**
         * @return \DateTimeInterface
         */
        public function getCreateDate(): \DateTimeInterface
        {
            return $this->create_date;
        }

        /**
         * @param \DateTimeInterface $create_date
         *
         * @return $this
         */
        public function setCreateDate(\DateTimeInterface $create_date): self
        {
            $this->create_date = $create_date;

            return $this;
        }

        /**
         * @return \DateTimeInterface
         */
        public function getUpdateDate(): \DateTimeInterface
        {
            return $this->update_date;
        }

        /**
         * @param \DateTimeInterface $update_date
         *
         * @return $this
         */
        public function setUpdateDate(\DateTimeInterface $update_date): self
        {
            $this->update_date = $update_date;

            return $this;
        }

        /**
         * @return Product
         */
        public function getProduct(): Product
        {
            return $this->Product;
        }

        /**
         * @param Product $Product
         *
         * @return $this
         */
        public function setProduct(Product $Product): self
        {
            $this->Product = $Product;

            return $this;
        }

        /**
         * @return Customer|null
         */
        public function getCustomer(): ?Customer
        {
            return $this->Customer;
        }

        /**
         * @param Customer|null $Customer
         *
         * @return $this
         */
        public function setCustomer(?Customer $Customer): self
        {
            $this->Customer = $Customer;

            return $this;
        }

        /**
         * 有効なリンクかどうかを判定
         *
         * @return bool
         */
        public function isValid(): bool
        {
            // 無効化されている場合
            if (!$this->is_active) {
                return false;
            }

            // 有効期限が設定されており、期限切れの場合
            if ($this->expires_at !== null && $this->expires_at < new \DateTime()) {
                return false;
            }

            return true;
        }

        /**
         * @ORM\PrePersist
         */
        public function prePersist(): void
        {
            $now = new \DateTime();
            $this->create_date = $now;
            $this->update_date = $now;
        }

        /**
         * @ORM\PreUpdate
         */
        public function preUpdate(): void
        {
            $this->update_date = new \DateTime();
        }
    }
}
