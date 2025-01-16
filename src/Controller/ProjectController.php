<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
// use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProjectController extends AbstractController
{
    #[Route('/api/projects', name: 'projects', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'unauthorized to access data')]
    public function index(ProjectRepository $projectRepository, SerializerInterface $serializerInterface, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $idProjects = 'getAllProjects-' . $page . '-' . $limit;
        $jsonProjects  = $cache->get(
            $idProjects,
            function (ItemInterface $item) use ($projectRepository, $page, $limit, $serializerInterface) {
                echo ("L'element n'est encore en cache \n");
                $item->tag('projectsCache');
                $projectList = $projectRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(['getProjects']);
                return $serializerInterface->serialize($projectList, 'json', $context);
            }
        );
        // $projects = $serializerInterface->serialize($jsonProjects, 'json', ['groups' => 'getProjects']);
        return new JsonResponse($jsonProjects, Response::HTTP_OK, [], true);
    }


    #[Route('/api/projects/{id}', name: 'one_project', methods: ['GET'])]
    public function getOneProject(int $id, ProjectRepository $projectRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $project  = $projectRepository->find($id);
        if ($project) {
            $context = SerializationContext::create()->setGroups(['getProjects']);
            $getProject = $serializerInterface->serialize($project, 'json', $context);
            return new JsonResponse($getProject, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/projects', name: 'add_project', methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: 'unauthorized to access data')]
    public function addProject(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository, ValidatorInterface $validator): JsonResponse
    {
        $project = $serializer->deserialize($request->getContent(), Project::class, 'json');

        $errors = $validator->validate($project);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        $client_id = $content['client_id'] ?? -1;

        $project->setClient($userRepository->find($client_id));

        // Persist and flush the user data
        $em->persist($project);
        $em->flush();
        $context = SerializationContext::create()->setGroups(['getProjects']);
        $addProject = $serializer->serialize($project, 'json', $context);

        return new JsonResponse($addProject, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/projects/{id}', name: 'update_project', methods: ['PUT'])]
    public function updateProject(Request $request, Project $currentProject,  SerializerInterface $serializer, EntityManagerInterface $em, UserRepository $userRepository, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $newProject = $serializer->deserialize($request->getContent(), Project::class, 'json');
        $currentProject->setName($newProject->getName());
        $currentProject->setDescription($newProject->getDescription());
        $currentProject->setStartDate($newProject->getStartDate());
        $currentProject->setEndDate($newProject->getEndDate());
        $currentProject->setClient($currentProject->getClient());


        // On vérifie les erreurs
        $errors = $validator->validate($currentProject);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $clientId = $content['client_id'] ?? -1;

        $currentProject->setClient($userRepository->find($clientId));

        $em->persist($currentProject);
        $em->flush();

        // On vide le cache.
        $cache->invalidateTags(['projectsCache']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/projects/{id}', name: 'delete_project', methods: ['DELETE'])]
    public function deleteProject(int $id, ProjectRepository $projectRepository, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $project  = $projectRepository->find($id);
        if ($project) {
            //clear cache
            $cache->invalidateTags(['projectsCache']);
            $em->remove($project);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
