<?php

declare(strict_types=1);

namespace ApiSearch\Service;

use GuzzleHttp\Exception\GuzzleException;
use ApiSearch\Interfaces\ApiV1ScoreInterface;

/**
 * Class ApiV1ScoreService
 * @package ApiSearch\Service
 */
class ApiV1ScoreService implements ApiV1ScoreInterface
{
    private const GITHUB = 'github';

    // private const TWITTER = 'twitter';

    /**
     * @var array
     */
    private array $enabledApiProviders;

    /**
     * @var GithubApiService
     */
    private $githubApiService;

    public function __construct(GithubApiService $githubApiService, array $enabledApiProviders)
    {
        $this->githubApiService = $githubApiService;
        $this->enabledApiProviders = $enabledApiProviders;
    }

    /**
     * @param string $term
     * @param array $options
     * @param string|null $code
     * @return array
     * @throws GuzzleException
     */
    public function getScoreFromProviders(string $term, string $code, array $options = []): array
    {
        $dataFromProviders = [];

        foreach ($this->enabledApiProviders as $apiProviderData) {
            switch (key($apiProviderData)) {
                case self::GITHUB:
                    $dataFromProviders[key($apiProviderData)] = $this->githubApiService->getTermScoreFromApi(
                        $term,
                        $apiProviderData[key($apiProviderData)], // API endpoint for GitHub from configuration
                        $code,
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
