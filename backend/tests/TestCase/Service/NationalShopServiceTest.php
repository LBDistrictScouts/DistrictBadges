<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\NationalShopService;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * App\Service\NationalShopService Test Case
 *
 * @link \App\Service\NationalShopService
 */
class NationalShopServiceTest extends TestCase
{
    /**
     * Test fetchProductByExternalId method
     *
     * @return void
     * @link \App\Service\NationalShopService::fetchProductByExternalId()
     */
    public function testFetchProductByExternalId(): void
    {
        $client = $this->createMock(Client::class);
        $response = $this->createStub(Response::class);
        $expected = ['data' => ['id' => 123, 'name' => 'Test Product']];

        $response->method('isOk')->willReturn(true);
        $response->method('getJson')->willReturn($expected);

        $client->expects($this->once())
            ->method('get')
            ->with(
                'https://shop.scouts.org.uk/api/n/load',
                [
                    'type' => 'product',
                    'verbosity' => 3,
                    'ids' => 123,
                    'pushDeps' => 'true',
                ],
            )
            ->willReturn($response);

        $service = new NationalShopService($client);
        $result = $service->fetchProductByExternalId(123);

        $this->assertSame($expected, $result);
    }

    public function testFetchProductByExternalIdThrowsOnFailure(): void
    {
        $client = $this->createMock(Client::class);
        $response = $this->createStub(Response::class);

        $response->method('isOk')->willReturn(false);
        $response->method('getStatusCode')->willReturn(500);

        $client->expects($this->once())
            ->method('get')
            ->with(
                'https://shop.scouts.org.uk/api/n/load',
                [
                    'type' => 'product',
                    'verbosity' => 3,
                    'ids' => 123,
                    'pushDeps' => 'true',
                ],
            )
            ->willReturn($response);

        $service = new NationalShopService($client);

        $this->expectException(RuntimeException::class);
        $service->fetchProductByExternalId(123);
    }

    public function testFetchProductByExternalIdThrowsOnInvalidJson(): void
    {
        $client = $this->createMock(Client::class);
        $response = $this->createStub(Response::class);

        $response->method('isOk')->willReturn(true);
        $response->method('getJson')->willReturn(null);

        $client->expects($this->once())
            ->method('get')
            ->with(
                'https://shop.scouts.org.uk/api/n/load',
                [
                    'type' => 'product',
                    'verbosity' => 3,
                    'ids' => 123,
                    'pushDeps' => 'true',
                ],
            )
            ->willReturn($response);

        $service = new NationalShopService($client);

        $this->expectException(RuntimeException::class);
        $service->fetchProductByExternalId(123);
    }
}
