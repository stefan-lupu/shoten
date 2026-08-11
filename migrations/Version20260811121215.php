<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811121215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE invoice_sequence (
              id INT AUTO_INCREMENT NOT NULL,
              series VARCHAR(20) NOT NULL,
              last_number INT DEFAULT 0 NOT NULL,
              UNIQUE INDEX UNIQ_4DAE8D733A10012D (series),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              orders
            ADD
              invoice_series VARCHAR(20) DEFAULT NULL,
            ADD
              invoice_number INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE invoice_sequence');
        $this->addSql('ALTER TABLE orders DROP invoice_series, DROP invoice_number');
    }
}
