<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250708152654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE leaderboard_entry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE puzzle_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE student_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE submission_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE leaderboard_entry (id INT NOT NULL, word VARCHAR(255) NOT NULL, score INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_word ON leaderboard_entry (word)');
        $this->addSql('COMMENT ON COLUMN leaderboard_entry.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE puzzle (id INT NOT NULL, puzzle_string VARCHAR(14) NOT NULL, remaining_letters VARCHAR(14) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN puzzle.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE student (id INT NOT NULL, puzzle_id INT NOT NULL, session_id VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_activity TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF33613FECDF ON student (session_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF33D9816812 ON student (puzzle_id)');
        $this->addSql('COMMENT ON COLUMN student.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN student.last_activity IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE submission (id INT NOT NULL, puzzle_id INT NOT NULL, word VARCHAR(255) NOT NULL, score INT NOT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DB055AF3D9816812 ON submission (puzzle_id)');
        $this->addSql('COMMENT ON COLUMN submission.submitted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33D9816812 FOREIGN KEY (puzzle_id) REFERENCES puzzle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3D9816812 FOREIGN KEY (puzzle_id) REFERENCES puzzle (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE leaderboard_entry_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE puzzle_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE student_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE submission_id_seq CASCADE');
        $this->addSql('ALTER TABLE student DROP CONSTRAINT FK_B723AF33D9816812');
        $this->addSql('ALTER TABLE submission DROP CONSTRAINT FK_DB055AF3D9816812');
        $this->addSql('DROP TABLE leaderboard_entry');
        $this->addSql('DROP TABLE puzzle');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE submission');
    }
}
