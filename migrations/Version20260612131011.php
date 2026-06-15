<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260612131011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_items (id INT AUTO_INCREMENT NOT NULL, ean VARCHAR(100) DEFAULT NULL, mpn VARCHAR(100) NOT NULL, producer_name VARCHAR(255) NOT NULL, external_id VARCHAR(100) NOT NULL, price NUMERIC(10, 2) DEFAULT NULL, quantity INT NOT NULL, supplier VARCHAR(100) NOT NULL, INDEX idx_mpn (mpn), INDEX idx_ean (ean), UNIQUE INDEX uniq_supplier_external (supplier, external_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE stock_items');
    }
}
