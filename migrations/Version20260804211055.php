<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804211055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shipping_settings (id INT AUTO_INCREMENT NOT NULL, cost NUMERIC(10, 2) NOT NULL, free_shipping_threshold NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        // Rândul unic de setări, cu valorile implicite din entitate — admin-ul îl editează din /admin, nu se creează altele.
        $this->addSql("INSERT INTO shipping_settings (cost, free_shipping_threshold) VALUES ('15.00', '200.00')");

        // Comenzile deja plasate nu au avut cost de transport calculat — 0.00 e cea mai corectă valoare istorică.
        $this->addSql("ALTER TABLE orders ADD shipping_cost NUMERIC(10, 2) NOT NULL DEFAULT '0.00'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE shipping_settings');
        $this->addSql('ALTER TABLE orders DROP shipping_cost');
    }
}
