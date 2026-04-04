<?php

/*
 * 名入れ機能: 名入れ情報エンティティ
 *
 * 購入確定時に永続化される名入れテキスト情報。
 * OrderItem と OneToOne で紐付く。
 */

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\OrderItem;

if (!class_exists(NaireInfo::class)) {
    /**
     * NaireInfo
     *
     * @ORM\Table(name="dtb_naire_info")
     *
     * @ORM\Entity(repositoryClass="Customize\Repository\NaireInfoRepository")
     *
     * @ORM\HasLifecycleCallbacks()
     */
    class NaireInfo extends AbstractEntity
    {
        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         *
         * @ORM\Id
         *
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * 名入れテキスト
         *
         * @var string
         *
         * @ORM\Column(name="naire_text", type="string", length=255)
         */
        private $naire_text;

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
         * @var OrderItem
         *
         * @ORM\OneToOne(targetEntity="Eccube\Entity\OrderItem")
         *
         * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id", nullable=false)
         */
        private $OrderItem;

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
        public function getNaireText(): string
        {
            return $this->naire_text;
        }

        /**
         * @param string $naireText
         *
         * @return $this
         */
        public function setNaireText(string $naireText): self
        {
            $this->naire_text = $naireText;

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
         * @param \DateTimeInterface $createDate
         *
         * @return $this
         */
        public function setCreateDate(\DateTimeInterface $createDate): self
        {
            $this->create_date = $createDate;

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
         * @param \DateTimeInterface $updateDate
         *
         * @return $this
         */
        public function setUpdateDate(\DateTimeInterface $updateDate): self
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * @return OrderItem
         */
        public function getOrderItem(): OrderItem
        {
            return $this->OrderItem;
        }

        /**
         * @param OrderItem $orderItem
         *
         * @return $this
         */
        public function setOrderItem(OrderItem $orderItem): self
        {
            $this->OrderItem = $orderItem;

            return $this;
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
