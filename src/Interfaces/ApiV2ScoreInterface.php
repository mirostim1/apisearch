<?php

namespace ApiSearch\Interfaces;

interface ApiV2ScoreInterface {

    public function getScoreFromProviders(string $queryString): array;

}
