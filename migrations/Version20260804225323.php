<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804225323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              orders
            ADD
              refunded_at DATETIME DEFAULT NULL,
            ADD
              refund_reason VARCHAR(255) DEFAULT NULL,
            ADD
              tracking_number VARCHAR(100) DEFAULT NULL,
            ADD
              admin_notes LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              orders
            DROP
              refunded_at,
            DROP
              refund_reason,
            DROP
              tracking_number,
            DROP
              admin_notes
        SQL);
    }
}
