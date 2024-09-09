<?php

namespace App\Controller;

use ApiPlatform\Api\IriConverterInterface;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\Routing\Annotation\Route;
use \Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route('/logout', name: 'app_logout')]
    function logout(): void
    {
        throw new \RuntimeException('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    #[Route('/login/json_form', name: 'app_login_json')]
    public function index(NormalizerInterface $normalizer, #[CurrentUser] ?User $user = null): Response
    {
        return $this->render('security/login_json.html.twig', [
            'userData' => $normalizer->normalize($user, 'jsonld', [
                'groups' => ['user:read'],
            ]),
        ]);
    }

    #[Route('/login/json_result', name: 'app_login_json_result', methods: ['POST'])]
    public function loginJson(IriConverterInterface  $iriConverter, #[CurrentUser] ?User $user = null): Response
    {

        if (!$user) {
            return $this->json([
                'error' => 'Invalid login request: check that the Content-Type header is "application/json".',
            ], 401);
        }

        //$token = '...'; // somehow create an API token for $user

        return new Response(null, 204, [
            'Location' => $iriConverter->getIriFromResource($user),
        ]);

        /*
        return $this->json([
            'user' => $user->getId(),
            'token' => $token,
        ]);
        */
    }
}
