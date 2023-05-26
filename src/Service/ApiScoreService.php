<?php

namespace ApiSearch\Service;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Class ApiScoreService
 * @package ApiSearch\Service
 */
class ApiScoreService
{
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
                case 'github':
                    $githubService = new GithubApiService();
                    $dataFromProviders[key($apiProviderData)] = $githubService->getTermScoreFromApi(
                        $term,
                        $apiProviderData[key($apiProviderData)],
                        $options
                    );
                    break;
                /* case 'twitter':
                    $twitterService = new TwitterApiService();
                    $twitterData = $twitterService->getTermScoreFromApi($term, $endpoint, $options);
                    break; */
                default:
                    break;
            }
        }

        return $dataFromProviders;
    }
}
