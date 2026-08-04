<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804214833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Utilizatorii deja existenți sunt considerați verificați (grandfathered) —
        // cerința se aplică doar înregistrărilor noi, de acum înainte.
        $this->addSql("ALTER TABLE user ADD is_verified TINYINT NOT NULL DEFAULT 1");
        $this->addSql('ALTER TABLE user ALTER is_verified DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP is_verified');
    }
}
