<?php

namespace ApiSearch\Interfaces;

interface ApiV1ScoreInterface {

    public function getScoreFromProviders(string $term, array $options = []): array;

}
