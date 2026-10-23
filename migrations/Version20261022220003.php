<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261022220003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, full_name VARCHAR(80) NOT NULL, phone VARCHAR(20) NOT NULL, email_verified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, city_id INT NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), UNIQUE INDEX UNIQ_88BDF3E9444F97DD (phone), INDEX idx_user_city_role (city_id, role), INDEX IDX_88BDF3E98BAC62AF (city_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, UNIQUE INDEX UNIQ_2D5B02345E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) NOT NULL, payload JSON NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_notification_user_created (user_id, created_at), INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE push_subscription (id INT AUTO_INCREMENT NOT NULL, endpoint VARCHAR(500) NOT NULL, `keys` JSON NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_562830F3C4420F7B (endpoint), INDEX IDX_562830F3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service_request (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, is_urgent TINYINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, seen_at DATETIME DEFAULT NULL, answered_at DATETIME DEFAULT NULL, escalated_at DATETIME DEFAULT NULL, client_id INT NOT NULL, worker_id INT NOT NULL, INDEX idx_request_worker_status (worker_id, status), INDEX idx_request_client_created (client_id, created_at), INDEX IDX_F413DD0319EB6921 (client_id), INDEX IDX_F413DD036B20BA36 (worker_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trade (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, UNIQUE INDEX UNIQ_7E1A43665E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE worker_profile (id INT AUTO_INCREMENT NOT NULL, rate_tnd INT DEFAULT NULL, is_available TINYINT NOT NULL, phone_public TINYINT NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, trade_id INT NOT NULL, UNIQUE INDEX UNIQ_B5B8D142A76ED395 (user_id), INDEX idx_worker_trade_available (trade_id, is_available), INDEX IDX_B5B8D142C2D9760 (trade_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E98BAC62AF FOREIGN KEY (city_id) REFERENCES city (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE push_subscription ADD CONSTRAINT FK_562830F3A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD0319EB6921 FOREIGN KEY (client_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD036B20BA36 FOREIGN KEY (worker_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE worker_profile ADD CONSTRAINT FK_B5B8D142A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE worker_profile ADD CONSTRAINT FK_B5B8D142C2D9760 FOREIGN KEY (trade_id) REFERENCES trade (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user DROP FOREIGN KEY FK_88BDF3E98BAC62AF');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE push_subscription DROP FOREIGN KEY FK_562830F3A76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD0319EB6921');
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD036B20BA36');
        $this->addSql('ALTER TABLE worker_profile DROP FOREIGN KEY FK_B5B8D142A76ED395');
        $this->addSql('ALTER TABLE worker_profile DROP FOREIGN KEY FK_B5B8D142C2D9760');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE push_subscription');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE service_request');
        $this->addSql('DROP TABLE trade');
        $this->addSql('DROP TABLE worker_profile');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
