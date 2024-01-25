<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240122085853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OAuth bundle tables';
    }

    public function up(Schema $schema): void
    {
        $this->upAuthCodeTable();
        $this->upClientTable();
        $this->upRefreshTokenTable();
        $this->upAccessTokenTable();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE oauth2_authorization_code');

        $this->addSql('DROP TABLE oauth2_client');

        $this->addSql('DROP TABLE oauth2_refresh_token');

        $this->addSql('DROP TABLE oauth2_access_token');
    }

    private function upAuthCodeTable(): void
    {
        $this->addSql('CREATE TABLE oauth2_authorization_code 
        (identifier CHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE 
        `utf8mb4_unicode_ci`, client VARCHAR(32) CHARACTER SET utf8mb4 
        NOT NULL COLLATE `utf8mb4_unicode_ci`, expiry DATETIME 
        NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_identifier 
        VARCHAR(128) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE 
        `utf8mb4_unicode_ci`, scopes TEXT CHARACTER SET utf8mb4 
        DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT 
        \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) NOT NULL, 
        INDEX IDX_509FEF5FC7440455 (client), PRIMARY KEY(identifier)) 
        DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` 
        ENGINE = InnoDB COMMENT = \'\' ');
    }

    private function upClientTable(): void
    {
        $this->addSql('CREATE TABLE oauth2_client (identifier VARCHAR(32) 
        CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
        name VARCHAR(128) CHARACTER SET utf8mb4 NOT NULL COLLATE 
        `utf8mb4_unicode_ci`, secret VARCHAR(128) CHARACTER SET utf8mb4 
        DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, redirect_uris 
        TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE 
        `utf8mb4_unicode_ci` COMMENT \'(DC2Type:oauth2_redirect_uri)\', 
        grants TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE 
        `utf8mb4_unicode_ci` COMMENT \'(DC2Type:oauth2_grant)\', 
        scopes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE 
        `utf8mb4_unicode_ci` COMMENT \'(DC2Type:oauth2_scope)\', 
        active TINYINT(1) NOT NULL, allow_plain_text_pkce TINYINT(1) 
        DEFAULT 0 NOT NULL, PRIMARY KEY(identifier)) DEFAULT 
        CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` 
        ENGINE = InnoDB COMMENT = \'\' ');
    }

    private function upRefreshTokenTable(): void
    {
        $this->addSql('CREATE TABLE oauth2_refresh_token 
        (identifier CHAR(80) CHARACTER SET utf8mb4 NOT NULL 
        COLLATE `utf8mb4_unicode_ci`, access_token CHAR(80) 
        CHARACTER SET utf8mb4 DEFAULT NULL COLLATE 
        `utf8mb4_unicode_ci`, expiry DATETIME NOT NULL 
        COMMENT \'(DC2Type:datetime_immutable)\', revoked 
        TINYINT(1) NOT NULL, INDEX IDX_4DD90732B6A2DD68 
        (access_token), PRIMARY KEY(identifier)) DEFAULT 
        CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` 
        ENGINE = InnoDB COMMENT = \'\' ');
    }

    private function upAccessTokenTable(): void
    {
        $this->addSql('CREATE TABLE oauth2_access_token 
        (identifier CHAR(80) CHARACTER SET utf8mb4 
        NOT NULL COLLATE `utf8mb4_unicode_ci`, client VARCHAR(32) 
        CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, 
        expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
        user_identifier VARCHAR(128) CHARACTER SET utf8mb4 DEFAULT 
        NULL COLLATE `utf8mb4_unicode_ci`, scopes TEXT CHARACTER SET 
        utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` 
        COMMENT \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) 
        NOT NULL, INDEX IDX_454D9673C7440455 (client), 
        PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4
         COLLATE `utf8mb4_unicode_ci` 
         ENGINE = InnoDB COMMENT = \'\' ');
    }
}
