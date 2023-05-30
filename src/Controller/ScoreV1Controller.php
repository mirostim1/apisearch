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
use ApiSearch\Traits\ScoreTrait;
use ApiSearch\Traits\FormatJsonTrait;
use GuzzleHttp\Exception\GuzzleException;
use OpenApi\Annotations as OA;

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
    /**
     * @OA\Get(
     *     description="ApiSearch endpoint for fetching score for given search term.<br/><br/>
           Example of request uri: https://example.com/api/v1/score?<strong>term=php&sort=reactions-+1&order=desc&per_page=50&page=1</strong>"
     * )
     * @OA\Response(
     *      response="200",
     *      description="Ok",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/vnd.api+json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      description="Type respresents data entity of object."
     *                  ),
     *                  @OA\Property(
     *                      property="id",
     *                      type="integer",
     *                      description="Id represents unique object id."
     *                  ),
     *                  @OA\Property(
     *                      property="term",
     *                      type="string",
     *                      description="Term represents search term."
     *                  ),
     *                  @OA\Property(
     *                      property="score",
     *                      type="float",
     *                      description="Id represents unique object id."
     *                  ),
     *                  @OA\Property(
     *                      property="createdAt",
     *                      type="datetime",
     *                      description="CreatedAt represents date/time of object creation.",
     *
     *                  )
     *              )
     *          )
     *      }
     * )
     * @OA\Parameter(
     *     name="term",
     *     in="query",
     *     description="Parameter for search term and retrieve term score (required).",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sort by one of the options (optional). Default one is best match.",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="order",
     *     in="query",
     *     description="Order of search (optional). Default one is: desc.",
     *     @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     description="Items per page in search (optional). Maximum is 100 and default is 30.",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page of search results (optional). Default is 1.",
     *     @OA\Schema(type="integer")
     * )
     *
     *
     * @param Request $request
     * @param ApiV1ScoreService $apiScoreService
     * @return JsonResponse
     * @throws GuzzleException
     */
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
                    'apiVersion' => 'v1',
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
                    'apiVersion' => 'v1',
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
                    'apiVersion' => 'v1',
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
                    'apiVersion' => 'v1',
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
                'apiVersion' => 'v1',
            ],
        ];

        return $this->formatJsonResponse($data, 200);
    }
}
