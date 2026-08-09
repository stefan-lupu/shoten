<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260809211459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE product_wholesale_tier (
              id INT AUTO_INCREMENT NOT NULL,
              min_quantity INT NOT NULL,
              unit_price NUMERIC(10, 2) NOT NULL,
              product_id INT NOT NULL,
              INDEX IDX_109FD82E4584665A (product_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              product_wholesale_tier
            ADD
              CONSTRAINT FK_109FD82E4584665A FOREIGN KEY (product_id) REFERENCES product (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_wholesale_tier DROP FOREIGN KEY FK_109FD82E4584665A');
        $this->addSql('DROP TABLE product_wholesale_tier');
    }
}
