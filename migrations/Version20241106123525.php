<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241106123525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB960E92400 ON course (character_code)');
        $this->addSql('DROP INDEX uniq_723705d1cc405842');
        $this->addSql('CREATE INDEX IDX_723705D1CC405842 ON transaction (billing_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_723705D1CC405842');
        $this->addSql('CREATE UNIQUE INDEX uniq_723705d1cc405842 ON transaction (billing_user_id)');
        $this->addSql('DROP INDEX UNIQ_169E6FB960E92400');
    }
}
