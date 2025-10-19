<?php

namespace App\Pager;

use Pagerfanta\Adapter\AdapterInterface;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;

/**
 * @example
 * Not used yet. Leave it here as an example
 */
class AdjustedPositionAdapter implements AdapterInterface
{
    private AdapterInterface $innerAdapter;
    private AdjustedPositionDecoratorFactory $decoratorFactory;

    public function __construct(
        AdapterInterface $innerAdapter,
        AdjustedPositionDecoratorFactory $decoratorFactory
    ) {
        $this->innerAdapter = $innerAdapter;
        $this->decoratorFactory = $decoratorFactory;
    }

    /**
     * Return number of results
     *
     * @return int
     */
    public function getNbResults(): int
    {
        return $this->innerAdapter->getNbResults();
    }

    /**
     * Get the slice of data that we want to decorate so the adjusted amount and average price
     * is up to date.
     *
     * Will not work anymore after refactoring of factory.
     *
     * @return iterable
     */
    public function getSlice($offset, $length): iterable
    {
        $results = $this->innerAdapter->getSlice($offset, $length);

        foreach ($results as $key => $position) {
            $results[$key] = $this->decoratorFactory->decorate($position);
        }

        return $results;
    }
}
