<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fast-validate-dictionary',
    description: 'Fast validation of dictionary words using multiple strategies'
)]
class FastValidateDictionaryCommand extends Command
{
    private const DICTIONARY_FILE = __DIR__ . '/../../data/words.txt';
    private const VALID_WORDS_FILE = __DIR__ . '/../../data/words_valid.txt';
    private const INVALID_WORDS_FILE = __DIR__ . '/../../data/words_invalid.txt';
    
    // Common English words (expanded list)
    private const COMMON_WORDS = [
        // Articles and prepositions
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'up', 'out', 'off', 'over', 'under',
        // Pronouns
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'her', 'its', 'our', 'their',
        // Common verbs
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might',
        // Common adjectives
        'good', 'bad', 'big', 'small', 'new', 'old', 'high', 'low', 'long', 'short', 'hot', 'cold', 'warm', 'cool', 'fast', 'slow', 'hard', 'soft',
        // Common nouns
        'time', 'person', 'year', 'way', 'day', 'thing', 'man', 'world', 'life', 'hand', 'part', 'child', 'eye', 'woman', 'place', 'work', 'week', 'case', 'point', 'government', 'company', 'number', 'group', 'problem', 'fact',
        // Common adverbs
        'very', 'really', 'quite', 'rather', 'too', 'also', 'only', 'just', 'even', 'still', 'again', 'ever', 'never', 'always', 'sometimes', 'often', 'usually',
        // Common conjunctions
        'that', 'this', 'these', 'those', 'what', 'when', 'where', 'why', 'how', 'which', 'who', 'whom', 'whose',
        // Common interjections
        'yes', 'no', 'ok', 'okay', 'oh', 'ah', 'wow', 'hey', 'hi', 'hello', 'goodbye', 'bye',
        // Common contractions
        'don\'t', 'doesn\'t', 'didn\'t', 'won\'t', 'wouldn\'t', 'couldn\'t', 'shouldn\'t', 'can\'t', 'cannot', 'isn\'t', 'aren\'t', 'wasn\'t', 'weren\'t', 'haven\'t', 'hasn\'t', 'hadn\'t',
        // Common words from your original list
        'heat', 'star', 'mind', 'fire', 'wind', 'tree', 'book', 'fish', 'bird', 'door', 'lamp', 'desk', 'card', 'ball', 'cake', 'milk', 'bread', 'water', 'sun', 'moon'
    ];

    // Common word patterns that are likely valid
    private const VALID_PATTERNS = [
        '/^[a-z]+$/', // Only letters
        '/^[a-z]{2,14}$/', // Length 2-14
        '/^[a-z]+[aeiou][a-z]*$/', // Contains at least one vowel
        '/^[a-z]*[aeiou][a-z]*[aeiou][a-z]*$/', // Contains at least two vowels
    ];

    // Invalid patterns
    private const INVALID_PATTERNS = [
        '/[^a-z]/', // Contains non-letters
        '/^[aeiou]{3,}/', // Starts with 3+ vowels
        '/[aeiou]{4,}/', // Contains 4+ consecutive vowels
        '/[bcdfghjklmnpqrstvwxyz]{5,}/', // Contains 5+ consecutive consonants
        '/^[bcdfghjklmnpqrstvwxyz]+$/', // No vowels at all
        '/^[aeiou]+$/', // Only vowels
        '/^[bcdfghjklmnpqrstvwxyz]+$/', // Only consonants
    ];

