<?php
namespace App\Controller;

use App\Entity\User;
use App\Service\PasskeyAuthService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    // After (temporary):
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
        // private RefreshTokenManagerInterface $refreshManager
    ) {}
    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        if (!$email) {
            return $this->json(['error' => 'Email requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]) ?? new User($email);

        try {
            $options = $passkeyService->getRegistrationOptions($user);
            return $this->json($options);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $credential = $data['credential'] ?? null;

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$credential) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $passkeyService->verifyRegistration($credential, $user);

            $jwt = $this->jwtManager->create($user);
            $refresh = $this->refreshManager->createForUser($user);

            return $this->json([
                'success' => true,
                'token' => $jwt,
                'refresh_token' => $refresh->getRefreshToken(),
                'user' => ['id' => $user->getId(), 'email' => $user->getEmail()]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(PasskeyAuthService $passkeyService): JsonResponse
    {
        try {
            return $this->json($passkeyService->getLoginOptions());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(Request $request, PasskeyAuthService $passkeyService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $passkeyService->verifyLogin($credential);
            $jwt = $this->jwtManager->create($user);
            $refresh = $this->refreshManager->createForUser($user);

            return $this->json([
                'success' => true,
                'token' => $jwt,
                'refresh_token' => $refresh->getRefreshToken(),
                'user' => ['id' => $user->getId(), 'email' => $user->getEmail()]
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }


}