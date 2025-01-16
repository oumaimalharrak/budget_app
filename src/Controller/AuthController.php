<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;


class AuthController extends AbstractController
{
    #[Route('/api/getAllUsers', name: 'getUsersList', methods:['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'unauthorized to access data')]
    public function index(UserRepository $userRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $usersList  = $userRepository->findAll();
        $users = $serializerInterface->serialize($usersList, 'json', ['groups'=>'getClients']);

        return new JsonResponse($users, Response::HTTP_OK, [], true);
    }

    #[Route('/api/register', name: 'app_auth', methods:['POST'])]
    public function register(UserPasswordHasherInterface $userPasswordHasherInterface, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        // $user->setRoles(['ROLE_USER']);
        $user->setRoles($user->getRoles());

        $hashedPassword = $userPasswordHasherInterface->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        // Persist and flush the user data
        $em->persist($user);
        $em->flush();

        $userRegister = $serializer->serialize($user, 'json', ['groups'=>'getClients']);
    
        return new JsonResponse($userRegister, Response::HTTP_CREATED, [], true);
    }
}
