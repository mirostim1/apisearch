<?php

namespace ApiSearch\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GithubOAuth2Service
{
    /**
     * @var string|string
     */
    private string $githubOauth2TokenEndpoint;

    /**
     * @var string|string
     */
    private string $githubOauth2AppClientId;

    /**
     * @var string|string
     */
    private string $githubOauth2ClientSecret;

    public function __construct(
        string $githubOauth2TokenEndpoint,
        string $githubOauth2AppClientId,
        string $githubOauth2ClientSecret
    ) {
        $this->githubOauth2TokenEndpoint = $githubOauth2TokenEndpoint;
        $this->githubOauth2AppClientId = $githubOauth2AppClientId;
        $this->githubOauth2ClientSecret = $githubOauth2ClientSecret;
    }

    /**
     * @param string $code
     * @return string
     * @throws GuzzleException
     */
    public function getPersonalToken(string $code): string
    {
        return $this->getToken($code);
    }

    /**
     * @param string $code
     * @return string
     * @throws GuzzleException
     */
    private function getToken(string $code): string
    {
        $headers = [
            'Accept'               => 'application/json',
            'X-GitHub-Api-Version' => '2022-11-28'
        ];

        $client = new Client();

        $result = $client->request(
            'POST',
            $this->githubOauth2TokenEndpoint,
            [
                'form_params' => [
                    'client_id'     => $this->githubOauth2AppClientId,
                    'client_secret' => $this->githubOauth2ClientSecret,
                    'code'          => $code
                ],
                'headers' => $headers
            ]
        );

        $tokenData = json_decode($result->getBody()->getContents(), true);

        return $tokenData['access_token'] ?? '';
    }
}
