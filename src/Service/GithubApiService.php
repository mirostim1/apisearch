<?php

declare(strict_types=1);

namespace ApiSearch\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class GithubApiService
 * @package ApiSearch\Service
 */
class GithubApiService {

    // private const GITHUB_TOKEN = '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';

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
     * @param string $term
     * @param string $endpoint
     * @param array $options
     * @return float
     * @throws GuzzleException
     */
    public function getTermScoreFromApi(string $term, string $endpoint, array $options = []): float
    {
        $apiData = $this->getApiData($term, $endpoint, $options);

        return $this->getCalculatedScore($apiData['items']);
    }

    /**
     * @param string $term
     * @param string $endpoint
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    private function getApiData(string $term, string $endpoint, array $options = []): array
    {
        $client = new Client();

        $uri = $endpoint . $term;

        $uri .= $this->getOptionsString($options);

        $headers = [
            'Accept'               => 'application/vnd.github+json',
            // 'Authorization'     => 'Bearer ' . self::GITHUB_TOKEN,
            'X-GitHub-Api-Version' => '2022-11-28'
        ];

        $result = $client->request(
            'GET',
            $uri,
            [
                'headers' => $headers
            ]
        );

        if ($result) {
            $apiData = json_decode($result->getBody()->getContents(), true);
        }

        return $apiData ?? [];
    }

    /**
     * @param array $items
     * @return float
     */
    private function getCalculatedScore(array $items): float
    {
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
