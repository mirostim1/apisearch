<?php

declare(strict_types=1);

namespace ApiSearch\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

/**
 * Class ApiV2ScoreService
 * @package ApiSearch\Service
 */
class ApiV2ScoreService
{
    private const GITHUB = 'github';

    // private const TWITTER = 'twitter';

    /**
     * @var array
     */
    private array $enabledApiProviders;

    public function __construct(array $enabledApiProviders)
    {
        $this->enabledApiProviders = $enabledApiProviders;
    }

    /**
     * @param string $queryString
     * @return array
     * @throws GuzzleException
     */
    public function getScoreFromProviders(string $queryString): array
    {
        $dataFromProviders = [];

        foreach ($this->enabledApiProviders as $apiProviderData) {
            switch (key($apiProviderData)) {
                case self::GITHUB:
                    $apiUri = $apiProviderData[self::GITHUB];
                    $contentType = 'application/vnd.github+json';
                    $dataFromProviders[self::GITHUB] = $this->makeApiRequest($apiUri, $queryString, $contentType);
                    break;
                /* case self::TWITTER:
                    $apiUri = $apiProviderData[self::TWITTER];
                    $contentType = 'application/vnd.api+json';
                    $dataFromProviders[self::TWITTER] = $this->makeApiRequest($apiUri, $queryString, $contentType);
                    break;
                */
                default:
                    break;
            }
        }

        return $dataFromProviders;
    }

    /**
     * @param string $apiUri
     * @param string $queryString
     * @param string $contentType
     * @return float
     * @throws GuzzleException
     */
    private function makeApiRequest(string $apiUri, string $queryString, string $contentType): float
    {
        $client = new Client();
        $uri = $apiUri . $queryString;

        $headers = [
            'Accept'               => $contentType,
            "Content-Type"         => $contentType,
            'X-GitHub-Api-Version' => '2022-11-28'
        ];

        try {
            $result = $client->request(
                'GET',
                $uri,
                [
                    'headers' => $headers
                ]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $apiData = [];

        if ($result) {
            $apiData = json_decode($result->getBody()->getContents(), true);
        }

        return $this->calculateScorePerProvider($apiData['items']);
    }

    /**
     * @param array $items
     * @return float
     */
    private function calculateScorePerProvider(array $items = []): float
    {
        if (!$items) {
            $reactionsSum = 0;
            $totalCountSum = 0;

            foreach ($items as $item) {
                $reactionsSum += $item['reactions']['+1'];
                $reactionsSum -= $item['reactions']['-1'];
                $totalCountSum += $item['reactions']['total_count'];
            }

            if ($totalCountSum === 0) {
                $totalCountSum = 1;
            }

            return (float) number_format(
                $reactionsSum / $totalCountSum,
                2
            );
        }

        return 0.00;
    }
}
