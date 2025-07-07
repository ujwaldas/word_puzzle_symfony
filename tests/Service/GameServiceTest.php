<?php

namespace App\Tests\Service;

use App\Entity\Puzzle;
use App\Entity\Student;
use App\Entity\Submission;
use App\Repository\LeaderboardEntryRepository;
use App\Repository\PuzzleRepository;
use App\Repository\StudentRepository;
use App\Repository\SubmissionRepository;
use App\Service\DictionaryService;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GameServiceTest extends TestCase
{
    private GameService $gameService;
    private EntityManagerInterface $entityManager;
    private DictionaryService $dictionaryService;
    private PuzzleRepository $puzzleRepository;
    private StudentRepository $studentRepository;
    private SubmissionRepository $submissionRepository;
    private LeaderboardEntryRepository $leaderboardRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dictionaryService = $this->createMock(DictionaryService::class);
        $this->puzzleRepository = $this->createMock(PuzzleRepository::class);
        $this->studentRepository = $this->createMock(StudentRepository::class);
        $this->submissionRepository = $this->createMock(SubmissionRepository::class);
        $this->leaderboardRepository = $this->createMock(LeaderboardEntryRepository::class);

        $this->gameService = new GameService(
            $this->entityManager,
            $this->dictionaryService,
            $this->puzzleRepository,
            $this->studentRepository,
            $this->submissionRepository,
            $this->leaderboardRepository
        );
    }

    public function testCreatePuzzleWithNewStudent(): void
    {
        $sessionId = 'test_session';
        
        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $puzzle = $this->gameService->createPuzzle($sessionId);

        $this->assertInstanceOf(Puzzle::class, $puzzle);
        $this->assertEquals(14, strlen($puzzle->getPuzzleString()));
        $this->assertTrue($puzzle->isActive());
        $this->assertEquals($puzzle->getPuzzleString(), $puzzle->getRemainingLetters());
    }

    public function testCreatePuzzleWithExistingActivePuzzle(): void
    {
        $sessionId = 'test_session';
        $existingPuzzle = new Puzzle();
        $existingPuzzle->setPuzzleString('ETAOINSHRDLUCM');
        $existingPuzzle->setIsActive(true);

        $existingStudent = new Student();
        $existingStudent->setSessionId($sessionId);
        $existingStudent->setPuzzle($existingPuzzle);

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($existingStudent);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $puzzle = $this->gameService->createPuzzle($sessionId);

        $this->assertSame($existingPuzzle, $puzzle);
    }

    public function testSubmitWordSuccess(): void
    {
        $sessionId = 'test_session';
        $word = 'HEAT';
        
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('HEATSTARMINDFIRE');

        $student = new Student();
        $student->setSessionId($sessionId);
        $student->setPuzzle($puzzle);

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($student);

        $this->submissionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->dictionaryService->expects($this->once())
            ->method('isValidEnglishWord')
            ->with('HEAT')
            ->willReturn(true);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->gameService->submitWord($sessionId, $word);

        $this->assertEquals('HEAT', $result['word']);
        $this->assertEquals(4, $result['score']);
        $this->assertEquals(4, $result['totalScore']);
        $this->assertFalse($result['isComplete']);
        $this->assertEquals('STARMINDFIRE', $result['remainingLetters']);
    }

    public function testSubmitWordEmptyWord(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Word cannot be empty');

        $this->gameService->submitWord('test_session', '');
    }

    public function testSubmitWordTooLong(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Word is too long');

        $this->gameService->submitWord('test_session', 'SUPERLONGWORDTHATEXCEEDSTHELIMIT');
    }

    public function testSubmitWordInvalidCharacters(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Word must contain only letters');

        $this->gameService->submitWord('test_session', 'HEAT123');
    }

    public function testSubmitWordNotValidEnglishWord(): void
    {
        $sessionId = 'test_session';
        $word = 'INVALID';
        
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('HEATSTARMINDFIRE');

        $student = new Student();
        $student->setSessionId($sessionId);
        $student->setPuzzle($puzzle);

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($student);

        $this->submissionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->dictionaryService->expects($this->once())
            ->method('isValidEnglishWord')
            ->with('INVALID')
            ->willReturn(false);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Not a valid English word');

        $this->gameService->submitWord($sessionId, $word);
    }

    public function testSubmitWordCannotBeFormed(): void
    {
        $sessionId = 'test_session';
        $word = 'HEAT';
        
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('STARMINDFIRE');
        $puzzle->setRemainingLetters('STARMINDFIRE');

        $student = new Student();
        $student->setSessionId($sessionId);
        $student->setPuzzle($puzzle);

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($student);

        $this->submissionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->dictionaryService->expects($this->once())
            ->method('isValidEnglishWord')
            ->with('HEAT')
            ->willReturn(true);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Word cannot be formed from remaining letters');

        $this->gameService->submitWord($sessionId, $word);
    }

    public function testSubmitWordAlreadySubmitted(): void
    {
        $sessionId = 'test_session';
        $word = 'HEAT';
        
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('HEATSTARMINDFIRE');

        $student = new Student();
        $student->setSessionId($sessionId);
        $student->setPuzzle($puzzle);

        $existingSubmission = new Submission();
        $existingSubmission->setWord('HEAT');

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($student);

        $this->submissionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existingSubmission);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Word already submitted');

        $this->gameService->submitWord($sessionId, $word);
    }

    public function testSubmitWordNoActivePuzzle(): void
    {
        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No active puzzle found for this session');

        $this->gameService->submitWord('test_session', 'HEAT');
    }

    public function testGetPuzzleState(): void
    {
        $sessionId = 'test_session';
        
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('STARMINDFIRE');
        $puzzle->setIsActive(true);

        $submission = new Submission();
        $submission->setWord('HEAT');
        $submission->setScore(4);

        $student = new Student();
        $student->setSessionId($sessionId);
        $student->setPuzzle($puzzle);

        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['sessionId' => $sessionId])
            ->willReturn($student);

        $state = $this->gameService->getPuzzleState($sessionId);

        $this->assertEquals('HEATSTARMINDFIRE', $state['puzzleString']);
        $this->assertEquals('STARMINDFIRE', $state['remainingLetters']);
        $this->assertTrue($state['isActive']);
        $this->assertCount(1, $state['submissions']);
        $this->assertEquals('HEAT', $state['submissions'][0]['word']);
        $this->assertEquals(4, $state['submissions'][0]['score']);
    }

    public function testGetPuzzleStateNotFound(): void
    {
        $this->studentRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No puzzle found for this session');

        $this->gameService->getPuzzleState('test_session');
    }

    public function testGetLeaderboard(): void
    {
        $mockEntries = [
            (object) ['getWord' => 'HEAT', 'getScore' => 4, 'getCreatedAt' => new \DateTimeImmutable()],
            (object) ['getWord' => 'STAR', 'getScore' => 4, 'getCreatedAt' => new \DateTimeImmutable()]
        ];

        $this->leaderboardRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['score' => 'DESC', 'createdAt' => 'ASC'], 10)
            ->willReturn($mockEntries);

        $leaderboard = $this->gameService->getLeaderboard();

        $this->assertCount(2, $leaderboard);
        $this->assertEquals('HEAT', $leaderboard[0]['word']);
        $this->assertEquals(4, $leaderboard[0]['score']);
    }

    public function testPuzzleCanUseLetters(): void
    {
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('HEATSTARMINDFIRE');

        $this->assertTrue($puzzle->canUseLetters('HEAT'));
        $this->assertTrue($puzzle->canUseLetters('STAR'));
        $this->assertFalse($puzzle->canUseLetters('ZEBRA'));
        $this->assertFalse($puzzle->canUseLetters('HEATT')); // Too many T's
    }

    public function testPuzzleUseLetters(): void
    {
        $puzzle = new Puzzle();
        $puzzle->setPuzzleString('HEATSTARMINDFIRE');
        $puzzle->setRemainingLetters('HEATSTARMINDFIRE');

        $puzzle->useLetters('HEAT');
        
        $this->assertEquals('STARMINDFIRE', $puzzle->getRemainingLetters());
        
        $puzzle->useLetters('STAR');
        
        $this->assertEquals('MINDFIRE', $puzzle->getRemainingLetters());
    }

    public function testPuzzleTotalScore(): void
    {
        $puzzle = new Puzzle();
        
        $submission1 = new Submission();
        $submission1->setScore(4);
        
        $submission2 = new Submission();
        $submission2->setScore(3);
        
        $puzzle->addSubmission($submission1);
        $puzzle->addSubmission($submission2);
        
        $this->assertEquals(7, $puzzle->getTotalScore());
    }
} 