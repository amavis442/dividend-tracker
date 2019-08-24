<?php

namespace App\Controller;

use App\Entity\Position;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TickerRepository;
use DateTime;

/**
 * @Route("/position")
 */
class PositionController extends AbstractController
{
    /**
     * @Route("/{page<\d+>?1}", name="position_index", methods={"GET"})
     */
    public function index(PositionRepository $positionRepository, int $page = 1): Response
    {
        //$positionRepository->findAll()
        $items = $positionRepository->getAll($page);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('position/index.html.twig', [
            'positions' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'routeName' => 'position_index',
        ]);
    }

    /**
     * @Route("/new/{tickerId<\d+>?0}", name="position_new", methods={"GET","POST"})
     */
    public function new(Request $request, TickerRepository $tickerRepository, ?int $tickerId ): Response
    {
        $position = new Position();

        if ($tickerId) {
            $ticker = $tickerRepository->find($tickerId);
            $position->setTicker($ticker);
        }
        $currentDate = new DateTime();
        $position->setBuyDate($currentDate);

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($position);
            $entityManager->flush();

            return $this->redirectToRoute('position_index');
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
    public function edit(Request $request, Position $position, ?int $closed): Response
    {
        if ($closed){
            $position->setClosed(true);
            $position->setCloseDate(new DateTime());
        }

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
        if ($this->isCsrfTokenValid('delete'.$position->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_index');
    }
}
