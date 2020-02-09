<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Referer
{
    protected $session;
    protected $request;

    public function __construct(SessionInterface $session)
    {
        $this->request = Request::createFromGlobals();
        $this->session = $session;
    }

    public function set(): void
    {
        $referer = $this->request->headers->get('referer');
        $this->session->set('referer', $referer);
    }

    public function get(): ?string
    {
        return $this->session->get('referer');
    }
}