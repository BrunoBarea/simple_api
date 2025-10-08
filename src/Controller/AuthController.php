<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON inválido.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json(['error' => 'Usuário e senha são obrigatórios.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Credenciais inválidas.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Login realizado com sucesso.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ],
        ]);
    }
}
