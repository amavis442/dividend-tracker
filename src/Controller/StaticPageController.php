<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class StaticPageController extends AbstractController
{
    /**
     * @Route("/static/page", name="static_page")
     */
    public function index()
    {
        return $this->render('static_page/index.html.twig', [
            'controller_name' => 'StaticPageController',
        ]);
    }
}
