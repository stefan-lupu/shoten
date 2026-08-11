<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811202806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_suggested (product_source INT NOT NULL, product_target INT NOT NULL, INDEX IDX_D722822E3DF63ED7 (product_source), INDEX IDX_D722822E24136E58 (product_target), PRIMARY KEY (product_source, product_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_suggested ADD CONSTRAINT FK_D722822E3DF63ED7 FOREIGN KEY (product_source) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_suggested ADD CONSTRAINT FK_D722822E24136E58 FOREIGN KEY (product_target) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_suggested DROP FOREIGN KEY FK_D722822E3DF63ED7');
        $this->addSql('ALTER TABLE product_suggested DROP FOREIGN KEY FK_D722822E24136E58');
        $this->addSql('DROP TABLE product_suggested');
    }
}
