<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/subcategories', name: 'api_subcategories_')]
class SubCategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SubCategoryRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $subCategories = $repository->findAll();
        $data = $serializer->serialize($subCategories, 'json', ['groups' => 'subCategory:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(SubCategory $subCategory, SerializerInterface $serializer): JsonResponse
    {
        $data = $serializer->serialize($subCategory, 'json', ['groups' => 'subCategory:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $data = $request->getContent();
        $subCategory = $serializer->deserialize($data, SubCategory::class, 'json');

        $this->entityManager->persist($subCategory);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'SubCategory created successfully',
            'id' => $subCategory->getId(),
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, SubCategory $subCategory, SerializerInterface $serializer): JsonResponse
    {
        $data = $request->getContent();
        $serializer->deserialize($data, SubCategory::class, 'json', ['object_to_populate' => $subCategory]);

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'SubCategory updated successfully'], 200);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(SubCategory $subCategory): JsonResponse
    {
        $this->entityManager->remove($subCategory);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'SubCategory deleted successfully'], 200);
    }
}
