<?php

namespace App\Service;

use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class DictionaryService
{
    private const DICTIONARY_FILE = __DIR__ . '/../../data/words.txt';
    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_KEY = 'dictionary_words';
    private const LETTER_FREQUENCY_CACHE_KEY = 'letter_frequency_index';

    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function isValidEnglishWord(string $word): bool
    {
        $word = strtolower(trim($word));
        
        if (empty($word) || !ctype_alpha($word) || strlen($word) > 14) {
            return false;
        }

        $dictionary = $this->getDictionaryWords();
        return in_array($word, $dictionary, true);
    }

    public function getWordDefinition(string $word): ?array
    {
        // Since we're using a simple word list, we can't provide definitions
        // This method is kept for compatibility but returns null
        return null;
    }

    /**
     * Calculate all possible words that can be formed from the remaining letters
     * 
     * @param string $remainingLetters The letters available to form words
     * @param int $maxWords Maximum number of words to return (default: 50)
     * @return array Array of valid words that can be formed
     */
    public function calculateRemainingWords(string $remainingLetters, int $maxWords = 5000000): array
    {
        $remainingLetters = strtolower(trim($remainingLetters));
        
        if (empty($remainingLetters) || !ctype_alpha($remainingLetters)) {
            return [];
        }

        // Get letter frequency of remaining letters
        $letterFrequency = $this->getLetterFrequency($remainingLetters);
        // Get optimized word candidates
        $candidates = $this->getWordCandidates($letterFrequency, $maxWords * 3);
        // Filter and validate candidates
        $validWords = $this->filterValidWords($candidates, $letterFrequency);
        
        // Sort by length (longer words first) and then alphabetically
        usort($validWords, function($a, $b) {
            $lengthDiff = strlen($b) - strlen($a);
            return $lengthDiff !== 0 ? $lengthDiff : strcmp($a, $b);
        });
        
        return array_slice($validWords, 0, $maxWords);
    }

    /**
     * Get letter frequency from a string of letters
     * 
     * @param string $letters
     * @return array Associative array with letter as key and count as value
     */
    private function getLetterFrequency(string $letters): array
    {
        $frequency = [];
        $lettersArray = str_split($letters);
        
        foreach ($lettersArray as $letter) {
            $frequency[$letter] = ($frequency[$letter] ?? 0) + 1;
        }
        
        return $frequency;
    }

    /**
     * Get word candidates based on letter frequency
     * 
     * @param array $letterFrequency
     * @param int $limit
     * @return array
     */
    private function getWordCandidates(array $letterFrequency, int $limit): array
    {
        $dictionary = $this->getDictionaryWords();
        $candidates = [];
        $letterKeys = array_keys($letterFrequency);
        
        foreach ($dictionary as $word) {
            // Quick pre-filter: word must contain at least one of our letters
            if (!$this->hasCommonLetters($word, $letterKeys)) {
                continue;
            }
            
            // Check if word can be formed from available letters
            if ($this->canFormWord($word, $letterFrequency)) {
                $candidates[] = $word;
                
                if (count($candidates) >= $limit) {
                    break;
                }
            }
        }
        
        return $candidates;
    }

    /**
     * Check if a word has any common letters with available letters
     * 
     * @param string $word
     * @param array $availableLetters
     * @return bool
     */
    private function hasCommonLetters(string $word, array $availableLetters): bool
    {
        $wordLetters = str_split($word);
        return !empty(array_intersect($wordLetters, $availableLetters));
    }

    /**
     * Check if a word can be formed from the given letter frequency
     * 
     * @param string $word
     * @param array $letterFrequency
     * @return bool
     */
    private function canFormWord(string $word, array $letterFrequency): bool
    {
        $wordFrequency = $this->getLetterFrequency($word);
        
        foreach ($wordFrequency as $letter => $count) {
            if (!isset($letterFrequency[$letter]) || $letterFrequency[$letter] < $count) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Filter and validate words that can actually be formed
     * 
     * @param array $candidates
     * @param array $letterFrequency
     * @return array
     */
    private function filterValidWords(array $candidates, array $letterFrequency): array
    {
        $validWords = [];
        $remainingLetters = $letterFrequency;
        
        foreach ($candidates as $word) {
            if ($this->canFormWordWithRemainingLetters($word, $remainingLetters)) {
                $validWords[] = $word;
            }
        }
        
        return $validWords;
    }

    /**
     * Check if a word can be formed and update remaining letters
     * 
     * @param string $word
     * @param array $remainingLetters
     * @return bool
     */
    private function canFormWordWithRemainingLetters(string $word, array &$remainingLetters): bool
    {
        $wordFrequency = $this->getLetterFrequency($word);
        
        foreach ($wordFrequency as $letter => $count) {
            if (!isset($remainingLetters[$letter]) || $remainingLetters[$letter] < $count) {
                return false;
            }
        }
        
        // If we can form the word, update remaining letters
        foreach ($wordFrequency as $letter => $count) {
            $remainingLetters[$letter] -= $count;
            if ($remainingLetters[$letter] <= 0) {
                unset($remainingLetters[$letter]);
            }
        }
        
        return true;
    }

    /**
     * Get all possible word combinations from remaining letters
     * 
     * @param string $remainingLetters
     * @param int $maxCombinations
     * @return array Array of word combinations
     */
    public function getWordCombinations(string $remainingLetters, int $maxCombinations = 20): array
    {
        $remainingLetters = strtolower(trim($remainingLetters));
        
        if (empty($remainingLetters) || !ctype_alpha($remainingLetters)) {
            return [];
        }

        $letterFrequency = $this->getLetterFrequency($remainingLetters);
        $combinations = [];
        
        // Get all possible words
        $allWords = $this->calculateRemainingWords($remainingLetters, 100);
        
        // Generate combinations using backtracking
        $this->generateCombinations($allWords, $letterFrequency, [], $combinations, $maxCombinations);
        
        // Sort combinations by total score (longer words = higher score)
        usort($combinations, function($a, $b) {
            $scoreA = array_sum(array_map('strlen', $a));
            $scoreB = array_sum(array_map('strlen', $b));
            return $scoreB - $scoreA;
        });
        
        return array_slice($combinations, 0, $maxCombinations);
    }

    /**
     * Generate word combinations using backtracking algorithm
     * 
     * @param array $words
     * @param array $remainingLetters
     * @param array $currentCombination
     * @param array $combinations
     * @param int $maxCombinations
     */
    private function generateCombinations(
        array $words, 
        array $remainingLetters, 
        array $currentCombination, 
        array &$combinations, 
        int $maxCombinations
    ): void {
        if (count($combinations) >= $maxCombinations) {
            return;
        }
        
        // Add current combination if it's not empty
        if (!empty($currentCombination)) {
            $combinations[] = $currentCombination;
        }
        
        foreach ($words as $word) {
            if ($this->canFormWord($word, $remainingLetters)) {
                // Create new remaining letters after using this word
                $newRemainingLetters = $remainingLetters;
                $wordFrequency = $this->getLetterFrequency($word);
                
                foreach ($wordFrequency as $letter => $count) {
                    $newRemainingLetters[$letter] -= $count;
                    if ($newRemainingLetters[$letter] <= 0) {
                        unset($newRemainingLetters[$letter]);
                    }
                }
                
                // Recursively generate combinations
                $this->generateCombinations(
                    $words, 
                    $newRemainingLetters, 
                    array_merge($currentCombination, [$word]), 
                    $combinations, 
                    $maxCombinations
                );
            }
        }
    }

    /**
     * Get optimized dictionary words with letter frequency indexing
     * 
     * @return array
     */
    private function getDictionaryWords(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (CacheItem $item) {
            $item->expiresAfter(self::CACHE_TTL);
            
            if (!file_exists(self::DICTIONARY_FILE)) {
                throw new \RuntimeException('Dictionary file not found: ' . self::DICTIONARY_FILE);
            }

            $words = file(self::DICTIONARY_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Filter words to only include alphabetic characters and max 14 length
            $filteredWords = array_filter($words, function($word) {
                $word = trim($word);
                return !empty($word) && 
                       ctype_alpha($word) && 
                       strlen($word) <= 14;
            });

            // Convert to lowercase for case-insensitive comparison
            return array_map('strtolower', $filteredWords);
        });
    }

    /**
     * Get a sample of words from the dictionary (useful for testing)
     */
    public function getSampleWords(int $count = 10): array
    {
        $words = $this->getDictionaryWords();
        return array_slice($words, 0, $count);
    }

    /**
     * Get word count in dictionary
     */
    public function getWordCount(): int
    {
        return count($this->getDictionaryWords());
    }

    /**
     * Get statistics about remaining words
     * 
     * @param string $remainingLetters
     * @return array Statistics about possible words
     */
    public function getRemainingWordsStats(string $remainingLetters): array
    {
        $words = $this->calculateRemainingWords($remainingLetters, 1000);
        
        if (empty($words)) {
            return [
                'total_words' => 0,
                'longest_word' => '',
                'shortest_word' => '',
                'average_length' => 0,
                'length_distribution' => []
            ];
        }
        
        $lengths = array_map('strlen', $words);
        $lengthDistribution = array_count_values($lengths);
        ksort($lengthDistribution);
        
        return [
            'total_words' => count($words),
            'longest_word' => $words[0], // Already sorted by length
            'shortest_word' => end($words),
            'average_length' => round(array_sum($lengths) / count($lengths), 2),
            'length_distribution' => $lengthDistribution
        ];
    }
} 