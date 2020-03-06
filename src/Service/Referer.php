<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Referer
{
    protected $session;
    protected $request;
    protected $container;

    public function __construct(SessionInterface $session, ContainerInterface $container)
    {
        $this->request = Request::createFromGlobals();
        $this->session = $session;
        $this->container = $container;
    }

    public function set(?string $name = null, array $parameters = []): void
    {
        $referer = $this->request->headers->get('referer');
        if ($name) {
            $referer = $this->container->get('router')->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }
        
        $this->session->set('referer', $referer);
    }

    public function get(): ?string
    {
        return $this->session->get('referer');
    }
}