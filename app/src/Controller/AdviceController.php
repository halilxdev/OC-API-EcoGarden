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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function getAllAdvicesThisMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer, ?int $id = null): JsonResponse
    {
        $month = $id ?? (int)date('n');

        if ($month < 1 || $month > 12) {
            throw new NotFoundHttpException('Le mois selectionné n\'existe pas. Veuillez indiquer un mois dans le format numérique entre 1 et 12');
        }

        $adviceList = $adviceRepository->findByMonth($month);
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
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

        $content = $request->toArray();
        
        $advice = new Advice();
        $advice->setText($content['text']);

        // On vérifie les erreurs
        $errors = $validator->validate($advice);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $monthValue = $content['month'] ?? (int)date('n');
        $monthEntity = $monthRepository->findByNumericValue($monthValue);

        if ($monthEntity) {
            $advice->addMonth($monthEntity);
        }

        $em->persist($advice);
        $em->flush();

        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }


    #[Route('/api/conseil/{id}', name:"updateAdvice", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour le conseil')]
    public function updateAdvice(Request $request, SerializerInterface $serializer, Advice $currentAdvice, EntityManagerInterface $em): JsonResponse 
    {
        $updatedAdvice = $serializer->deserialize($request->getContent(), 
                Advice::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);
        $em->persist($updatedAdvice);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/conseil/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $em): JsonResponse
    {
        foreach ($advice->getMonth() as $month) {
            $advice->removeMonth($month);
        }

        $em->remove($advice);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}