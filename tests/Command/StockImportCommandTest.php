<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\StockImportCommand;
use App\Service\Stock\StockImporterFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class StockImportCommandTest extends KernelTestCase
{
    private string $emptyFilePath;
    private StockImporterFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->emptyFilePath = sys_get_temp_dir() . '/stock-import-command-' . uniqid('', true) . '.csv';
        file_put_contents($this->emptyFilePath, '');
        $this->factory = static::getContainer()->get(StockImporterFactory::class);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->emptyFilePath)) {
            unlink($this->emptyFilePath);
        }

        parent::tearDown();
    }

    public function testMissingModeSelectsLegacyImporter(): void
    {
        $commandTester = $this->executeCommand([$this->emptyFilePath, 'trah']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('Import mode: legacy', $commandTester->getDisplay());
    }

    public function testMultilineModeSelectsBulkImporter(): void
    {
        $commandTester = $this->executeCommand([$this->emptyFilePath, 'trah', 'multiline']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('Import mode: multiline', $commandTester->getDisplay());
    }

    public function testMultilineModeIsNormalized(): void
    {
        $commandTester = $this->executeCommand([$this->emptyFilePath, 'trah', '  MULTILINE ']);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('Import mode: multiline', $commandTester->getDisplay());
    }

    public function testUnknownModeReturnsInvalidWithoutStartingImport(): void
    {
        $missingFilePath = sys_get_temp_dir() . '/does-not-exist-' . uniqid('', true) . '.csv';
        $commandTester = $this->executeCommand([$missingFilePath, 'trah', 'unknown']);

        self::assertSame(2, $commandTester->getStatusCode());
        self::assertStringContainsString('Unknown import mode "unknown"', $commandTester->getDisplay());
        self::assertStringNotContainsString('File not found or unreadable', $commandTester->getDisplay());
    }

    public function testMissingFileReturnsInvalid(): void
    {
        $missingFilePath = sys_get_temp_dir() . '/does-not-exist-' . uniqid('', true) . '.csv';
        $commandTester = $this->executeCommand([$missingFilePath, 'trah']);

        self::assertSame(2, $commandTester->getStatusCode());
        self::assertStringContainsString('File not found or unreadable', $commandTester->getDisplay());
    }

    public function testUnknownSupplierReturnsInvalid(): void
    {
        $commandTester = $this->executeCommand([$this->emptyFilePath, 'unknown-supplier']);

        self::assertSame(2, $commandTester->getStatusCode());
        self::assertStringContainsString('Unknown supplier', $commandTester->getDisplay());
    }

    /**
     * @param list<string> $arguments
     */
    private function executeCommand(array $arguments): CommandTester
    {
        $command = new StockImportCommand($this->factory);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'filepath' => $arguments[0],
            'supplier' => $arguments[1],
            'mode' => $arguments[2] ?? null,
        ]);

        return $commandTester;
    }
}
