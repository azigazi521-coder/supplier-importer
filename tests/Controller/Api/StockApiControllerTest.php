<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Factory\StockItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Doctrine\ORM\EntityManagerInterface;

class StockApiControllerTest extends WebTestCase
{
   
    use ResetDatabase, Factories;

    public function testGetStocksReturnsBadRequestWhenNoParametersProvided(): void
    {
        $client = static::createClient();

        $client->request('GET', '/get-stocks');

        $this->assertResponseStatusCodeSame(400);

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Bad Request', $responseContent['error']);
        $this->assertStringContainsString('At least one query attribute', $responseContent['message']);
    }

    public function testGetStocksFiltersByMpnSuccessfully(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        StockItemFactory::createOne([
            'mpn' => 'TARGET-MPN',
            'producerName' => 'BOSCH',
            'price' => 150.50,
            'quantity' => 5
        ]);

        StockItemFactory::createOne([
            'mpn' => 'OTHER-MPN',
            'producerName' => 'AMTRA'
        ]);

        $entityManager->flush();

        $client->request('GET', '/get-stocks?mpn=TARGET-MPN');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data);
        $this->assertSame('TARGET-MPN', $data[0]['mpn']);
        $this->assertSame('BOSCH', $data[0]['producer_name']);
        $this->assertSame(150.50, $data[0]['price']);
        $this->assertSame(5, $data[0]['quantity']);
    }

    public function testGetStocksFiltersByEanSuccessfully(): void
    {
        $client = static::createClient();

        StockItemFactory::createOne([
            'ean' => '9999999999999',
            'producerName' => 'FEBI'
        ]);

        $client->request('GET', '/get-stocks?ean=9999999999999');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame('9999999999999', $data[0]['ean']);
        $this->assertSame('FEBI', $data[0]['producer_name']);
    }
}
