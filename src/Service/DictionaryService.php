<?php

namespace App\Service;

use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class DictionaryService
{
    private const DICTIONARY_FILE = __DIR__ . '/../../data/words.txt';
    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_KEY = 'dictionary_words';

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


    /**
     * Calculate all possible words that can be formed from the remaining letters
     * 
     * @param string $remainingLetters The letters available to form words
     * @param int $maxWords Maximum number of words to return (default: 50)
     * @return array Array of valid words that can be formed
     */
    public function calculateRemainingWords(string $remainingLetters, int $maxWords = 1000000): array
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
     * Get dictionary words indexed by length for faster filtering
     * 
     * @return array
     */
    private function getDictionaryWordsIndexed(): array
    {
        return $this->cache->get(self::CACHE_KEY . '_indexed', function (CacheItem $item) {
            $item->expiresAfter(self::CACHE_TTL);
            
            $dictionary = $this->getDictionaryWords();
            $wordsByLength = [];
            
            foreach ($dictionary as $word) {
                $length = strlen($word);
                if (!isset($wordsByLength[$length])) {
                    $wordsByLength[$length] = [];
                }
                $wordsByLength[$length][] = $word;
            }
            
            // Sort lengths in descending order
            krsort($wordsByLength);
            
            return $wordsByLength;
        });
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
        $wordsByLength = $this->getDictionaryWordsIndexed();
        
        $candidates = [];
        $letterKeys = array_keys($letterFrequency);
        
        // Create a set of available letters for faster lookup
        $availableLettersSet = array_flip($letterKeys);
        
        // Iterate through words by length (longest first)
        foreach ($wordsByLength as $length => $words) {
            foreach ($words as $word) {
                // Quick pre-filter: word must contain at least one of our letters
                if (!$this->hasCommonLettersFast($word, $availableLettersSet)) {
                    continue;
                }
                
                // Check if word can be formed from available letters
                if ($this->canFormWord($word, $letterFrequency)) {
                    $candidates[] = $word;
                    
                    if (count($candidates) >= $limit) {
                        break 2; // Break out of both loops
                    }
                }
            }
        }
        
        return $candidates;
    }

    /**
     * Fast check if a word has any common letters with available letters
     * 
     * @param string $word
     * @param array $availableLettersSet
     * @return bool
     */
    private function hasCommonLettersFast(string $word, array $availableLettersSet): bool
    {
        $wordLength = strlen($word);
        for ($i = 0; $i < $wordLength; $i++) {
            if (isset($availableLettersSet[$word[$i]])) {
                return true;
            }
        }
        return false;
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
            $lowercaseWords = array_map('strtolower', $filteredWords);
            
            // Sort by word length (highest to lowest) once and cache it
            uasort($lowercaseWords, function($a, $b) {
                return strlen($b) - strlen($a);
            });
            
            return $lowercaseWords;
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

} 