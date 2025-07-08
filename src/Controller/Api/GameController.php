<?php

namespace App\Controller\Api;

use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api/game', name: 'api_game_')]
class GameController extends AbstractController
{
    public function __construct(
        private GameService $gameService
    ) {
    }

    #[Route('/puzzle', name: 'create_puzzle', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/game/puzzle",
     *     summary="Create a new puzzle",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sessionId", type="string", example="student123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Puzzle created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="puzzleString", type="string", example="ETAOINSHRDLUCM"),
     *             @OA\Property(property="remainingLetters", type="string", example="ETAOINSHRDLUCM"),
     *             @OA\Property(property="totalScore", type="integer", example=0),
     *             @OA\Property(property="isActive", type="boolean", example=true),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function createPuzzle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sessionId = $data['sessionId'] ?? null;

        if (!$sessionId) {
            return $this->json(['error' => 'Session ID is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $puzzle = $this->gameService->createPuzzle($sessionId);
            $state = $this->gameService->getPuzzleState($sessionId);

            return $this->json($state, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/submit', name: 'submit_word', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/game/submit",
     *     summary="Submit a word attempt",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sessionId", type="string", example="student123"),
     *             @OA\Property(property="word", type="string", example="HEAT")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Word submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="word", type="string", example="HEAT"),
     *             @OA\Property(property="score", type="integer", example=4),
     *             @OA\Property(property="totalScore", type="integer", example=15),
     *             @OA\Property(property="remainingLetters", type="string", example="OINSHRDLUCM"),
     *             @OA\Property(property="isComplete", type="boolean", example=false),
     *             @OA\Property(property="submissionId", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid word or game state",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Not a valid English word")
     *         )
     *     )
     * )
     */
    public function submitWord(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sessionId = $data['sessionId'] ?? null;
        $word = $data['word'] ?? null;

        if (!$sessionId || !$word) {
            return $this->json(['error' => 'Student ID and word are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->gameService->submitWord($sessionId, $word);
            return $this->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException 
                ? $e->getStatusCode() 
                : Response::HTTP_INTERNAL_SERVER_ERROR;
            
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    #[Route('/state/{sessionId}', name: 'get_state', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/game/state/{sessionId}",
     *     summary="Get current puzzle state",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Puzzle state retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="puzzleString", type="string", example="ETAOINSHRDLUCM"),
     *             @OA\Property(property="remainingLetters", type="string", example="OINSHRDLUCM"),
     *             @OA\Property(property="totalScore", type="integer", example=15),
     *             @OA\Property(property="isActive", type="boolean", example=true),
     *             @OA\Property(property="submissions", type="array", @OA\Items(
     *                 @OA\Property(property="word", type="string"),
     *                 @OA\Property(property="score", type="integer"),
     *                 @OA\Property(property="submittedAt", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No puzzle found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No puzzle found for this session")
     *         )
     *     )
     * )
     */
    public function getPuzzleState(string $sessionId): JsonResponse
    {
        try {
            $state = $this->gameService->getPuzzleState($sessionId);
            return $this->json($state, Response::HTTP_OK);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException 
                ? $e->getStatusCode() 
                : Response::HTTP_INTERNAL_SERVER_ERROR;
            
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    #[Route('/leaderboard', name: 'get_leaderboard', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/game/leaderboard",
     *     summary="Get top 10 leaderboard",
     *     @OA\Response(
     *         response=200,
     *         description="Leaderboard retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="word", type="string", example="HEAT"),
     *                 @OA\Property(property="score", type="integer", example=4),
     *                 @OA\Property(property="createdAt", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function getLeaderboard(): JsonResponse
    {
        try {
            $leaderboard = $this->gameService->getLeaderboard();
            return $this->json($leaderboard, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/end', name: 'end_game', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/game/end",
     *     summary="End the game",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sessionId", type="string", example="student123"),
     *             @OA\Property(property="remainingLetters", type="string", example="OINSHRDLUCM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Game ended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="remainingWords", type="array", example="['HEAT', 'HEAT', 'HEAT']"),
     *             @OA\Property(property="totalScore", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Session ID is required")
     *         )
     *     )
     * )
     */
    public function endGame(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sessionId = $data['sessionId'] ?? null;

        if (!$sessionId) {
            return $this->json(['error' => 'Session ID is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->gameService->endGame($sessionId);
            dump($result);die;
            return $this->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 