<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240106091309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration';
    }

    public function up(Schema $schema): void
    {
        // Create the 'users' table
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            salt VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            password_requested_at DATETIME DEFAULT NULL,
            access_token VARCHAR(255) DEFAULT NULL,
            authentication_date DATETIME DEFAULT NULL,
            remember_me BOOLEAN DEFAULT NULL,
            reset_password_token VARCHAR(255) DEFAULT NULL,
            roles JSON DEFAULT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            status INT NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX username_UNIQUE (username),
            UNIQUE INDEX email_UNIQUE (email)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'users_profiles' table
        $this->addSql('CREATE TABLE users_profiles (
            id INT AUTO_INCREMENT NOT NULL,
            full_name VARCHAR(255) DEFAULT NULL,
            phone_number VARCHAR(15) DEFAULT NULL,
            users_id INT NOT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            status INT NOT NULL,
            PRIMARY KEY (id),
            INDEX fk_users_profiles_users_idx (users_id),
            CONSTRAINT FK_users_profiles_users FOREIGN KEY (users_id) REFERENCES users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'roles' table
        $this->addSql('CREATE TABLE roles (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            status INT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX display_name_UNIQUE (display_name),
            UNIQUE INDEX name_UNIQUE (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'refresh_tokens' table
        $this->addSql('CREATE TABLE refresh_tokens (
            id VARCHAR(255) NOT NULL,
            username VARCHAR(255) DEFAULT NULL,
            role VARCHAR(255) DEFAULT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            valid BOOLEAN NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'passwords_history' table
        $this->addSql('CREATE TABLE passwords_history (
            id INT AUTO_INCREMENT NOT NULL,
            password_salt VARCHAR(255) NOT NULL,
            password_hash LONGTEXT NOT NULL,
            password_date DATETIME NOT NULL,
            expire_notification_date DATETIME DEFAULT NULL,
            users_id INT NOT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX fk_passwords_history_users1_idx (users_id),
            CONSTRAINT FK_passwords_history_users FOREIGN KEY (users_id) REFERENCES users (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'authentication_attempts' table
        $this->addSql('CREATE TABLE authentication_attempts (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(255) NOT NULL,
            attempts INT DEFAULT NULL,
            last_attempt_date DATETIME NOT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX email_UNIQUE (email)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create the 'books' table
        $this->addSql('CREATE TABLE books (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            status INT NOT NULL,
            author VARCHAR(255) NOT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insert data into the 'users' table
        $this->addSql('INSERT INTO users (id, username, email, salt, password, password_requested_at, access_token, authentication_date, remember_me, reset_password_token, roles, date_updated, date_created, status) VALUES
            (112211, \'mike.smith@domain.com\', \'mike.smith@domain.com\', \'rjY2zwnbFNIQPE0RpG0C6x3N9iLOWKdAM\', \'$2y$13$mfG3lg/YlgGEowpxpnKNTOXpVsHH93SL8kLJQWYtJASz7Fjsdm/NS\', NULL, \'027bfe25-35c5-4c3f-b7aa-8fd258649f79\', \'2024-01-06 08:44:17\', NULL, \'KEiZsjk4odKm97W55o35vwFvkT0knekdaS\', \'["ADMIN"]\', \'2024-01-06 11:44:17\', \'2023-11-10 11:31:57\', 1),
            (112212, \'mike.smith+1@domain.com\', \'mike.smith+1@domain.com\', \'XZQ2KXk1KkJfT0A3XzrM3r8ycZZeeyO8aKrIqTos\', \'$2y$13$mg3sRcCLEz1Zetwmb66E3.KcBgXkkXqLUKsnUBsnt2QP0Sc1E6dkO\', NULL, \'a7044144-b5c9-4d61-bf9c-97c08e47dfa3\', \'2023-11-10 09:32:23\', NULL, \'aWvvBaC0VZBglwQY6Yc89qp3d2XmAq\', \'["USER"]\', \'2023-11-15 09:34:40\', \'2023-11-10 11:32:21\', 1)');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('DROP TABLE books');
        $this->addSql('DROP TABLE authentication_attempts');
        $this->addSql('DROP TABLE passwords_history');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE users_profiles');
        $this->addSql('DROP TABLE users');
    }
}
