<?php

declare(strict_types=1);

namespace ApiSearch\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use ApiSearch\Entity\Score;
use ApiSearch\Service\ApiV1ScoreService;
use DateTimeImmutable;
use Exception;
use Art4\JsonApiClient\Exception\InputException;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use ApiSearch\Traits\ScoreTrait;
use ApiSearch\Service\ApiV2ScoreService;
use ApiSearch\Traits\FormatJsonTrait;

/**
 * ScoreV2Controller class.
 */
class ScoreV2Controller extends AbstractController
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

    #[Route('/api/v2/score', name: 'api_score_v2', methods: ['GET'])]
    public function scoreV2(Request $request, ApiV2ScoreService $apiV2ScoreService)
    {
        $queryString = $request->server->all()['QUERY_STRING'];

        $scoreFromDB = $this->em->getRepository(Score::class)->findOneBy(['term' => $queryString]);

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
                    'apiVersion' => 'V2',
                ],
            ];

            return $this->formatJsonResponse($data, 200);
        }

        try {
            $apiScoreData = $apiV2ScoreService->getScoreFromProviders($queryString);
        } catch (Exception $e) {
            $data = [
                'errors' => [
                    'status'  => (string) $e->getCode(),
                    'title'    => 'API error',
                    'details'  => $e->getMessage(),
                ],
                'meta' => [
                    'apiVersion' => 'V2',
                ],
            ];

            return $this->formatJsonResponse($data, $e->getCode());
        }

        $finalScore = $this->calculateOverallScore($apiScoreData);

        $dateTimeObj = new DateTimeImmutable();
        $score = new Score();
        $score->setScore($finalScore);
        $score->setTerm($queryString);
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

            return $this->formatJsonResponse($data, $e->getCode());
        }

        $data = [
            'type' => 'score',
            'id'   => (string) $score->getId(),
            'attributes' => [
                'term'      => $score->getTerm(),
                'score'     => $score->getScore(),
                'createdAt' => $score->getCreatedAt(),
            ],
            'meta' => [
                'apiVersion' => 'V2',
            ],
        ];

        return $this->formatJsonResponse($data, 200);
    }
}
