<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Referer
{
    protected const SESSION_REFERER_KEY = 'referer';
    protected RequestStack $requestStack;
    protected SessionInterface $session;
    protected ?Request $request;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
        $this->session = $this->requestStack->getSession();
        $this->request = $this->requestStack->getCurrentRequest();
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Set the url with parameters to refer to from other pages.
     * If no request object is set, then it will clear the referer
     */
    public function set(?string $name = null, array $parameters = []): void
    {
        $this->session->remove(self::SESSION_REFERER_KEY);
        if ($this->request != null) {
            $referer = $this->request->headers->get('referer'); // Header tag referer.
            if ($name != null) {
                $referer = $this->urlGenerator->generate(
                    $name,
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $this->session->set(self::SESSION_REFERER_KEY, $referer);
        }
    }

    /**
     * Get the session key referer or null
     */
    public function get(): ?string
    {
        return $this->session->get('referer');
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_REFERER_KEY);
    }
}
