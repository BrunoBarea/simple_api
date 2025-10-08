<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $data = array_map(static function (User $user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            ];
        }, $users);

        return $this->json(['users' => $data]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuário não encontrado.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ]);
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON inválido.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));

        if ($username === '' || $email === '') {
            return $this->json(['error' => 'Os campos "username" e "email" são obrigatórios.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'E-mail inválido.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['username' => $username])) {
            return $this->json(['error' => 'Nome de usuário já está em uso.'], JsonResponse::HTTP_CONFLICT);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'E-mail já está cadastrado.'], JsonResponse::HTTP_CONFLICT);
        }

        $user = (new User())
            ->setUsername($username)
            ->setEmail($email);

        $plainPassword = bin2hex(random_bytes(8));
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $userRepository->save($user, true);

        $message = (new Email())
            ->to($user->getEmail())
            ->subject('Sua conta foi criada')
            ->text(sprintf(
                "Olá %s!\n\nSua conta foi criada com sucesso. Use a senha a seguir para acessar: %s",
                $user->getUsername(),
                $plainPassword
            ));

        $mailer->send($message);

        return $this->json([
            'message' => 'Usuário criado com sucesso. A senha foi enviada por e-mail.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_user_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuário não encontrado.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON inválido.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $username = isset($payload['username']) ? trim((string) $payload['username']) : null;
        $email = isset($payload['email']) ? trim((string) $payload['email']) : null;
        $plainPassword = isset($payload['password']) ? (string) $payload['password'] : null;

        if ($username !== null && $username !== $user->getUsername()) {
            if ($username === '') {
                return $this->json(['error' => 'O nome de usuário não pode ser vazio.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($userRepository->findOneBy(['username' => $username])) {
                return $this->json(['error' => 'Nome de usuário já está em uso.'], JsonResponse::HTTP_CONFLICT);
            }

            $user->setUsername($username);
        }

        if ($email !== null && $email !== $user->getEmail()) {
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(['error' => 'E-mail inválido.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            if ($userRepository->findOneBy(['email' => $email])) {
                return $this->json(['error' => 'E-mail já está cadastrado.'], JsonResponse::HTTP_CONFLICT);
            }

            $user->setEmail($email);
        }

        if ($plainPassword !== null) {
            if ($plainPassword === '') {
                return $this->json(['error' => 'A senha não pode ser vazia.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $user->setUpdatedAt(new DateTimeImmutable());
        $userRepository->save($user, true);

        return $this->json([
            'message' => 'Usuário atualizado com sucesso.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuário não encontrado.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $userRepository->remove($user, true);

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
