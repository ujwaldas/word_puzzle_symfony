<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class DictionaryService
{
    private const DICTIONARY_API_URL = 'https://api.dictionaryapi.dev/api/v2/entries/en/';
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {
    }

    public function isValidEnglishWord(string $word): bool
    {
        $word = strtolower(trim($word));
        
        if (empty($word) || !ctype_alpha($word)) {
            return false;
        }

        $cacheKey = 'dictionary_' . md5($word);
        
        return $this->cache->get($cacheKey, function (CacheItem $item) use ($word) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->httpClient->request('GET', self::DICTIONARY_API_URL . $word);
                
                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();
                    return !empty($data);
                }
                
                return false;
            } catch (\Exception $e) {
                // Log error in production
                return false;
            }
        });
    }

    public function getWordDefinition(string $word): ?array
    {
        $word = strtolower(trim($word));
        
        if (empty($word) || !ctype_alpha($word)) {
            return null;
        }

        $cacheKey = 'definition_' . md5($word);
        
        return $this->cache->get($cacheKey, function (CacheItem $item) use ($word) {
            $item->expiresAfter(self::CACHE_TTL);
            
            try {
                $response = $this->httpClient->request('GET', self::DICTIONARY_API_URL . $word);
                
                if ($response->getStatusCode() === 200) {
                    return $response->toArray();
                }
                
                return null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }
} 