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
use ApiSearch\Service\ApiV1ScoreService;
use DateTimeImmutable;
use Exception;
use ApiSearch\Traits\ScoreTrait;
use ApiSearch\Traits\FormatJsonTrait;

/**
 * SearchController class.
 */
class ScoreV1Controller extends AbstractController
{
    use ScoreTrait, FormatJsonTrait;

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
    public function scoreV1(Request $request, ApiV1ScoreService $apiScoreService): JsonResponse
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
                'meta' => [
                    'apiVersion' => 'V1',
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
                'meta' => [
                    'apiVersion' => 'V1',
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
                    ],
                ],
                'meta' => [
                    'apiVersion' => 'V1',
                ],
            ];

            return $this->formatJsonResponse($data, 400);
        }

        $finalScore = $this->calculateOverallScore($apiScoreData);

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
                ],
                'meta' => [
                    'apiVersion' => 'V1',
                ],
            ];

            return $this->formatJsonResponse($data, $e->getCode());
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
            'meta' => [
                'apiVersion' => 'V1',
            ],
        ];

        return $this->formatJsonResponse($data, 200);
    }
}
