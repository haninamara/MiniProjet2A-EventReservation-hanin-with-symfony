<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ApiController extends AbstractController
{
    #[Route('/api/test', name: 'api_test')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function test(): JsonResponse
    {
        $user = $this->getUser();
        return new JsonResponse([
            'message' => 'You are authenticated!',
            'user' => [
                'username' => $user->getUsername(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}