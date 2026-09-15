<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\StockItemRepository;
use App\Service\Stock\Api\InvalidStockQueryException;
use App\Service\Stock\Api\StockItemResponseMapper;
use App\Service\Stock\Api\StockSearchCriteriaValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StockApiController extends AbstractController
{
    #[Route('/get-stocks', name: 'api_get_stocks', methods: ['GET'])]
    public function getStocks(
        Request $request,
        StockItemRepository $stockItemRepository,
        StockSearchCriteriaValidator $criteriaValidator,
        StockItemResponseMapper $responseMapper,
    ): JsonResponse {
        try {
            $criteria = $criteriaValidator->validate(
                $request->query->get('mpn'),
                $request->query->get('ean'),
            );
        } catch (InvalidStockQueryException $exception) {
            return new JsonResponse([
                'error' => 'Bad Request',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $stockItems = $stockItemRepository->findByMpnOrEan($criteria->mpn, $criteria->ean);
        $responseData = array_map(
            fn ($stockItem): array => $responseMapper->map($stockItem),
            $stockItems,
        );

        return new JsonResponse($responseData);
    }
}
