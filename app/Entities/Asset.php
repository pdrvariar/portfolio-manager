<?php

namespace App\Entities;

use App\Repositories\AssetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: 'system_assets')]
class Asset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(name: 'asset_type', type: 'string', length: 20)]
    private string $assetType;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }
    public function getAssetType(): string { return $this->assetType; }
    public function setAssetType(string $assetType): self { $this->assetType = $assetType; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
