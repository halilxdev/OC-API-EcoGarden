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

        $content = $request->toArray();
        $advice = new Advice();
        $advice->setText($content['text']);
        $errors = $validator->validate($advice);
        if ($errors->count() > 0) {
            throw new NotFoundHttpException('Une erreur est survenue. Un ou plusieurs champs sont vides ou incorrects.');
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
    public function updateAdvice(int $id, AdviceRepository $adviceRepository, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse 
    {
        $advice = $adviceRepository->find($id);
        if($advice)
        {
            $updatedAdvice = $serializer->deserialize($request->getContent(),
                    Advice::class,
                    'json',
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $advice]);
            $em->persist($updatedAdvice);
            $em->flush();
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        }
        throw new NotFoundHttpException('Le conseil selectionné n\'existe pas. Veuillez réessayer.');
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