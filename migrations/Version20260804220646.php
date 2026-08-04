<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804220646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_notification_request (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL, notified_at DATETIME DEFAULT NULL, product_id INT NOT NULL, INDEX IDX_4E3C30D14584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stock_notification_request ADD CONSTRAINT FK_4E3C30D14584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock_notification_request DROP FOREIGN KEY FK_4E3C30D14584665A');
        $this->addSql('DROP TABLE stock_notification_request');
    }
}
