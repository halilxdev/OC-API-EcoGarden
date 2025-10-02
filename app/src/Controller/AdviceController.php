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
    #[Route('/api/advice', name: 'advice', methods: ['GET'])]
    public function getAllAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $adviceList = $adviceRepository->findAll();
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/advice/{id}', name: 'detailAdvice', methods: ['GET'])]
    public function getDetailBook(Advice $advice, SerializerInterface $serializer): JsonResponse 
    {
        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_OK, [], true);
    }
}