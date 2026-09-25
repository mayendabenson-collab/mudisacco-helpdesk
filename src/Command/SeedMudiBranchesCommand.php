<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Branch;
use App\Enum\BranchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sacco:seed-branches',
    description: 'Seeds the official Mudi SACCO branches and satellite centres into the database.',
)]
class SeedMudiBranchesCommand extends Command
{
    /** @var list<array{string, string, BranchType}> */
    private const OFFICIAL_LOCATIONS = [
        ['BLANTYRE_MAIN', 'Blantyre — Main / Head Office', BranchType::BRANCH],
        ['LILONGWE_KANENGO', 'Lilongwe — Kanengo', BranchType::BRANCH],
        ['MZUZU', 'Mzuzu', BranchType::BRANCH],
        ['ZOMBA', 'Zomba', BranchType::BRANCH],
        ['KASINTHULA_CHIKWAWA', 'Kasinthula — Chikwawa', BranchType::BRANCH],
        ['NSANJE', 'Nsanje', BranchType::BRANCH],
        ['MULANJE_SATELLITE', 'Mulanje — Satellite Centre', BranchType::SATELLITE_CENTRE],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding Mudi SACCO Official Locations');

        $repository = $this->entityManager->getRepository(Branch::class);
        $created = 0;
        $existing = 0;

        foreach (self::OFFICIAL_LOCATIONS as [$code, $name, $type]) {
            $branch = $repository->findOneBy(['code' => $code]);
            if (!$branch instanceof Branch) {
                $branch = new Branch();
                $branch->setCode($code);
                $branch->setName($name);
                $branch->setType($type);
                $branch->setActive(true);
                $this->entityManager->persist($branch);
                $io->text(sprintf(' + Created: %s (%s) [%s]', $name, $code, $type->value));
                $created++;
            } else {
                $io->text(sprintf(' = Existing: %s (%s)', $name, $code));
                $existing++;
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('Mudi SACCO location seeding completed. (%d added, %d existing)', $created, $existing));

        return Command::SUCCESS;
    }
}
