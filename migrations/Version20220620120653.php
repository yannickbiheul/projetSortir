<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620120653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annulation (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sortie ADD annulation_id INT DEFAULT NULL, CHANGE infos_sortie infos_sortie LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2C7E10D1C FOREIGN KEY (annulation_id) REFERENCES annulation (id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2C7E10D1C ON sortie (annulation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2C7E10D1C');
        $this->addSql('DROP TABLE annulation');
        $this->addSql('DROP INDEX IDX_3C3FD3F2C7E10D1C ON sortie');
        $this->addSql('ALTER TABLE sortie DROP annulation_id, CHANGE infos_sortie infos_sortie VARCHAR(500) DEFAULT NULL');
    }
}
