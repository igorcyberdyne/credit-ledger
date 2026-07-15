<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715095007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, uuid BINARY(16) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, deleted_by_id INT DEFAULT NULL, shop_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_81398E09D17F50A6 (uuid), INDEX IDX_81398E09C76F1F52 (deleted_by_id), INDEX IDX_81398E094D16C4DD (shop_id), INDEX IDX_81398E09B03A8386 (created_by_id), INDEX IDX_81398E09896DBBDE (updated_by_id), INDEX idx_customer_lastname (lastname), INDEX idx_customer_phone (phone), UNIQUE INDEX uniq_shop_phone (shop_id, phone), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ledger_entry (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, uuid BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, amount_in_cents INT NOT NULL, description VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(255) DEFAULT NULL, occurred_at DATETIME DEFAULT NULL, deleted_by_id INT DEFAULT NULL, shop_id INT NOT NULL, customer_id INT NOT NULL, reversed_entry_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_64272A69D17F50A6 (uuid), INDEX IDX_64272A69C76F1F52 (deleted_by_id), INDEX IDX_64272A694D16C4DD (shop_id), INDEX IDX_64272A699395C3F3 (customer_id), UNIQUE INDEX UNIQ_64272A6914AB0CB5 (reversed_entry_id), INDEX IDX_64272A69B03A8386 (created_by_id), INDEX IDX_64272A69896DBBDE (updated_by_id), INDEX idx_ledger_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shop (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, uuid BINARY(16) NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL, address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, country VARCHAR(120) NOT NULL, phone VARCHAR(30) DEFAULT NULL, currency VARCHAR(5) NOT NULL, timezone VARCHAR(60) NOT NULL, deleted_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_AC6A4CA2D17F50A6 (uuid), UNIQUE INDEX UNIQ_AC6A4CA2989D9B62 (slug), INDEX IDX_AC6A4CA2C76F1F52 (deleted_by_id), INDEX idx_shop_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, uuid BINARY(16) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) DEFAULT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, deleted_by_id INT DEFAULT NULL, shop_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649D17F50A6 (uuid), INDEX IDX_8D93D649C76F1F52 (deleted_by_id), INDEX IDX_8D93D6494D16C4DD (shop_id), UNIQUE INDEX uniq_user_email (email), UNIQUE INDEX uniq_user_phone (phone), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E094D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A694D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A699395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A6914AB0CB5 FOREIGN KEY (reversed_entry_id) REFERENCES ledger_entry (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ledger_entry ADD CONSTRAINT FK_64272A69896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA2C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09C76F1F52');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E094D16C4DD');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09896DBBDE');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69C76F1F52');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A694D16C4DD');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A699395C3F3');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A6914AB0CB5');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69B03A8386');
        $this->addSql('ALTER TABLE ledger_entry DROP FOREIGN KEY FK_64272A69896DBBDE');
        $this->addSql('ALTER TABLE shop DROP FOREIGN KEY FK_AC6A4CA2C76F1F52');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649C76F1F52');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494D16C4DD');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE ledger_entry');
        $this->addSql('DROP TABLE shop');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
