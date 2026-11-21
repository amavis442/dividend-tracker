<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Moves research attachments from the old public directory to the private
 * documents directory. Run it once after deploying #75; running it again
 * does nothing.
 */
#[AsCommand(
    name: 'app:research:move-attachments',
    description: 'Move research attachments out of public/uploads/documents',
)]
final class MoveResearchAttachmentsCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/documents')] private readonly string $legacyDirectory,
        #[Autowire('%documents_directory%')] private readonly string $documentsDirectory,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!is_dir($this->legacyDirectory)) {
            $io->success('Nothing to move: ' . $this->legacyDirectory . ' does not exist.');

            return Command::SUCCESS;
        }

        $this->filesystem->mkdir($this->documentsDirectory);
        $moved = 0;
        $skipped = 0;
        foreach ((new Finder())->files()->in($this->legacyDirectory)->depth(0) as $file) {
            $target = $this->documentsDirectory . '/' . $file->getFilename();
            if (file_exists($target)) {
                $io->warning('Skipped, already exists in the target directory: ' . $file->getFilename());
                $skipped++;
                continue;
            }
            $this->filesystem->rename($file->getPathname(), $target);
            $moved++;
        }

        $io->success(sprintf('Moved %d file(s) to %s, skipped %d.', $moved, $this->documentsDirectory, $skipped));

        return $skipped === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
