<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240121122200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user (id BINARY(16) NOT NULL, 
        email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE 
        `utf8mb4_unicode_ci`, initials VARCHAR(255) CHARACTER SET 
        utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) 
        CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
        confirmed TINYINT(1) NOT NULL, roles JSON NOT NULL COMMENT 
        \'(DC2Type:json)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), 
        PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE 
        `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
