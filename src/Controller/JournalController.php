<?php

namespace App\Controller;

use App\Entity\Journal;
use App\Form\JournalType;
use App\Repository\JournalRepository;
use App\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/journal')]
class JournalController extends AbstractController
{
    public const TAXONOMY_KEY = 'journal_taxonomy';

    #[
        Route(
            path: '/list/{page<\d+>?1}',
            name: 'journal_index',
            methods: ['GET']
        )
    ]
    public function index(
        Request $request,
        JournalRepository $journalRepository,
        TaxonomyRepository $taxonomyRepository,
        int $page = 1
    ): Response {
        $taxonomySelected = $request
            ->getSession()
            ->get(self::TAXONOMY_KEY, null);

        if (!is_null($taxonomySelected)) {
            $taxonomySelected = array_flip($taxonomySelected);
        }

        $queryBuilder = $journalRepository->findItemsQuery($taxonomySelected);
        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(5);
        $pager->setCurrentPage($page);

        $taxonomy = $taxonomyRepository->findLinked();

        return $this->render('journal/index.html.twig', [
            'pager' => $pager,
            'taxonomy' => $taxonomy,
            'taxonomySelected' => $taxonomySelected ?? [],
            'thisPage' => $page,
            'routeName' => 'journal_index',
        ]);
    }

    #[Route(path: '/create', name: 'journal_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $journal = new Journal();
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($journal);
            $entityManager->flush();

            return $this->redirectToRoute('journal_index');
        }

        return $this->render('journal/new.html.twig', [
            'journal' => $journal,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'journal_show', methods: ['GET'])]
    public function show(Journal $journal): Response
    {
        return $this->render('journal/show.html.twig', [
            'journal' => $journal,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'journal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Journal $journal
    ): Response {
        $form = $this->createForm(JournalType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('journal_index');
        }

        return $this->render('journal/edit.html.twig', [
            'journal' => $journal,
            'form' => $form->createView(),
        ]);
    }

    #[
        Route(
            path: '/delete/{id}',
            name: 'journal_delete',
            methods: ['POST', 'DELETE']
        )
    ]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Journal $journal
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $journal->getId(),
                $request->request->get('_token')
            )
        ) {
            $entityManager->remove($journal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('journal_index');
    }

    #[Route(path: '/taxonomy', name: 'journal_taxonomy', methods: ['POST'])]
    public function pie(Request $request): Response
    {
        $taxonomy = $request->request->all('taxonomy'); // ->get('taxonomy', []);
        $request->getSession()->set(self::TAXONOMY_KEY, $taxonomy);
        return $this->redirectToRoute('journal_index');
    }
}
