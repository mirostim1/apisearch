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
use ApiSearch\Service\ApiV2ScoreService;
use ApiSearch\Traits\FormatJsonTrait;
use GuzzleHttp\Exception\GuzzleException;
use OpenApi\Annotations as OA;

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
    /**
     * @OA\Get(
     *     description="ApiSearch endpoint for fetching score for given search term.<br/><br/>
           Example of request uri with all parameters:
           https://example.com/api/v2/score?<strong>php&sort=reactions-+1&order=desc&per_page=50&page=1</strong>"
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
     *     name="searchQuery",
     *     in="query",
     *     description="URI query string for search. Required parameter in this string is search term for e.g.
           http://example.com/api/v2/score?<strong>php</strong>. Query string can also include 4 more parameters inside the
           the query string: sort, order, per_page, and page. Sort will sort search by some field.
           Order will order search either desc (default) or asc. Per_page parameter will fetch some
           number of results (default is 30 results per page and maximum is 100). Page parameter will return
           wanted page of results (default is 1). <br/><br/>Example of API request with all parameters will be e.g.
           http://example.com/api/v2/score?<strong>php&sort=reactions&order=asc&per_page=25&page=2</strong>",
     *     @OA\Schema(type="string")
     * )
     *
     *
     * @param Request $request
     * @param ApiV2ScoreService $apiV2ScoreService
     * @return JsonResponse
     * @throws GuzzleException
     */
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
                    'apiVersion' => 'v2',
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
                    'title'   => 'API error',
                    'details' => $e->getMessage(),
                ],
                'meta' => [
                    'apiVersion' => 'v2',
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
                'apiVersion' => 'v2',
            ],
        ];

        return $this->formatJsonResponse($data, 200);
    }
}
