<?php

namespace App\Command;

use App\Entity\File;
use App\Service\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// The database deletes file rows with their account but can't reach storage, so this removes
// the stored files left behind. Run it once a day on the server.
#[AsCommand(name: 'app:sweep-files', description: 'Deletes stored files that no file row points at')]
class SweepFilesCommand extends Command
{
    // An upload is saved before its row, so a newer file may still be getting its row.
    private const GRACE = '-1 hour';

    public function __construct(
        private UploadStorage $uploads,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the files without deleting them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $known = array_flip(array_column(
            $this->em->createQuery('SELECT f.storedName FROM ' . File::class . ' f')->getScalarResult(),
            'storedName',
        ));
        $deleted = $this->uploads->sweep($known, new \DateTimeImmutable(self::GRACE), $dryRun);

        foreach ($deleted as $name) {
            $io->writeln(($dryRun ? 'Would delete ' : 'Deleted ') . $name);
        }

        $count = count($deleted);
        $io->success(sprintf('%d %s %s.', $count, $count === 1 ? 'file' : 'files', $dryRun ? 'to delete' : 'deleted'));

        return Command::SUCCESS;
    }
}
