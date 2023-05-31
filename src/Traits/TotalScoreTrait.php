<?php

namespace ApiSearch\Traits;

trait TotalScoreTrait {

    /**
     * @param array $apiScoreData
     * @return float
     */
    private function calculateOverallScore(array $apiScoreData): float
    {
        $totalScore = 0;
        foreach ($apiScoreData as $apiScore) {
            $totalScore += $apiScore;
        }

        return (float) number_format($totalScore / count($apiScoreData), 2);
    }
}
