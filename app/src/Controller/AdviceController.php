<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Entity\Month;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class AdviceController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer tous les conseils d'un mois précis ou de celui en cours
     *
     * @param AdviceRepository
     * @param SerializerInterface
     * @return JsonResponse
     */
    #[Route('/api/conseil', name: 'advice_current_month', methods: ['GET'])]
    #[Route('/api/conseil/{id}', name: 'advice', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER', message: 'Vous devez vous authentifier.')]
    public function getAllAdvicesThisMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer, ?int $id = null, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $month = $id ?? (int)date('n');

        if ($month < 1 || $month > 12) {
            throw new NotFoundHttpException('Le mois selectionné n\'existe pas. Veuillez indiquer un mois dans le format numérique entre 1 et 12');
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $idCache = "getAllAdvicesThisMonth-" . $month . "-" . $page . "-" . $limit;

        $adviceList = $cache->get($idCache, function (ItemInterface $item) use ($adviceRepository, $month, $page, $limit) {
            $item->tag("advicesCache");
            return $adviceRepository->findByMonthWithPagination($month, $page, $limit);
        });

        return $this->json($adviceList, Response::HTTP_OK, [], ['groups' => 'getAdvices']);
    }

    /**
     * Cette méthode permet d'insérer un nouveau conseil. 
     * Exemple de données : 
     * {
     *     "text": "Ceci est un conseil",
     *     "month": 3 // Pas encore opti
     * }
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param MonthRepository $monthRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/conseil', name:"createAdvice", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function createAdvice(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, MonthRepository $monthRepository, ValidatorInterface $validator): JsonResponse {

        try {
            $content = $request->toArray();
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Le format JSON est invalide : ' . $e->getMessage());
        }

        if (!isset($content['text']) || empty(trim($content['text']))) {
            return $this->json([
                'error' => 'Validation échouée',
                'details' => ['text' => 'Le champ text est requis et ne peut pas être vide']
            ], Response::HTTP_BAD_REQUEST);
        }

        $monthValue = $content['month'] ?? (int)date('n');
        if (!is_numeric($monthValue) || $monthValue < 1 || $monthValue > 12) {
            return $this->json([
                'error' => 'Validation échouée',
                'details' => ['month' => 'Le mois doit être un nombre entre 1 et 12']
            ], Response::HTTP_BAD_REQUEST);
        }

        $monthEntity = $monthRepository->findByNumericValue($monthValue);
        if (!$monthEntity) {
            return $this->json([
                'error' => 'Ressource non trouvée',
                'details' => ['month' => 'Le mois spécifié n\'existe pas en base de données']
            ], Response::HTTP_BAD_REQUEST);
        }

        $advice = new Advice();
        $advice->setText(trim($content['text']));
        $advice->addMonth($monthEntity);

        $errors = $validator->validate($advice);
        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation échouée',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $em->persist($advice);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'details' => 'Impossible de sauvegarder le conseil. Veuillez réessayer.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }


    #[Route('/api/conseil/{id}', name:"updateAdvice", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour le conseil')]
    public function updateAdvice(int $id, AdviceRepository $adviceRepository, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $advice = $adviceRepository->find($id);
        if (!$advice) {
            return $this->json([
                'error' => 'Ressource non trouvée',
                'details' => 'Le conseil avec l\'ID ' . $id . ' n\'existe pas'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $request->toArray();
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Le format JSON est invalide : ' . $e->getMessage());
        }

        if (isset($content['text']) && empty(trim($content['text']))) {
            return $this->json([
                'error' => 'Validation échouée',
                'details' => ['text' => 'Le champ text ne peut pas être vide']
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedAdvice = $serializer->deserialize($request->getContent(),
                    Advice::class,
                    'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $advice]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur de désérialisation',
                'details' => 'Impossible de traiter les données fournies'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validation de l'entité mise à jour
        $errors = $validator->validate($updatedAdvice);
        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation échouée',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $em->persist($updatedAdvice);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'details' => 'Impossible de mettre à jour le conseil. Veuillez réessayer.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/conseil/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteAdvice(int $id, AdviceRepository $adviceRepository, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $advice = $adviceRepository->find($id);
        if($advice)
        {
            $cachePool->invalidateTags(["advicesCache"]);
            foreach ($advice->getMonth() as $month) {
                $advice->removeMonth($month);
            }
            $em->remove($advice);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        throw new NotFoundHttpException(message:'Le conseil selectionné n\'existe pas. Veuillez réessayer.');
    }
}