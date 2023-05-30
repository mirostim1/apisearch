<?php

namespace ApiSearch\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use Art4\JsonApiClient\Exception\InputException;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;

trait FormatJsonTrait {

    /**
    * @param array $data
    * @param int $code
    * @return JsonResponse
    */
    private function formatJsonResponse(array $data, int $code): JsonResponse
    {
        $validation = $this->validateAndParseJsonApi(json_encode($data));

        if ($validation['code'] !== 200) {
            $response = new JsonResponse([
                'errors' => [
                    'status' => 'error',
                    'code' => (string)$validation['code'],
                    'title' => $validation['message'],
                ]
            ],
                $validation['code'],
                [
                    'Content-Type' => 'application/vnd.api+json',
                    'Access-Control-Allow-Origin' => '*',
                ]
            );

            return $response->setEncodingOptions(
                $response->getEncodingOptions() | JSON_PRETTY_PRINT
            );
        }

        $response = new JsonResponse(
            $data,
            $code,
            [
                'Content-Type' => 'application/vnd.api+json',
                'Access-Control-Allow-Origin' => '*',
            ]
        );

        return $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );
    }

    /**
     * @param string $jsonApiString
     * @return array
     */
    private function validateAndParseJsonApi(string $jsonApiString): array
    {
        try {
            if (Parser::isValidResponseString($jsonApiString)) {
                Parser::parseResponseString(($jsonApiString));
            }
        } catch (InputException $e) {
            return [
                'status'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (ValidationException $e) {
            return [
                'status'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'code'   => 200,
            'message' => 'String is valid JSON API',
        ];
    }
}
