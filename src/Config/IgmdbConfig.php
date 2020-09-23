<?php

namespace App\Config;

class IgmdbConfig
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(string $apiKey, string $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
