<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823163443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_tokens (user_id VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_used TINYINT(1) NOT NULL, token_value VARCHAR(255) NOT NULL, INDEX idx_user_id (user_id), INDEX idx_expires_at (expires_at), INDEX idx_created_at (created_at), PRIMARY KEY(token_value)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_reset_tokens');
    }
}
