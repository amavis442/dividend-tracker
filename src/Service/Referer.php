<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Referer
{
    protected $requestStack;
    protected $session;
    protected $request;
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->session = $this->requestStack->getSession();
        $this->request = $this->requestStack->getCurrentRequest();
        $this->urlGenerator = $urlGenerator;
    }

    public function set(?string $name = null, array $parameters = []): void
    {
        $referer = $this->request->headers->get('referer');
        if ($name) {
            $referer = $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $this->session->set('referer', $referer);
    }

    public function get(): ?string
    {
        return $this->session->get('referer');
    }
}
