<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803074820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, starts_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, is_active TINYINT NOT NULL, coupon_code VARCHAR(50) DEFAULT NULL, discount_value NUMERIC(10, 2) DEFAULT NULL, max_uses INT DEFAULT NULL, uses_count INT NOT NULL, UNIQUE INDEX UNIQ_1F1512DD372BEC9A (coupon_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE campaign_product (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, campaign_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_7BF09881F639F774 (campaign_id), INDEX IDX_7BF098814584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE campaign_product ADD CONSTRAINT FK_7BF09881F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('ALTER TABLE campaign_product ADD CONSTRAINT FK_7BF098814584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE orders ADD coupon_code VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_product DROP FOREIGN KEY FK_7BF09881F639F774');
        $this->addSql('ALTER TABLE campaign_product DROP FOREIGN KEY FK_7BF098814584665A');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE campaign_product');
        $this->addSql('ALTER TABLE orders DROP coupon_code');
    }
}
