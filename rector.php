<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        //__DIR__ . '/config',
        // __DIR__ . '/node_modules',
        //__DIR__ . '/public',
        //__DIR__ . '/spec',
        __DIR__ . '/src',
        //__DIR__ . '/tests',
    ]);

    $rectorConfig->import('vendor/fakerphp/faker/rector-migrate.php');

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::FRAMEWORK_EXTRA_61,
    ]);
};
