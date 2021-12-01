<?php

declare(strict_types=1);

use Freyr\DP\Http\Controller\ImageController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

/** @var App $app */
$app->group('/image', function (Group $group): void {
    $group->get('/show/{id}', [ImageController::class, 'showImage'])->setName('image.show');
    $group->get('/catalog/{name}', [ImageController::class, 'addCatalog'])->setName('image.catalog.add');
});
