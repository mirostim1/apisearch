<?php

declare(strict_types=1);

namespace ApiSearch\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Art4\JsonApiClient\Exception\InputException;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use ApiSearch\Entity\Score;
use ApiSearch\Service\ApiScoreService;
use DateTimeImmutable;
use Exception;

/**
 * SearchController class.
 */
class ScoreV1Controller extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * ScoreController constructor.
     *
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    #[Route('/api/v1/score', name: 'api_score_v1', methods: ['GET'])]
    public function scoreV1(Request $request, ApiScoreService $apiScoreService): JsonResponse
    {
        $term = $request->get('term');

        $options = [
            'sort'     => $request->get('sort'),
            'order'    => $request->get('order', 'desc'),
            'per_page' => $request->get('per_page', 30),
            'page'     => $request->get('page', 1),
        ];

        if (!$term) {
            $data = [
                'errors' => [
                    [
                        'status' => 'error',
                        'code'   => '400',
                        'title'  => 'Search term must be provided, it is a required parameter.',
                    ]
                ],
            ];

            return $this->formatJsonResponse($data, 400);
        }

        $scoreFromDB = $this->em->getRepository(Score::class)->findOneBy(['term' => $term]);

        if ($scoreFromDB) {
            $data = [
                'data' => [
                    'type' => 'score',
                    'id'   => (string) $scoreFromDB->getId(),
                    'attributes' => [
                        'term'  => $scoreFromDB->getTerm(),
                        'score' => $scoreFromDB->getScore(),
                        'createdAt' => $scoreFromDB->getCreatedAt(),
                    ],
                ],
            ];

            return $this->formatJsonResponse($data, 200);
        }

        try {
            $apiScoreData = $apiScoreService->getScoreFromProviders($term, $options);
        } catch (Exception $e) {
            $data = [
                'errors' => [
                    [
                        'status' => 'error',
                        'code'   => (string) $e->getCode(),
                        'title'  => $e->getMessage(),
                    ]
                ],
            ];

            return $this->formatJsonResponse($data, 400);
        }

        $totalScore = 0;
        foreach ($apiScoreData as $apiScore) {
            $totalScore += $apiScore;
        }

        $finalScore = (float) number_format($totalScore / count($apiScoreData), 2);

        $dateTimeObj = new DateTimeImmutable();

        $score = new Score();
        $score->setScore($finalScore);
        $score->setTerm($term);
        $score->setCreatedAt($dateTimeObj);

        try {
            $this->em->persist($score);
            $this->em->flush();
        } catch (Exception $e) {
            $data = [
                'errors' => [
                    'status' => 'error',
                    'code'   => $e->getCode(),
                    'title'  => $e->getMessage(),
                ]
            ];

            return $this->formatJsonResponse($data, 400);
        }

        $data = [
            'data' => [
                'type' => 'score',
                'id'   => (string) $score->getId(),
                'attributes' => [
                    'term'      => $score->getTerm(),
                    'score'     => $score->getScore(),
                    'createdAt' => $score->getCreatedAt(),
                ],
            ],
        ];

        return $this->formatJsonResponse($data, 200);
    }

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
                        'code'   => (string) $validation['code'],
                        'title'  => $validation['message'],
                    ]
                ],
                $validation['code'],
                ['Content-Type' => 'application/vnd.api+json']
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
