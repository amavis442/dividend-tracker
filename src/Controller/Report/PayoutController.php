<?php

namespace App\Controller\Report;

use App\Repository\PaymentRepository;
use App\Service\Payouts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/dashboard/report")
 */
class PayoutController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/payout", name="report_payout")
     */
    public function index(
        PaymentRepository $paymentRepository,
        Payouts $payout,
        UserInterface $user
    ): Response {
        $result = $payout->payout($paymentRepository, $user);

        return $this->render('report/payout/index.html.twig', array_merge($result, [
            'controller_name' => 'ReportController',
        ]));
    }
}
