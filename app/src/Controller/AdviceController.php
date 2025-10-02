<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AdviceController extends AbstractController
{
    #[Route('/api/conseil', name: 'advice_current_month', methods: ['GET'])]
    #[Route('/api/conseil/{id}', name: 'advice', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getAllAdvicesThisMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer, ?int $id = null): JsonResponse
    {
        $month = $id ?? (int)date('n');
        $adviceList = $adviceRepository->findByMonth($month);
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }
}