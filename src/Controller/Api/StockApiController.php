<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\StockItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StockApiController extends AbstractController
{
    #[Route('/get-stocks', name: 'api_get_stocks', methods: ['GET'])]
    public function getStocks(Request $request, StockItemRepository $stockItemRepository): JsonResponse
    {
        $mpn = $request->query->get('mpn');
        $ean = $request->query->get('ean');

        $hasMpn = is_string($mpn) && '' !== $mpn;
        $hasEan = is_string($ean) && '' !== $ean;

        if (!$hasMpn && !$hasEan) {
            return new JsonResponse([
                'error' => 'Bad Request',
                'message' => 'At least one query attribute (mpn or ean) must be specified.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $stockItems = $stockItemRepository->findByMpnOrEan(
            $hasMpn ? $mpn : null,
            $hasEan ? $ean : null,
        );

        dd($stockItems);

        $responseData = [];
        foreach ($stockItems as $item) {
            $responseData[] = [
                'id' => $item->getId(),
                'ean' => $item->getEan(),
                'mpn' => $item->getMpn(),
                'producer_name' => $item->getProducerName(),
                'external_id' => $item->getExternalId(),
                'price' => (float) $item->getPrice(),
                'quantity' => $item->getQuantity(),
                'supplier' => $item->getSupplier(),
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }
}
