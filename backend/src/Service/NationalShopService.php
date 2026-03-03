<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\Client;
use RuntimeException;

class NationalShopService
{
    private Client $http;

    /**
     * @param \Cake\Http\Client|null $http HTTP client override.
     */
    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client();
    }

    /**
     * @param int $externalId External product id.
     * @return array<string, mixed>
     */
    public function fetchProductByExternalId(int $externalId): array
    {
        $response = $this->http->get('https://shop.scouts.org.uk/api/n/load', [
            'type' => 'product',
            'verbosity' => 3,
            'ids' => $externalId,
            'pushDeps' => 'true',
        ]);

        if (!$response->isOk()) {
            throw new RuntimeException(
                'National Shop API request failed. Status: ' . $response->getStatusCode(),
            );
        }

        $data = $response->getJson();

        if (!is_array($data)) {
            throw new RuntimeException('National Shop API returned invalid JSON.');
        }

        return $data;
    }
}
