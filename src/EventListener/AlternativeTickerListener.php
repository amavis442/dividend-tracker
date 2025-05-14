<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use App\Entity\TickerAlternativeSymbol;
use App\Repository\Trading212PieInstrumentRepository;

#[
    AsEntityListener(
        event: Events::postPersist,
        method: 'postUpdate',
        entity: TickerAlternativeSymbol::class
    )
]
final class AlternativeTickerListener
{

    public function __construct(
        private Trading212PieInstrumentRepository $trading212PieInstrumentRepository,
    )
    { }


    public function postUpdate(
        TickerAlternativeSymbol $tickerAlternativeSymbol
    ): void {
        $symbol = $tickerAlternativeSymbol->getSymbol();
        $ticker = $tickerAlternativeSymbol->getTicker();
        $this->trading212PieInstrumentRepository->updateTicker($ticker, $symbol);
    }
}
