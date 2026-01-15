<?php

namespace App\Entities;

use App\Repositories\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
#[ORM\Table(name: 'portfolios')]
#[ORM\HasLifecycleCallbacks]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'initial_capital', type: 'decimal', precision: 15, scale: 2)]
    private string $initialCapital;

    #[ORM\Column(name: 'start_date', type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(name: 'end_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'rebalance_frequency', type: 'string', length: 20)]
    private string $rebalanceFrequency = 'monthly';

    #[ORM\Column(name: 'output_currency', type: 'string', length: 3)]
    private string $outputCurrency = 'BRL';

    #[ORM\Column(name: 'is_system_default', type: 'boolean')]
    private bool $isSystemDefault = false;

    #[ORM\OneToMany(mappedBy: 'portfolio', targetEntity: PortfolioAsset::class, cascade: ['persist', 'remove'])]
    private Collection $assets;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->assets = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getInitialCapital(): string { return $this->initialCapital; }
    public function setInitialCapital(string $initialCapital): self { $this->initialCapital = $initialCapital; return $this; }
    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }
    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }
    public function getRebalanceFrequency(): string { return $this->rebalanceFrequency; }
    public function setRebalanceFrequency(string $rebalanceFrequency): self { $this->rebalanceFrequency = $rebalanceFrequency; return $this; }
    public function getOutputCurrency(): string { return $this->outputCurrency; }
    public function setOutputCurrency(string $outputCurrency): self { $this->outputCurrency = $outputCurrency; return $this; }
    public function isSystemDefault(): bool { return $this->isSystemDefault; }
    public function setIsSystemDefault(bool $isSystemDefault): self { $this->isSystemDefault = $isSystemDefault; return $this; }
    public function getAssets(): Collection { return $this->assets; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
