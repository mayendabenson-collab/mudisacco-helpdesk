<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Branch;
use App\Entity\Member;
use App\Enum\MemberStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-members',
    description: 'Import members from a CSV file. CSV columns: member_number,display_name,primary_phone,email,status,branch_code',
)]
class ImportMembersCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter character', ',')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip members that already exist (by member number)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = (string) $input->getArgument('file');
        $delimiter = (string) $input->getOption('delimiter');
        $skipExisting = $input->getOption('skip-existing');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            $io->error("Cannot open file: $filePath");
            return Command::FAILURE;
        }

        $io->title('Importing Members from CSV');
        $io->section("File: $filePath");

        $headers = null;
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $branchCache = [];

        $memberRepository = $this->entityManager->getRepository(Member::class);
        $branchRepository = $this->entityManager->getRepository(Branch::class);

        while (($row = fgetcsv($file, null, $delimiter)) !== false) {
            // Parse header row
            if ($headers === null) {
                $headers = array_map('strtolower', $row);
                $io->text('Detected columns: ' . implode(', ', $headers));
                continue;
            }

            try {
                $data = array_combine($headers, $row);
                if (!is_array($data)) {
                    $errors++;
                    continue;
                }

                $memberNumber = strtoupper(trim($data['member_number'] ?? ''));
                $displayName = trim($data['display_name'] ?? '');
                $primaryPhone = trim($data['primary_phone'] ?? '');
                $email = strtolower(trim($data['email'] ?? ''));
                $statusStr = strtoupper(trim($data['status'] ?? 'ACTIVE'));
                $branchCode = strtoupper(trim($data['branch_code'] ?? ''));

                // Validate required fields
                if (!$memberNumber || !$displayName) {
                    $errors++;
                    $io->warning("Row skipped: member_number and display_name are required");
                    continue;
                }

                // Check if member already exists
                $existing = $memberRepository->findOneBy(['memberNumber' => $memberNumber]);
                if ($existing instanceof Member) {
                    if ($skipExisting) {
                        $skipped++;
                        continue;
                    }
                    // Update existing member
                    $member = $existing;
                } else {
                    $member = new Member();
                }

                // Set member data
                $member->setMemberNumber($memberNumber);
                $member->setDisplayName($displayName);
                $member->setPrimaryPhone($primaryPhone ?: null);
                $member->setEmail($email ?: null);

                // Set status
                try {
                    $status = MemberStatus::from($statusStr);
                    $member->setStatus($status);
                } catch (\ValueError) {
                    $io->warning("Invalid status '$statusStr' for $memberNumber, using ACTIVE");
                    $member->setStatus(MemberStatus::ACTIVE);
                }

                // Set branch if provided
                if ($branchCode) {
                    if (!isset($branchCache[$branchCode])) {
                        $branchCache[$branchCode] = $branchRepository->findOneBy(['code' => $branchCode]);
                    }
                    $branch = $branchCache[$branchCode];
                    if ($branch instanceof Branch) {
                        $member->setBranch($branch);
                    }
                }

                $this->entityManager->persist($member);
                $imported++;

                if ($imported % 50 === 0) {
                    $this->entityManager->flush();
                    $io->text("Processed $imported members so far...");
                }
            } catch (\Exception $e) {
                $errors++;
                $io->warning("Error processing row: " . $e->getMessage());
            }
        }

        fclose($file);

        // Final flush
        $this->entityManager->flush();

        $io->section('Import Summary');
        $io->text("✓ Imported: <fg=green>$imported</>");
        if ($skipped > 0) {
            $io->text("⊘ Skipped: <fg=yellow>$skipped</>");
        }
        if ($errors > 0) {
            $io->text("✗ Errors: <fg=red>$errors</>");
        }

        $io->success('Members import completed!');
        return Command::SUCCESS;
    }
}
