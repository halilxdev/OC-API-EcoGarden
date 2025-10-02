<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdviceController extends AbstractController
{
    #[Route('/api/advice', name: 'advice', methods: ['GET'])]
    public function getAllAdvices(AdviceRepository $adviceRepository): JsonResponse
    {
        $adviceList = $adviceRepository->findAll();

        return new JsonResponse([
            'advices' => $adviceRepository,
        ]);
    }
}
