<?php

namespace App\Controller;

use App\Entity\Research;
use App\Entity\Ticker;
use App\Form\ResearchType;
use App\Repository\ResearchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/dashboard/research")
 */
class ResearchController extends AbstractController
{
    public const SEARCH_KEY = 'research_searchCriteria';
    
    /**
     * @Route("/list/{page}/{orderBy}/{sort}", name="research_index", methods={"GET"})
     */
    public function index(
        ResearchRepository $researchRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'id',
        string $sort = 'asc'
    ): Response {
        if (!in_array($orderBy, ['id','ticker'])) {
            $orderBy = 'id';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $researchRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('research/index.html.twig', [
            'researches' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'research_index',
            'searchPath' => 'research_search'
        ]);
    }

    /**
     * @Route("/new/{ticker?}", name="research_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?Ticker $ticker): Response
    {
        $research = new Research();
        if ($ticker instanceof Ticker) {
            $research->setTicker($ticker);
        }
        $form = $this->createForm(ResearchType::class, $research);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($research);
            $entityManager->flush();

            return $this->redirectToRoute('research_index');
        }

        return $this->render('research/new.html.twig', [
            'research' => $research,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="research_show", methods={"GET"})
     */
    public function show(Research $research): Response
    {
        return $this->render('research/show.html.twig', [
            'research' => $research,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="research_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Research $research): Response
    {
        $form = $this->createForm(ResearchType::class, $research);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('research_index');
        }

        return $this->render('research/edit.html.twig', [
            'research' => $research,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="research_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Research $research): Response
    {
        if ($this->isCsrfTokenValid('delete' . $research->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($research);
            $entityManager->flush();
        }

        return $this->redirectToRoute('research_index');
    }

    /**
     * @Route("/search", name="research_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('research_index');
    }
}
