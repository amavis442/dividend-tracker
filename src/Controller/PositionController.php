<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Entity\Ticker;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use App\Service\Summary;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/dashboard/position")
 */
class PositionController extends AbstractController
{
    public const SEARCH_KEY = 'position_searchCriteria';

    /**
     * @Route("/list/{page}/{tab}/{orderBy}/{sort}", name="position_index", methods={"GET"})
     */
    public function index(
        Summary $summary,
        PositionRepository $positionRepository,
        SessionInterface $session,
        int $page = 1,
        string $tab = 'All',
        string $orderBy = 'buyDate',
        string $sort = 'asc'
    ): Response {
        if (!in_array($orderBy, ['buyDate', 'profit', 'ticker'])) {
            $orderBy = 'buyDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;
        $brokers = array_merge(['All'], Position::BROKERS);

        return $this->render('position/index.html.twig', [
            'positions' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'position_index',
            'searchPath' => 'position_search',
            'brokers' => $brokers,
            'tab' => $tab,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
        ]);
    }

    private function presetMetrics(Position $position)
    {
        if ($position->getAllocation() && empty($position->getPrice())) {
            $position->setPrice($position->getAllocation() / ($position->getAmount() / 100));
            $position->setCurrency($position->getAllocationCurrency());
        }
        if ($position->getPrice() && empty($position->getAllocation())) {
            $position->setAllocation($position->getPrice() * ($position->getAmount() / 100));
            $position->setAllocationCurrency($position->getCurrency());
        }
    }

    /**
     * @Route("/new/{ticker}", name="position_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?Ticker $ticker = null, SessionInterface $session): Response
    {
        $position = new Position();

        if ($ticker instanceof Ticker) {
            $position->setTicker($ticker);
        }
        $currentDate = new DateTime();
        $position->setBuyDate($currentDate);

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($position);

            $transaction = new Transaction();
            $transaction->setSide(Transaction::BUY)
                ->setAmount($position->getAmount())
                ->setPrice($position->getPrice())
                ->setCurrency($position->getCurrency())
                ->setAllocationCurrency($position->getAllocation())
                ->setAllocationCurrency($position->getAllocationCurrency())
                ->setTransactionDate($position->getBuyDate())
                ->setBroker($position->getBroker());

            $position->addTransaction($transaction);
            
            $position->setClosed(0);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($position);
            $entityManager->flush();
            $session->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
            $session->set(PortfolioController::SEARCH_KEY, $position->getTicker()->getTicker());
            return $this->redirectToRoute('portfolio_index');
        }

        return $this->render('position/new.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="position_show", methods={"GET"})
     */
    public function show(Position $position): Response
    {
        return $this->render('position/show.html.twig', [
            'position' => $position,
        ]);
    }

    /**
     * @Route("/{id}/edit/{closed<\d+>?0}", name="position_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Position $position, ?int $closed, SessionInterface $session): Response
    {
        if ($closed === 1) {
            $position->setClosed(true);
            $position->setCloseDate(new DateTime());
        }

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($position);
            $this->getDoctrine()->getManager()->flush();
            $session->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
            return $this->redirectToRoute('position_index');
        }

        return $this->render('position/edit.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="position_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_index');
    }

    /**
     * @Route("/search", name="position_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('position_index',['orderBy' => 'buyDate','sort'=>'desc']);
    }
}
