<?php

declare(strict_types=1);

namespace ApiSearch\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\EntityManagerInterface;
use ApiSearch\Entity\Score;
use ApiSearch\Service\ApiScoreService;
use DateTimeImmutable;
use Exception;

/**
 * SearchController class.
 */
class ScoreController extends AbstractController
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
                'status' => 'error',
                'code' => 400,
                'errors' => ['Search term must be provided, it is a required parameter.'],
            ];

            return $this->formatJsonResponse(
                $data,
                400,
                [
                    'headers' => ['Content-Type: application/json;charset=utf-8']
                ]
            );
        }

        $scoreFromDB = $this->em->getRepository(Score::class)->findOneBy(['term' => $term]);

        if ($scoreFromDB) {
            $data = [
                'status'     => 'success',
                'code'       => 200,
                'term'       => $scoreFromDB->getTerm(),
                'score'      => $scoreFromDB->getScore(),
                'createdAt'  => $scoreFromDB->getCreatedAt(),
            ];

            return $this->formatJsonResponse(
                $data,
                200,
                [
                    'headers' => ['Content-Type: application/json;charset=utf-8']
                ]
            );
        }

        try {
            $apiScoreData = $apiScoreService->getScoreFromProviders($term, $options);
        } catch (Exception $e) {
            $data = [
                'status' => 'error',
                'code' => $e->getCode(),
                'errors' => [$e->getMessage()],
            ];

            return $this->formatJsonResponse(
                $data,
                400,
                [
                    'headers' => ['Content-Type: application/json;charset=utf-8']
                ]
            );
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

        $errors = $this->validateSearchTerm($score);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            $data = [
                'status' => 'error',
                'code'   => 400,
                'errors' => $errorMessages,
            ];

            return $this->formatJsonResponse(
                $data,
                400,
                [
                    'headers' => ['Content-Type: application/json;charset=utf-8']
                ]
            );
        }

        try {
            $this->em->persist($score);
            $this->em->flush();
        } catch (Exception $e) {
            $data = [
                'status' => 'error',
                'code'   => $e->getCode(),
                'errors' => [$e->getMessage()],
            ];

            return $this->formatJsonResponse(
                $data,
                400,
                [
                    'headers' => ['Content-Type: application/json;charset=utf-8']
                ]
            );
        }

        $data = [
            'status'     => 'success',
            'code'       => 200,
            'term'       => $term,
            'score'      => $finalScore,
            'createdAt'  => $dateTimeObj,
        ];

        return $this->formatJsonResponse(
            $data,
            200,
            [
                'headers' => 'Content-Type: application/json;charset=utf-8',
            ]
        );
    }

    /**
     * @param array $data
     * @param int $code
     * @param array $headers
     * @return JsonResponse
     */
    private function formatJsonResponse(array $data, int $code, array $headers): JsonResponse
    {
        $response = new JsonResponse(
            $data,
            $code,
            $headers
        );

        return $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
    }

    /**
     * @param Score $score
     * @return ConstraintViolationList
     */
    private function validateSearchTerm(Score $score): ConstraintViolationList
    {
        return $this->validator->validate($score);
    }
}
