<?php

namespace App\Command;

use App\Entity\LeaderboardEntry;
use App\Entity\Puzzle;
use App\Entity\Student;
use App\Entity\Submission;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-puzzle-data',
    description: 'Generate seed puzzle data for testing'
)]
class GeneratePuzzleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameService $gameService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('students', null, InputOption::VALUE_OPTIONAL, 'Number of students to create', 5)
            ->addOption('submissions', null, InputOption::VALUE_OPTIONAL, 'Number of submissions per student', 3)
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing data before generating')
            ->setHelp('This command generates sample puzzle data for testing purposes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numStudents = (int) $input->getOption('students');
        $numSubmissions = (int) $input->getOption('submissions');
        $clearData = $input->getOption('clear');

        if ($clearData) {
            $io->info('Clearing existing data...');
            $this->clearExistingData();
        }

        $io->info("Generating {$numStudents} students with {$numSubmissions} submissions each...");

        $sampleWords = [
            'HEAT', 'STAR', 'MIND', 'FIRE', 'WIND', 'TREE', 'BOOK', 'FISH', 'BIRD', 'DOOR',
            'LAMP', 'DESK', 'CARD', 'BALL', 'CAKE', 'MILK', 'BREAD', 'WATER', 'SUN', 'MOON'
        ];

        for ($i = 1; $i <= $numStudents; $i++) {
            $sessionId = "student_{$i}";
            
            // Create puzzle
            $puzzle = $this->gameService->createPuzzle($sessionId);
            $io->text("Created puzzle for {$sessionId}: {$puzzle->getPuzzleString()}");

            // Generate random submissions
            $availableWords = $sampleWords;
            shuffle($availableWords);
            
            for ($j = 0; $j < $numSubmissions && $j < count($availableWords); $j++) {
                $word = $availableWords[$j];
                
                try {
                    $result = $this->gameService->submitWord($sessionId, $word);
                    $io->text("  - Submitted '{$word}' (score: {$result['score']})");
                } catch (\Exception $e) {
                    $io->text("  - Failed to submit '{$word}': {$e->getMessage()}");
                }
            }
        }

        // Generate some leaderboard entries
        $io->info('Generating leaderboard entries...');
        $this->generateLeaderboardEntries();

        $io->success('Puzzle data generated successfully!');
        
        return Command::SUCCESS;
    }

    private function clearExistingData(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Submission')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Puzzle')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Student')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\LeaderboardEntry')->execute();
        $this->entityManager->flush();
    }

    private function generateLeaderboardEntries(): void
    {
        $highScoringWords = [
            'HEAT' => 4,
            'STAR' => 4,
            'MIND' => 4,
            'FIRE' => 4,
            'WIND' => 4,
            'TREE' => 4,
            'BOOK' => 4,
            'FISH' => 4,
            'BIRD' => 4,
            'DOOR' => 4
        ];

        foreach ($highScoringWords as $word => $score) {
            $entry = new LeaderboardEntry();
            $entry->setWord($word);
            $entry->setScore($score);
            $this->entityManager->persist($entry);
        }

        $this->entityManager->flush();
    }
} 