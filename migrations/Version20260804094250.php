<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804094250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campaign ADD discount_value_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD internal_code VARCHAR(100) DEFAULT NULL, ADD external_code VARCHAR(100) DEFAULT NULL');

        // CampaignType::PercentageDiscount/FixedDiscount s-au unificat în CampaignType::Discount,
        // distincția mutându-se în discount_value_type (vezi App\Enum\DiscountValueType).
        $this->addSql("UPDATE campaign SET discount_value_type = 'percentage', type = 'discount' WHERE type = 'percentage_discount'");
        $this->addSql("UPDATE campaign SET discount_value_type = 'fixed', type = 'discount' WHERE type = 'fixed_discount'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE campaign SET type = 'percentage_discount' WHERE type = 'discount' AND discount_value_type = 'percentage'");
        $this->addSql("UPDATE campaign SET type = 'fixed_discount' WHERE type = 'discount' AND discount_value_type = 'fixed'");

        $this->addSql('ALTER TABLE campaign DROP discount_value_type');
        $this->addSql('ALTER TABLE product DROP internal_code, DROP external_code');
    }
}
