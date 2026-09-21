<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
use App\Entity\Department;
use App\Entity\Member;
use App\Entity\Permission as PermissionEntity;
use App\Entity\Role;
use App\Entity\SlaRule;
use App\Entity\User;
use App\Enum\MemberStatus;
use App\Enum\TicketPriority;
use App\Enum\UserType;
use App\Security\Permission;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-foundation', description: 'Seeds foundational roles, permissions, departments, categories, SLA rules, and demo access accounts.')]
class SeedFoundationDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Administrator email address.', 'admin@mudi-sacco.local')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Administrator password. If omitted for a new admin, a random password is generated.')
            ->addOption('demo-password', null, InputOption::VALUE_REQUIRED, 'Password for seeded staff/member demo users.', 'MudiDemo123!');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $permissions = $this->seedPermissions();
        $roles = $this->seedRoles($permissions);
        $departments = $this->seedDepartments();
        $this->seedCategoriesAndSlaRules($departments);
        $generatedPassword = $this->seedAdministrator($roles[SystemRole::ADMIN->value], (string) $input->getOption('admin-email'), $input->getOption('admin-password'));
        $this->seedDemoUsers($roles, $departments, (string) $input->getOption('demo-password'));

        $this->entityManager->flush();

        $output->writeln('<info>Foundation data is ready.</info>');
        $output->writeln('<comment>Demo staff: support@mudi-sacco.local / ' . (string) $input->getOption('demo-password') . '</comment>');
        $output->writeln('<comment>Demo member: member@mudi-sacco.local / ' . (string) $input->getOption('demo-password') . '</comment>');

        if ($generatedPassword !== null) {
            $output->writeln(sprintf('<comment>Generated admin password for first login: %s</comment>', $generatedPassword));
        }

        return Command::SUCCESS;
    }

    /** @return array<string, PermissionEntity> */
    private function seedPermissions(): array
    {
        $repository = $this->entityManager->getRepository(PermissionEntity::class);
        $permissions = [];

        foreach (Permission::cases() as $definition) {
            $permission = $repository->findOneBy(['code' => $definition->value]) ?? new PermissionEntity();
            $permission->setCode($definition->value);
            $permission->setName($definition->label());
            $permission->setDescription($definition->label());
            $this->entityManager->persist($permission);
            $permissions[$definition->value] = $permission;
        }

        return $permissions;
    }

    /** @param array<string, PermissionEntity> $permissions @return array<string, Role> */
    private function seedRoles(array $permissions): array
    {
        $definitions = [
            SystemRole::ADMIN->value => [
                'name' => 'Administrator',
                'description' => 'Full system administration.',
                'permissions' => array_keys($permissions),
            ],
            SystemRole::SUPERVISOR->value => [
                'name' => 'Supervisor',
                'description' => 'Department supervisor for escalations, reassignment, workload, and departmental reports.',
                'permissions' => [
                    Permission::HANDLE_TICKETS->value,
                    Permission::ASSIGN_TICKETS->value,
                    Permission::ESCALATE_TICKETS->value,
                    Permission::VIEW_REPORTS->value,
                ],
            ],            SystemRole::STAFF->value => [
                'name' => 'Staff',
                'description' => 'Support staff that can handle assigned departmental work.',
                'permissions' => [Permission::HANDLE_TICKETS->value, Permission::ASSIGN_TICKETS->value, Permission::ESCALATE_TICKETS->value, Permission::VIEW_REPORTS->value],
            ],
            SystemRole::MEMBER->value => [
                'name' => 'Member',
                'description' => 'SACCO member portal access.',
                'permissions' => [],
            ],
        ];

        $repository = $this->entityManager->getRepository(Role::class);
        $roles = [];

        foreach ($definitions as $code => $definition) {
            $role = $repository->findOneBy(['code' => $code]) ?? new Role();
            $role->setCode($code);
            $role->setName($definition['name']);
            $role->setDescription($definition['description']);
            $role->syncPermissions(array_map(static fn (string $permissionCode): PermissionEntity => $permissions[$permissionCode], $definition['permissions']));
            $this->entityManager->persist($role);
            $roles[$code] = $role;
        }

        return $roles;
    }

    /** @return array<string, Department> */
    private function seedDepartments(): array
    {
        $definitions = [
            'CUSTOMER_SERVICE' => ['Customer Service', 'First-line member support and complaint coordination.'],
            'MEMBER_SERVICES' => ['Member Services', 'General member inquiries and profile service requests.'],
            'LOANS' => ['Loans', 'Loan inquiries, repayments, statements, and loan support.'],
            'SAVINGS' => ['Savings', 'Savings account inquiries, deposits, and withdrawals.'],
            'FINANCE' => ['Finance', 'Shares, reconciliations, transaction disputes, and account corrections.'],
            'COMPLIANCE' => ['Compliance', 'Fraud, account-security, and sensitive member complaints.'],
            'ICT' => ['ICT', 'Digital channels, portal access, and internal systems support.'],
        ];

        $repository = $this->entityManager->getRepository(Department::class);
        $departments = [];

        foreach ($definitions as $code => [$name, $description]) {
            $department = $repository->findOneBy(['code' => $code]) ?? new Department();
            $department->setCode($code);
            $department->setName($name);
            $department->setDescription($description);
            $department->setActive(true);
            $this->entityManager->persist($department);
            $departments[$code] = $department;
        }

        return $departments;
    }

    /** @param array<string, Department> $departments */
    private function seedCategoriesAndSlaRules(array $departments): void
    {
        $definitions = [
            ['CUSTOMER_SERVICE', 'GENERAL_ENQUIRY', 'General enquiry', 'General member questions and service guidance.', TicketPriority::LOW],
            ['CUSTOMER_SERVICE', 'COMPLAINT', 'Complaint', 'Service complaint requiring follow-up.', TicketPriority::MEDIUM],
            ['MEMBER_SERVICES', 'PROFILE_UPDATE', 'Profile update', 'Member profile or contact detail change.', TicketPriority::MEDIUM],
            ['LOANS', 'LOAN_ISSUE', 'Loan issue', 'Loan application, repayment, statement, or loan support issue.', TicketPriority::MEDIUM],
            ['SAVINGS', 'FAILED_WITHDRAWAL', 'Failed withdrawal', 'Withdrawal failed or member did not receive funds.', TicketPriority::HIGH],
            ['FINANCE', 'MONEY_MISSING', 'Money missing', 'Missing deposit, savings, shares, or transaction value.', TicketPriority::HIGH],
            ['FINANCE', 'SHARES', 'Shares', 'Shares statement, contribution, or correction request.', TicketPriority::MEDIUM],
            ['COMPLIANCE', 'FRAUD', 'Fraud', 'Suspected fraud or unauthorized activity.', TicketPriority::URGENT],
            ['COMPLIANCE', 'ACCOUNT_SECURITY', 'Account security', 'Account takeover, suspicious login, or credential concern.', TicketPriority::URGENT],
            ['ICT', 'TECHNICAL', 'Technical', 'Portal, mobile app, or digital channel support.', TicketPriority::MEDIUM],
        ];

        $categoryRepository = $this->entityManager->getRepository(Category::class);
        $slaRepository = $this->entityManager->getRepository(SlaRule::class);

        foreach ($definitions as [$departmentCode, $code, $name, $description, $priority]) {
            $category = $categoryRepository->findOneBy(['code' => $code]) ?? new Category();
            $category->setDepartment($departments[$departmentCode]);
            $category->setCode($code);
            $category->setName($name);
            $category->setDescription($description);
            $category->setDefaultPriority($priority);
            $category->setActive(true);
            $this->entityManager->persist($category);

            $sla = $slaRepository->findOneBy(['category' => $category, 'priority' => $priority]) ?? new SlaRule();
            $sla->setCategory($category);
            $sla->setPriority($priority);
            $sla->setTargets(...$this->targetsForPriority($priority));
            $this->entityManager->persist($sla);
        }
    }

    private function seedAdministrator(Role $adminRole, string $email, ?string $password): ?string
    {
        $repository = $this->entityManager->getRepository(User::class);
        $admin = $repository->findOneBy(['email' => strtolower(trim($email))]);
        $generatedPassword = null;
        $isNew = !$admin instanceof User;

        if (!$admin instanceof User) {
            $admin = new User();
            $this->entityManager->persist($admin);
        }

        $admin->setEmail($email);
        $admin->setFullName('Mudi SACCO Administrator');
        $admin->setUserType(UserType::ADMINISTRATOR);
        $admin->setActive(true);
        $admin->setPasswordChangeRequired(false);
        $admin->addRole($adminRole);

        if ($isNew || is_string($password)) {
            $passwordToHash = is_string($password) && $password !== '' ? $password : bin2hex(random_bytes(10));
            $admin->setPasswordHash($this->passwordHasher->hashPassword($admin, $passwordToHash));
            $generatedPassword = is_string($password) && $password !== '' ? null : $passwordToHash;
        }

        return $generatedPassword;
    }

    /** @param array<string, Role> $roles @param array<string, Department> $departments */
    private function seedDemoUsers(array $roles, array $departments, string $demoPassword): void
    {
        $supervisor = $this->upsertUser('supervisor@mudi-sacco.local', 'Samuel Karanja', UserType::SUPERVISOR, $roles[SystemRole::SUPERVISOR->value], $demoPassword);
        $supervisor->setDepartment($departments['CUSTOMER_SERVICE']);

        $staffDefinitions = [
            ['support@mudi-sacco.local', 'Grace Wanjiku', 'CUSTOMER_SERVICE'],
            ['loans@mudi-sacco.local', 'Peter Otieno', 'LOANS'],
            ['compliance@mudi-sacco.local', 'Amina Hassan', 'COMPLIANCE'],
        ];

        foreach ($staffDefinitions as [$email, $name, $departmentCode]) {
            $staff = $this->upsertUser($email, $name, UserType::STAFF, $roles[SystemRole::STAFF->value], $demoPassword);
            $staff->setDepartment($departments[$departmentCode]);
        }

        $memberUser = $this->upsertUser('member@mudi-sacco.local', 'Daniel Mwangi', UserType::MEMBER, $roles[SystemRole::MEMBER->value], $demoPassword);
        $memberRepository = $this->entityManager->getRepository(Member::class);
        $member = $memberRepository->findOneBy(['memberNumber' => 'MUDI-000123']) ?? new Member();
        $member->setMemberNumber('MUDI-000123');
        $member->setDisplayName('Daniel Mwangi');
        $member->setPrimaryPhone('+254700000123');
        $member->setEmail('member@mudi-sacco.local');
        $member->setStatus(MemberStatus::ACTIVE);
        $member->setUser($memberUser);
        $this->entityManager->persist($member);
    }

    private function upsertUser(string $email, string $fullName, UserType $userType, Role $role, string $password): User
    {
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->findOneBy(['email' => strtolower(trim($email))]) ?? new User();
        $isNew = $user->getEmail() === '';

        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setUserType($userType);
        $user->setActive(true);
        $user->setPasswordChangeRequired(false);
        $user->addRole($role);

        if ($isNew || $user->getPasswordHash() === '') {
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));
        }

        $this->entityManager->persist($user);

        return $user;
    }

    /** @return array{0:int, 1:int, 2:int} */
    private function targetsForPriority(TicketPriority $priority): array
    {
        return match ($priority) {
            TicketPriority::URGENT => [30, 240, 180],
            TicketPriority::HIGH => [120, 480, 360],
            TicketPriority::MEDIUM => [480, 1440, 1200],
            TicketPriority::LOW => [1440, 2880, 2400],
        };
    }
}
