<?php

declare(strict_types=1);

namespace ApiSearch\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ApiSearch\Traits\CalculateScoreFromItems;

/**
 * Class GithubApiService
 * @package ApiSearch\Service
 */
class GithubApiService {

    use CalculateScoreFromItems;

    private const SORT_OPTIONS = [
        'comments',
        'reactions',
        'reactions-+1',
        'reactions--1',
        'reactions-smile',
        'reactions-thinking_face',
        'reactions-heart',
        'reactions-tada',
        'interactions',
        'created',
        'updated'
    ];

    private const ORDER_OPTIONS = ['asc', 'desc'];

    /**
     * @var GithubOAuth2Service
     */
    protected $githubOAuth2Service;

    public function __construct(GithubOAuth2Service $githubOAuth2Service)
    {
        $this->githubOAuth2Service = $githubOAuth2Service;
    }

    /**
     * @param string $term
     * @param string $endpoint
     * @param string $code
     * @param array $options
     * @return float
     * @throws GuzzleException
     */
    public function getTermScoreFromApi(string $term, string $endpoint, string $code, array $options = []): float
    {
        $apiData = $this->getApiData($term, $endpoint, $code, $options);

        return $this->getCalculatedScore($apiData['items']);
    }

    /**
     * @param string $term
     * @param string $endpoint
     * @param string $code
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    private function getApiData(string $term, string $endpoint, string $code, array $options = []): array
    {
        $client = new Client();

        $uri = $endpoint . $term;

        $uri .= $this->getOptionsString($options);

        $token = $this->githubOAuth2Service->getPersonalToken($code);

        $headers = [
            'Accept'               => 'application/vnd.github+json',
            'Authorization'        => 'Bearer ' . $token,
            'X-GitHub-Api-Version' => '2022-11-28'
        ];

        $result = $client->request(
            'GET',
            $uri,
            [
                'headers' => $headers
            ]
        );

        $apiData = [];

        if ($result) {
            $apiData = json_decode($result->getBody()->getContents(), true);
        }

        return $apiData;
    }

    /**
     * @param array $options
     * @return string
     */
    private function getOptionsString(array $options = []): string
    {
        if (!$options) {
            return '';
        }

        $optionsString = '';

        if (
            $options['sort'] &&
            in_array($options['sort'], self::SORT_OPTIONS, true)
        ) {
            $optionsString .= '&sort=' . $options['sort'];
        }

        if (
            $options['order'] &&
            in_array($options['order'], self::ORDER_OPTIONS, true)
        ) {
            $optionsString .= '&order=' . $options['order'];
        }

        if (
            $options['per_page'] &&
            ($options['per_page'] > 0 && $options['per_page'] <= 100)
        ) {
            $optionsString .= '&per_page=' . $options['per_page'];
        }

        if ($options['page']) {
            $optionsString .= '&page=' . $options['page'];
        }

        return $optionsString;
    }
}
