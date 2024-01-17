<?php

namespace App\Controller;

use App\Entity\Taxonomy;
use App\Form\TaxonomyType;
use App\Repository\TaxonomyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/taxonomy')]
class TaxonomyController extends AbstractController
{
    #[Route(path: '/', name: 'app_taxonomy_index', methods: ['GET'])]
    public function index(TaxonomyRepository $taxonomyRepository): Response
    {
        return $this->render('taxonomy/index.html.twig', [
            'taxonomies' => $taxonomyRepository->findAll(),
        ]);
    }

    #[Route(path: '/new', name: 'app_taxonomy_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TaxonomyRepository $taxonomyRepository): Response
    {
        $taxonomy = new Taxonomy();
        $form = $this->createForm(TaxonomyType::class, $taxonomy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taxonomyRepository->add($taxonomy, true);

            return $this->redirectToRoute('app_taxonomy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('taxonomy/new.html.twig', [
            'taxonomy' => $taxonomy,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'app_taxonomy_show', methods: ['GET'])]
    public function show(Taxonomy $taxonomy): Response
    {
        return $this->render('taxonomy/show.html.twig', [
            'taxonomy' => $taxonomy,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'app_taxonomy_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Taxonomy $taxonomy, TaxonomyRepository $taxonomyRepository): Response
    {
        $form = $this->createForm(TaxonomyType::class, $taxonomy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taxonomyRepository->add($taxonomy, true);

            return $this->redirectToRoute('app_taxonomy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('taxonomy/edit.html.twig', [
            'taxonomy' => $taxonomy,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'app_taxonomy_delete', methods: ['POST'])]
    public function delete(Request $request, Taxonomy $taxonomy, TaxonomyRepository $taxonomyRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $taxonomy->getId(), $request->request->get('_token'))) {
            $taxonomyRepository->remove($taxonomy, true);
        }

        return $this->redirectToRoute('app_taxonomy_index', [], Response::HTTP_SEE_OTHER);
    }
}
