<?php

namespace ApiSearch\Traits;

trait CalculateScoreFromItems {
    /**
     * @param array $items
     * @return float
     */
    private function getCalculatedScore(array $items): float
    {
        if ($items) {
            $reactionsPositiveSum = 0;
            $reactionsNegativeSum = 0;
            $totalCountSum = 0;

            foreach ($items as $item) {
                $reactionsPositiveSum += $item['reactions']['+1'];
                $reactionsNegativeSum -= $item['reactions']['-1'];
                $totalCountSum += $item['reactions']['total_count'];
            }

            if ($totalCountSum === 0) {
                $totalCountSum = 1;
            }

            if ($reactionsPositiveSum >= $reactionsNegativeSum) {
                $reactions = $reactionsPositiveSum;
            } else {
                $reactions = $reactionsNegativeSum;
            }

            return (float) number_format(
                ($reactions / $totalCountSum) * 10,
                2
            );
        }

        return 0.00;
    }
}
