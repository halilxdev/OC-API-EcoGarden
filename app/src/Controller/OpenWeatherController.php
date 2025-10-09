<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenWeatherController extends AbstractController
{
    /**
     * Cette méthode fait appel à l'API OpenWeather pour récupérer les données météo
     * et les transmet telles quelles.
     *
     * @param Request $request
     * @param HttpClientInterface $httpClient
     * @param User|null $user
     * @param string|null $city
     * @return JsonResponse
     */
    #[Route('/api/meteo/{city}', name: 'getWeatherByCity', methods: 'GET')]
    #[Route('/api/meteo/', name: 'getWeather', methods: 'GET')]
    public function getWeather(Request $request, HttpClientInterface $httpClient, #[CurrentUser] ?User $user = null, ?string $city = null): JsonResponse
    {
        $apiKey = $request->headers->get('X-API-Key') ?? $request->query->get('api_key');
        if (!$apiKey) {
            return new JsonResponse(['error' => 'Clé API manquante. Utilisez le header X-API-Key ou le paramètre api_key'], 400);
        }

        if ($city) {
            $normalizedCity = $this->normalizeCity($city);
            try {
                $response = $httpClient->request(
                    'GET',
                    sprintf('https://api.openweathermap.org/data/2.5/weather?q=%s,FR&appid=%s&units=metric&lang=fr', $normalizedCity, $apiKey)
                );
                return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Erreur lors de l\'appel à l\'API OpenWeather avec la ville',
                    'message' => $e->getMessage(),
                    'city' => $normalizedCity
                ], 500);
            }
        }

        $zipCode = $request->query->get('zip_code');

        if (!$zipCode && $user) {
            $zipCode = $user->getZipCode();
        }

        if (!$zipCode) {
            return new JsonResponse(['error' => 'Ville ou code postal manquant. Spécifiez une ville dans l\'URL, connectez-vous ou utilisez le paramètre zip_code'], 400);
        }

        if (!preg_match('/^[0-9]{5}$/', (string)$zipCode)) {
            return new JsonResponse(['error' => 'Code postal invalide. Doit être composé de 5 chiffres'], 400);
        }

        try {
            $response = $httpClient->request(
                'GET',
                sprintf('https://api.openweathermap.org/data/2.5/weather?zip=%s,FR&appid=%s&units=metric&lang=fr', $zipCode, $apiKey)
            );
            return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'appel à l\'API OpenWeather avec le code postal',
                'message' => $e->getMessage(),
                'zip_code' => $zipCode
            ], 500);
        }
    }

    /**
     * Normalise le nom de ville pour l'API OpenWeather (gère les accents et la casse)
     *
     * @param string $city
     * @return string
     */
    private function normalizeCity(string $city): string
    {
        // Décoder l'URL si nécessaire
        $city = urldecode($city);

        // Première lettre en majuscule, reste en minuscule
        $city = ucfirst(strtolower($city));

        // Remplacer les caractères spéciaux par leurs équivalents ASCII
        $city = str_replace(
            ['à', 'á', 'â', 'ã', 'ä', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'],
            ['a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'],
            $city
        );

        return $city;
    }
}
