<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'portfolio_assets')]
class PortfolioAsset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'assets')]
    #[ORM\JoinColumn(name: 'portfolio_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Portfolio $portfolio;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id', nullable: false)]
    private Asset $asset;

    #[ORM\Column(name: 'allocation_percentage', type: 'decimal', precision: 10, scale: 6)]
    private string $allocationPercentage;

    #[ORM\Column(name: 'performance_factor', type: 'decimal', precision: 10, scale: 4)]
    private string $performanceFactor = '1.0000';

    public function getId(): ?int { return $this->id; }
    public function getPortfolio(): Portfolio { return $this->portfolio; }
    public function setPortfolio(Portfolio $portfolio): self { $this->portfolio = $portfolio; return $this; }
    public function getAsset(): Asset { return $this->asset; }
    public function setAsset(Asset $asset): self { $this->asset = $asset; return $this; }
    public function getAllocationPercentage(): string { return $this->allocationPercentage; }
    public function setAllocationPercentage(string $allocationPercentage): self { $this->allocationPercentage = $allocationPercentage; return $this; }
    public function getPerformanceFactor(): string { return $this->performanceFactor; }
    public function setPerformanceFactor(string $performanceFactor): self { $this->performanceFactor = $performanceFactor; return $this; }
}
