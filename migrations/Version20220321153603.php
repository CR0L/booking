<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220321153603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classroom (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classroom_reservation (id INT AUTO_INCREMENT NOT NULL, classroom_id INT DEFAULT NULL, reserved_by_id INT DEFAULT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL, max_students INT NOT NULL, INDEX IDX_B7E988316278D5A8 (classroom_id), INDEX IDX_B7E98831BCDB4AF4 (reserved_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lecture_reservation (id INT AUTO_INCREMENT NOT NULL, reserved_by_id INT NOT NULL, classroom_reservation_id INT NOT NULL, INDEX IDX_33F25ED1BCDB4AF4 (reserved_by_id), INDEX IDX_33F25ED1696402BF (classroom_reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classroom_reservation ADD CONSTRAINT FK_B7E988316278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id)');
        $this->addSql('ALTER TABLE classroom_reservation ADD CONSTRAINT FK_B7E98831BCDB4AF4 FOREIGN KEY (reserved_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lecture_reservation ADD CONSTRAINT FK_33F25ED1BCDB4AF4 FOREIGN KEY (reserved_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lecture_reservation ADD CONSTRAINT FK_33F25ED1696402BF FOREIGN KEY (classroom_reservation_id) REFERENCES classroom_reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom_reservation DROP FOREIGN KEY FK_B7E988316278D5A8');
        $this->addSql('ALTER TABLE lecture_reservation DROP FOREIGN KEY FK_33F25ED1696402BF');
        $this->addSql('DROP TABLE classroom');
        $this->addSql('DROP TABLE classroom_reservation');
        $this->addSql('DROP TABLE lecture_reservation');
    }
}
