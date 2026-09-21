<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketPriority;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\Index(columns: ['department_id'], name: 'idx_categories_department')]
#[UniqueEntity(fields: ['code'])]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private Department $department;

    #[ORM\Column(length: 50, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 32, enumType: TicketPriority::class)]
    private TicketPriority $defaultPriority = TicketPriority::MEDIUM;

    #[ORM\Column]
    private bool $active = true;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDepartment(): Department
    {
        return $this->department;
    }

    public function setDepartment(Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null && trim($description) !== '' ? trim($description) : null;

        return $this;
    }

    public function getDefaultPriority(): TicketPriority
    {
        return $this->defaultPriority;
    }

    public function setDefaultPriority(TicketPriority $defaultPriority): self
    {
        $this->defaultPriority = $defaultPriority;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
