<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Stock\Exception\StockImportInputException;
use App\Service\Stock\StockImportMode;
use App\Service\Stock\StockImporterFactory;
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
        private readonly StockImporterFactory $stockImporterFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filepath', InputArgument::REQUIRED, 'Absolute filepath to the CSV file')
            ->addArgument('supplier', InputArgument::REQUIRED, 'Supplier name (e.g., trah, lorotom)')
            ->addArgument('mode', InputArgument::OPTIONAL, 'Import mode: multiline (default: legacy)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $startTime = microtime(true);

        $io = new SymfonyStyle($input, $output);

        $filePath = (string) $input->getArgument('filepath');
        $supplier = (string) $input->getArgument('supplier');

        $io->title(sprintf('Starting stock import for supplier: <info>%s</info>', $supplier));
        $io->text(sprintf('Processing file: %s', $filePath));

        try {
            $mode = $this->parseMode($input->getArgument('mode'));
            $io->text(sprintf('Import mode: <info>%s</info>', $mode->value));
            $importer = $this->stockImporterFactory->create($mode);
            $processedRows = $importer->import($filePath, $supplier);

            $executionTime = microtime(true) - $startTime;

            $io->success(sprintf(
                'Imported %d stock items for supplier "%s". Execution time: %.1f s.',
                $processedRows,
                $supplier,
                $executionTime
            ));
            return Command::SUCCESS;
        } catch (StockImportInputException $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        } catch (\Exception $e) {
            $io->error(sprintf('An error occurred during import: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function parseMode(mixed $mode): StockImportMode
    {
        if (null === $mode || '' === trim((string) $mode)) {
            return StockImportMode::LEGACY;
        }

        $normalizedMode = strtolower(trim((string) $mode));

        if (StockImportMode::MULTILINE->value === $normalizedMode) {
            return StockImportMode::MULTILINE;
        }

        throw new StockImportInputException(sprintf(
            'Unknown import mode "%s". Allowed mode: %s.',
            $mode,
            StockImportMode::MULTILINE->value,
        ));
    }
}
