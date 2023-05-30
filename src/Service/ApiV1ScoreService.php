<?php

declare(strict_types=1);

namespace ApiSearch\Service;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Class ApiV1ScoreService
 * @package ApiSearch\Service
 */
class ApiV1ScoreService
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
     * @param string $term
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    public function getScoreFromProviders(string $term, array $options = []): array
    {
        $dataFromProviders = [];

        foreach ($this->enabledApiProviders as $apiProviderData) {
            switch (key($apiProviderData)) {
                case self::GITHUB:
                    $githubService = new GithubApiService();
                    $dataFromProviders[key($apiProviderData)] = $githubService->getTermScoreFromApi(
                        $term,
                        $apiProviderData[key($apiProviderData)], // API endpoint for GitHub from configuration
                        $options
                    );
                    break;
                /* case self::TWITTER:
                    $twitterService = new TwitterApiService();
                    $twitterData = $twitterService->getTermScoreFromApi($term, $endpoint, $options);
                    break;
                */
                default:
                    break;
            }
        }

        return $dataFromProviders;
    }
}