    protected function configure(): void
    {
        $this
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Use strict validation (more words rejected)')
            ->addOption('lenient', null, InputOption::VALUE_NONE, 'Use lenient validation (more words accepted)')
            ->addOption('min-length', null, InputOption::VALUE_OPTIONAL, 'Minimum word length', 2)
            ->addOption('max-length', null, InputOption::VALUE_OPTIONAL, 'Maximum word length', 14)
            ->addOption('output-stats', null, InputOption::VALUE_NONE, 'Output detailed statistics')
            ->setHelp('Fast validation using multiple strategies without API calls.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $strict = $input->getOption('strict');
        $lenient = $input->getOption('lenient');
        $minLength = (int) $input->getOption('min-length');
        $maxLength = (int) $input->getOption('max-length');
        $outputStats = $input->getOption('output-stats');

        if (!file_exists(self::DICTIONARY_FILE)) {
            $io->error('Dictionary file not found: ' . self::DICTIONARY_FILE);
            return Command::FAILURE;
        }

        $io->info('Reading dictionary file...');
        $words = file(self::DICTIONARY_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $originalCount = count($words);
        
        $io->text("Found {$originalCount} words in dictionary file");

        // Create word lookup sets for fast validation
        $commonWordsSet = array_flip(self::COMMON_WORDS);
        
        $io->info('Starting fast validation...');
        
        $validWords = [];
        $invalidWords = [];
        $stats = [
            'common_words' => 0,
            'pattern_valid' => 0,
            'pattern_invalid' => 0,
            'length_filtered' => 0,
            'character_filtered' => 0
        ];

        $progressBar = $io->createProgressBar($originalCount);
        $progressBar->start();

        foreach ($words as $word) {
            $word = strtolower(trim($word));
            
            if ($this->isValidWordFast($word, $commonWordsSet, $strict, $lenient, $minLength, $maxLength, $stats)) {
                $validWords[] = $word;
            } else {
                $invalidWords[] = $word;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $io->newLine();

        $validCount = count($validWords);
        $invalidCount = count($invalidWords);
        
        $io->text("Valid words: {$validCount}");
        $io->text("Invalid words: {$invalidCount}");
        $io->text("Removal percentage: " . round(($invalidCount / $originalCount) * 100, 2) . '%');

        if ($outputStats) {
            $io->section('Validation Statistics');
            $io->text("Common words found: {$stats['common_words']}");
            $io->text("Pattern-valid words: {$stats['pattern_valid']}");
            $io->text("Pattern-invalid words: {$stats['pattern_invalid']}");
            $io->text("Length-filtered words: {$stats['length_filtered']}");
            $io->text("Character-filtered words: {$stats['character_filtered']}");
        }

        // Write results
        $io->info('Writing results...');
        
        file_put_contents(self::VALID_WORDS_FILE, implode("\n", $validWords));
        $io->text('Valid words written to: ' . self::VALID_WORDS_FILE);
        
        if (!empty($invalidWords)) {
            file_put_contents(self::INVALID_WORDS_FILE, implode("\n", $invalidWords));
            $io->text('Invalid words written to: ' . self::INVALID_WORDS_FILE);
        }
        
        // Replace original file with valid words
        copy(self::VALID_WORDS_FILE, self::DICTIONARY_FILE);
        unlink(self::VALID_WORDS_FILE);
        $io->text('Original file updated with valid words only');
        
        $io->success('Fast dictionary validation completed successfully!');
        
        return Command::SUCCESS;
    }

    private function isValidWordFast(string $word, array $commonWordsSet, bool $strict, bool $lenient, int $minLength, int $maxLength, array &$stats): bool
    {
        // Basic length and character validation
        if (strlen($word) < $minLength || strlen($word) > $maxLength) {
            $stats['length_filtered']++;
            return false;
        }

        if (!ctype_alpha($word)) {
            $stats['character_filtered']++;
            return false;
        }

        // Check against common words first (fastest)
        if (isset($commonWordsSet[$word])) {
            $stats['common_words']++;
            return true;
        }

        // Pattern validation
        foreach (self::INVALID_PATTERNS as $pattern) {
            if (preg_match($pattern, $word)) {
                $stats['pattern_invalid']++;
                return false;
            }
        }

        // For strict mode, require more validation
        if ($strict) {
            // Additional strict checks
            if (!$this->hasReasonableVowelConsonantRatio($word)) {
                $stats['pattern_invalid']++;
                return false;
            }
            
            if ($this->hasUnusualLetterCombinations($word)) {
                $stats['pattern_invalid']++;
                return false;
            }
        }

        // For lenient mode, accept more words
        if ($lenient) {
            // Basic pattern validation only
            foreach (self::VALID_PATTERNS as $pattern) {
                if (preg_match($pattern, $word)) {
                    $stats['pattern_valid']++;
                    return true;
                }
            }
        } else {
            // Standard validation
            $stats['pattern_valid']++;
            return true;
        }

        return false;
    }

    private function hasReasonableVowelConsonantRatio(string $word): bool
    {
        $vowels = preg_match_all('/[aeiou]/', $word);
        $consonants = strlen($word) - $vowels;
        
        // Word should have reasonable vowel-consonant ratio
        return $vowels > 0 && $consonants > 0 && $vowels <= $consonants * 2;
    }

    private function hasUnusualLetterCombinations(string $word): bool
    {
        // Check for unusual letter combinations
        $unusualPatterns = [
            '/[bcdfghjklmnpqrstvwxyz]{6,}/', // 6+ consecutive consonants
            '/[aeiou]{5,}/', // 5+ consecutive vowels
            '/^[bcdfghjklmnpqrstvwxyz]{3,}/', // Starts with 3+ consonants
            '/[bcdfghjklmnpqrstvwxyz]{3,}$/', // Ends with 3+ consonants
        ];

        foreach ($unusualPatterns as $pattern) {
            if (preg_match($pattern, $word)) {
                return true;
            }
        }

        return false;
    }
} 