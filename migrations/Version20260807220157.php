<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807220157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // wholesale_status are DEFAULT explicit ('none', valoarea backată a
        // WholesaleStatus::None) — coloana e NOT NULL și tabelul `user` are
        // deja rânduri; fără default, MySQL (strict mode) respinge ALTER-ul.
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            ADD
              company_name VARCHAR(255) DEFAULT NULL,
            ADD
              company_cui VARCHAR(20) DEFAULT NULL,
            ADD
              company_reg_com VARCHAR(255) DEFAULT NULL,
            ADD
              company_address VARCHAR(255) DEFAULT NULL,
            ADD
              wholesale_status VARCHAR(255) NOT NULL DEFAULT 'none',
            ADD
              wholesale_requested_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            DROP
              company_name,
            DROP
              company_cui,
            DROP
              company_reg_com,
            DROP
              company_address,
            DROP
              wholesale_status,
            DROP
              wholesale_requested_at
        SQL);
    }
}
