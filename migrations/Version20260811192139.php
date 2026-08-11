<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811192139 extends AbstractMigration
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
              guest_email VARCHAR(180) DEFAULT NULL,
            ADD
              guest_token VARCHAR(64) DEFAULT NULL,
            CHANGE
              user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE4AC9362F ON orders (guest_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_E52FFDEE4AC9362F ON orders');
        $this->addSql('ALTER TABLE orders DROP guest_email, DROP guest_token, CHANGE user_id user_id INT NOT NULL');
    }
}
