<?php

require __DIR__ . '/vendor/autoload.php';

use Arshia\LarisAIGen\Commands\AIGenerateControllerCommand;
use Symfony\Component\Console\Application;

$app = new Application();

$app->add(new AIGenerateControllerCommand());

$app->run();
