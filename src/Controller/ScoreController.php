<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Score;
use App\Service\ApiScoreService;

/**
 * SearchController class.
 */
class ScoreController extends AbstractController
{
    #[Route('/score', name: 'score', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em/*, ApiScoreService $apiScoreService*/)
    {
        $term = $request->get('term');

        if (!$term) {
            return $this->render('error/score-term-missing-error.html.twig');
        }

        $scoreFromDB = $em->getRepository(Score::class)->findOneBy(['term' => $term]);

        if ($scoreFromDB) {
            $data = [
                'term'       => $scoreFromDB->getTerm(),
                'score'      => $scoreFromDB->getScore(),
                'created_at' => $scoreFromDB->getCreatedAt(),
            ];

            echo json_encode($data);
            die;
        }

        // $apiStatsData = $apiScoreService->getApiStatsData();

        $dateTimeObj = new \DateTimeImmutable();

        $score = new Score();
        // $score->setScore($apiStatsData['score']);
        $score->setScore(3.33);
        $score->setTerm($term);
        $score->setCreatedAt($dateTimeObj);

        try {
            $em->persist($score);
            $em->flush();
        } catch (\RuntimeException $e) {
            // log error to error log in db
            return $this->render('error/score-term-db-error.html.twig');
        }

        echo json_encode([
            'term' => $term,
            'score' => 3.33,
            'created_at' => $dateTimeObj,
        ]);
        die;
    }
}
