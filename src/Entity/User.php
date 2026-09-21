<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\StaffPermission;
use App\Security\SystemRole;
use App\Enum\UserType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['user_type'], name: 'idx_users_user_type')]
#[UniqueEntity(fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(length: 140)]
    private string $fullName = '';

    #[ORM\Column(length: 32, enumType: UserType::class)]
    private UserType $userType = UserType::STAFF;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'staffMembers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'members')]
    #[ORM\JoinTable(name: 'team_members')]
    private Collection $teams;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $roles;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $passwordChangeRequired = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $failedLoginCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $staffPermissions = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->teams = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->staffPermissions = [];
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        $this->touch();

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        $this->touch();

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = trim($fullName);
        $this->touch();

        return $this;
    }

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function setUserType(UserType $userType): self
    {
        $this->userType = $userType;
        $this->touch();

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;
        $this->touch();

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->addMember($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): self
    {
        if ($this->teams->removeElement($team)) {
            $team->removeMember($this);
        }

        return $this;
    }

    public function belongsToDepartment(Department $department): bool
    {
        return $this->department !== null && $this->department->getId()->equals($department->getId());
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->teams->exists(fn (int $index, Team $memberTeam) => $memberTeam->getId()->equals($team->getId()));
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->roles as $role) {
            $roles[] = $role->getCode();
        }

        return array_values(array_unique($roles));
    }

    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
            $this->touch();
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->removeElement($role)) {
            $role->removeUser($this);
            $this->touch();
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        $this->touch();

        return $this;
    }

    public function isPasswordChangeRequired(): bool
    {
        return $this->passwordChangeRequired;
    }

    public function setPasswordChangeRequired(bool $passwordChangeRequired): self
    {
        $this->passwordChangeRequired = $passwordChangeRequired;
        $this->touch();

        return $this;
    }

    public function getFailedLoginCount(): int
    {
        return $this->failedLoginCount;
    }

    public function setFailedLoginCount(int $failedLoginCount): self
    {
        $this->failedLoginCount = max(0, $failedLoginCount);
        $this->touch();

        return $this;
    }

    public function recordFailedLogin(?\DateTimeImmutable $now = null): self
    {
        $this->failedLoginCount++;
        $this->touch();

        if ($this->failedLoginCount >= 5) {
            $this->lockedUntil = ($now ?? new \DateTimeImmutable())->modify('+15 minutes');
        }

        return $this;
    }

    public function clearLoginFailures(): self
    {
        $this->failedLoginCount = 0;
        $this->lockedUntil = null;
        $this->touch();

        return $this;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeImmutable $lockedUntil): self
    {
        $this->lockedUntil = $lockedUntil;
        $this->touch();

        return $this;
    }

    public function isLocked(?\DateTimeImmutable $now = null): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > ($now ?? new \DateTimeImmutable());
    }

    public function getStaffPermissions(): array
    {
        return $this->staffPermissions;
    }

    public function setStaffPermissions(array $permissions): self
    {
        $this->staffPermissions = array_unique($permissions);
        $this->touch();

        return $this;
    }

    public function hasPermission(StaffPermission|string $permission): bool
    {
        // Admin users implicitly have all permissions
        if (in_array(SystemRole::ADMIN->value, $this->getRoles(), true)) {
            return true;
        }

        if (is_string($permission)) {
            return $this->hasPermissionByString($permission);
        }

        return in_array($permission->value, $this->staffPermissions, true);
    }

    public function hasPermissionByString(string $permissionString): bool
    {
        return in_array($permissionString, $this->staffPermissions, true);
    }

    public function grantPermission(StaffPermission $permission): self
    {
        if (!$this->hasPermission($permission)) {
            $this->staffPermissions[] = $permission->value;
            $this->touch();
        }

        return $this;
    }

    public function revokePermission(StaffPermission $permission): self
    {
        $this->staffPermissions = array_filter(
            $this->staffPermissions,
            fn($p) => $p !== $permission->value
        );
        $this->touch();

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
