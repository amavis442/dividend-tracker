<?php

namespace App\Tests\Unit\Service;

use App\DataProvider\PositionDataProvider;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\Position\PositionAmountService;
use App\Service\Transaction\TransactionAdjuster;
use App\Tests\Unit\Service\Adjustment\AdjustmentStrategyTestTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * @test
 */
class PositionAmountServiceTest extends TestCase{

    use AdjustmentStrategyTestTrait;

    /**
     * Test the current number of shares
     */
    public function testNormalAmount()
    {
        $position = new Position();
        // Set id
        $reflection = new \ReflectionClass($position);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($position, 1);


        $tx1 = $this->createTransaction(100.0, new \DateTime('2024-06-10'), Transaction::BUY); // (100 * 0.2) * 2 = 40
        $tx2 = $this->createTransaction(50.0, new \DateTime('2025-06-10'), Transaction::BUY); // 50 * 2 = 100
        $tx3 = $this->createTransaction(50.0, new \DateTime('2025-08-10'), Transaction::BUY); // 50 = 50 -> total should be 190
        $transactions = [];
        $transactions[$position->getId()] = [$tx1, $tx2, $tx3];

        $ticker = new Ticker();
        $reflection = new \ReflectionClass($position);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($position, 1);
        $ticker->setSymbol('AAPL');
        $ticker->setFullName('Apple computers');

        $position->setTicker($ticker);

        $adjustedDecoratorFactory = new AdjustedPositionDecoratorFactory(new transactionAdjuster());

        /**
         * Unit under test
         */
        $positionAmountService = new PositionAmountService($adjustedDecoratorFactory);
        $positionAmountService->load($transactions, []);

        $positionAmountService->setPosition($position);

        $result = $positionAmountService->getAmount();
        $this->assertEquals(200, $result);
    }

    /**
     * Tests the amount after and before a corporate action like a split or reverse split happens.
     */
    public function testAdjustedAmount()
    {

        $position = new Position();
        // Set id
        $reflection = new \ReflectionClass($position);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($position, 1);

        $ticker = new Ticker();
        // Set id
        $reflection = new \ReflectionClass($ticker);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($ticker, 1);
        $ticker->setSymbol('AAPL');
        $ticker->setFullName('Apple computers');

        $position->setTicker($ticker);

        $ca1 = $this->createCorporateAction(CorporateAction::REVERSE_SPLIT, 0.2, new \DateTime('2025-01-20'));
        $ca2 = $this->createCorporateAction(CorporateAction::SPLIT, 2, new \DateTime('2025-07-25'));
        $corporateActions = [];
        $corporateActions[$ticker->getId()] = [$ca1, $ca2];

        $ticker->addCorporateAction($ca1);
        $ticker->addCorporateAction($ca2);

        $tx1 = $this->createTransaction(100.0, new \DateTime('2024-06-10'), Transaction::BUY); // (100 * 0.2) * 2 = 40
        $tx2 = $this->createTransaction(50.0, new \DateTime('2025-06-10'), Transaction::BUY); // 50 * 2 = 100
        $tx3 = $this->createTransaction(50.0, new \DateTime('2025-08-10'), Transaction::BUY); // 50 = 50 -> total should be 40 + 100 + 50 = 190
        $transactions = [];
        $transactions[$position->GetId()] = [$tx1, $tx2, $tx3];

        $adjustedDecoratorFactory = new AdjustedPositionDecoratorFactory(new transactionAdjuster());

        /**
         * Unit under test
         */
        $positionAmountService = new PositionAmountService($adjustedDecoratorFactory);
        $positionAmountService->setPosition($position);
        $positionAmountService->load($transactions, $corporateActions);


        $result = $positionAmountService->getAmount();
        $this->assertEquals(((100* 0.2) * 2) + (50*2) + 50, $result);
    }

}
