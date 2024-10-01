<?php

namespace App\Controller;

use App\Entity\Research;
use App\Entity\Ticker;
use App\Entity\Attachment;
use App\Entity\TickerAutocomplete;
use App\Form\ResearchType;
use App\Form\TickerAutocompleteType;
use App\Repository\ResearchRepository;
use App\Repository\TickerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route(path: '/dashboard/research')]
class ResearchController extends AbstractController
{
    public const SEARCH_KEY = 'research_searchCriteria';

    #[
        Route(
            path: '/list/{page}/{orderBy}/{sort}',
            name: 'research_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        ResearchRepository $researchRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $orderBy = 'id',
        #[MapQueryParameter] string $sort = 'asc'
    ): Response {
        if (!in_array($orderBy, ['id', 'symbol'])) {
            $orderBy = 'id';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $tickerAutoComplete = new TickerAutocomplete();
        $ticker = null;

        $tickerAutoCompleteCache = $request
            ->getSession()
            ->get(self::SEARCH_KEY, null);

        if ($tickerAutoCompleteCache instanceof TickerAutocomplete) {
            // We need a mapped entity else symfony will complain
            // This works, but i do not know if it is the best solution
            if (
                $tickerAutoCompleteCache->getTicker() &&
                $tickerAutoCompleteCache->getTicker()->getId()
            ) {
                $ticker = $tickerRepository->find(
                    $tickerAutoCompleteCache->getTicker()->getId()
                );
                $tickerAutoComplete->setTicker($ticker);
            }
        }

        /**
         * @var \Symfony\Component\Form\FormInterface $form
         */
        $form = $this->createForm(
            TickerAutocompleteType::class,
            $tickerAutoComplete,
            ['extra_options' => ['include_all_tickers' => true]]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticker = $tickerAutoComplete->getTicker();
            $request->getSession()->set(self::SEARCH_KEY, $tickerAutoComplete);
        }

        $queryBuilder = $researchRepository->getAllQuery(
            $orderBy,
            $sort,
            $ticker
        );

        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('research/index.html.twig', [
            'form' => $form,
            'pager' => $pager,
            'thisPage' => $page,
            'order' => $orderBy,
            'sort' => $sort,
        ]);
    }

    #[
        Route(
            path: '/create/{ticker?}',
            name: 'research_new',
            methods: ['GET', 'POST']
        )
    ]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Ticker $ticker,
        FileUploader $fileUploader,
        Referer $referer
    ): Response {
        $research = new Research();
        if ($ticker instanceof Ticker) {
            $research->setTicker($ticker);
        }
        $form = $this->createForm(ResearchType::class, $research);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attachments = $form->get('attachments')->getData();

            foreach ($attachments as $file) {
                $fileName = $fileUploader->upload($file);

                $attachment = new Attachment();
                $attachment->setAttachmentName($fileName);
                $attachment->setAttachmentSize($fileUploader->getSize());
                $research->addAttachment($attachment);
            }

            $entityManager->persist($research);
            $entityManager->flush();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('research_index');
        }

        $referer->set();

        return $this->render('research/new.html.twig', [
            'research' => $research,
            'form' => $form,
        ]);
    }

    #[Route(path: '/show/{id}', name: 'research_show', methods: ['GET'])]
    public function show(Research $research): Response
    {
        return $this->render('research/show.html.twig', [
            'research' => $research,
        ]);
    }

    #[
        Route(
            path: '/edit/{id}',
            name: 'research_edit',
            methods: ['GET', 'POST']
        )
    ]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Research $research,
        FileUploader $fileUploader,
        Referer $referer
    ): Response {
        $form = $this->createForm(ResearchType::class, $research);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attachments = $form->get('attachments')->getData();

            foreach ($attachments as $file) {
                $fileName = $fileUploader->upload($file);

                $attachment = new Attachment();
                $attachment->setAttachmentName($fileName);
                $attachment->setAttachmentSize($fileUploader->getSize());
                $research->addAttachment($attachment);
            }

            $entityManager->persist($research);
            $entityManager->flush();
            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('research_index');
        }

        $referer->set();

        return $this->render('research/edit.html.twig', [
            'research' => $research,
            'form' => $form,
        ]);
    }

    #[
        Route(
            path: '/delete/{id}',
            name: 'research_delete',
            methods: ['POST', 'DELETE']
        )
    ]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Research $research,
        Referer $referer
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $research->getId(),
                $request->request->get('_token')
            )
        ) {
            $entityManager->remove($research);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }

        return $this->redirectToRoute('research_index');
    }

    #[Route(path: '/search', name: 'research_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('research_index');
    }

    #[
        Route(
            path: '/attachment/delete/{id}',
            name: 'research_delete_attachment'
        )
    ]
    public function deleteAttachment(
        Attachment $attachment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (
            $this->isCsrfTokenValid(
                'delete' . $attachment->getId(),
                $data['_token']
            )
        ) {
            $name = $attachment->getAttachmentName();
            // On supprime le fichier
            unlink($this->getParameter('documents_directory') . '/' . $name);

            $entityManager->remove($attachment);
            $entityManager->flush();

            // On répond en json
            return new JsonResponse(['success' => 1]);
        } else {
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }
}
