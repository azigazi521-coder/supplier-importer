<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Stock\StockImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-stock',
    description: 'Imports supplier stock data from a CSV file into the database.'
)]

class StockImportCommand extends Command
{
    public function __construct(
        private readonly StockImporter $stockImporter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filepath', InputArgument::REQUIRED, 'Absolute filepath to the CSV file')
            ->addArgument('supplier', InputArgument::REQUIRED, 'Supplier name (e.g., trah, lorotom)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $startTime = microtime(true);

        $io = new SymfonyStyle($input, $output);

        $filePath = (string) $input->getArgument('filepath');
        $supplier = (string) $input->getArgument('supplier');

        $io->title(sprintf('Starting stock import for supplier: <info>%s</info>', $supplier));
        $io->text(sprintf('Processing file: %s', $filePath));

        if (!file_exists($filePath)) {
            $io->error(sprintf('File does not exist: %s', $filePath));
            return Command::FAILURE;
        }

        try {
            $processedRows = $this->stockImporter->import($filePath, $supplier);

            $executionTime = microtime(true) - $startTime;

            $io->success(sprintf(
                'Imported %d stock items for supplier "%s". Execution time: %.1f s.',
                $processedRows,
                $supplier,
                $executionTime
            ));
            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        } catch (\Exception $e) {
            $io->error(sprintf('An error occurred during import: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
