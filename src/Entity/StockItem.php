<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockItemRepository::class)]
#[ORM\Table(name: 'stock_items', options: ['collate' => 'utf8mb4_general_ci'])]
#[ORM\UniqueConstraint(name: 'uniq_supplier_external', columns: ['supplier', 'external_id'])]
#[ORM\Index(name: 'idx_mpn', columns: ['mpn'])]
#[ORM\Index(name: 'idx_ean', columns: ['ean'])]
class StockItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(length: 100)]
    private ?string $mpn = null;

    #[ORM\Column(length: 255)]
    private ?string $producerName = null;

    #[ORM\Column(length: 100)]
    private ?string $externalId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(length: 100)]
    private ?string $supplier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): static
    {
        $this->ean = $ean;

        return $this;
    }

    public function getMpn(): ?string
    {
        return $this->mpn;
    }

    public function setMpn(string $mpn): static
    {
        $this->mpn = $mpn;

        return $this;
    }

    public function getProducerName(): ?string
    {
        return $this->producerName;
    }

    public function setProducerName(string $producerName): static
    {
        $this->producerName = $producerName;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }
}
