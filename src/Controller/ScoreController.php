<?php

namespace ApiSearch\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ApiSearch\Entity\Score;
use ApiSearch\Service\ApiScoreService;
use Symfony\Component\Validator\ConstraintViolationList;

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

    #[Route('/score', name: 'score', methods: ['GET'])]
    public function search(Request $request, ApiScoreService $apiScoreService): Response
    {
        $term = $request->get('term');
        $options = [
            'sort'     => $request->get('sort'),
            'order'    => $request->get('order', 'desc'),
            'per_page' => $request->get('per_page', 30),
            'page'     => $request->get('page', 1),
        ];

        if (!$term) {
            return $this->render('error/score-term-missing-error.html.twig');
        }

        $scoreFromDB = $this->em->getRepository(Score::class)->findOneBy(['term' => $term]);

        if ($scoreFromDB) {
            $data = [
                'term'       => $scoreFromDB->getTerm(),
                'score'      => $scoreFromDB->getScore(),
                'created_at' => $scoreFromDB->getCreatedAt(),
            ];

            return $this->render('score/index.html.twig', [
                'data' => $data,
            ]);
        }

        $apiScoreData = $apiScoreService->getScoreFromProviders($term, $options);

        $dateTimeObj = new \DateTimeImmutable();

        $score = new Score();
        $score->setScore($apiScoreData);
        $score->setTerm($term);
        $score->setCreatedAt($dateTimeObj);

        $errors = $this->validateSearchTerm($score);

        if (count($errors) > 0) {
            return $this->render('error/score-term-validation-error.html.twig', [
                'errors' => $errors,
            ]);
        }

        try {
            $this->em->persist($score);
            $this->em->flush();
        } catch (\RuntimeException $e) {
            // log error to error log in db
            return $this->render('error/score-term-db-error.html.twig');
        }

        $data = [
            'term'       => $term,
            'score'      => $apiScoreData,
            'created_at' => $dateTimeObj,
        ];

        return $this->render('score/index.html.twig', [
            'data' => $data,
        ]);
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
