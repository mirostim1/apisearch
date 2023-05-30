<?php

namespace ApiSearch\Traits;

trait ScoreTrait {

    /**
     * @param array $apiScoreData
     * @return float
     */
    public function calculateOverallScore(array $apiScoreData): float
    {
        $totalScore = 0;
        foreach ($apiScoreData as $apiScore) {
            $totalScore += $apiScore;
        }

        return (float) number_format($totalScore / count($apiScoreData), 2);
    }
}
