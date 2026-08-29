<?php
declare(strict_types=1);

namespace App\Test\TestCase\Schema;

use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ScoutShopOrderV2SchemaTest extends TestCase
{
    public function testOptionalPostageAndDispatchAddressMayBeOmitted(): void
    {
        $this->assertSame([], $this->validate($this->validPayload()));
    }

    public function testAcceptsPostageAndCompleteDispatchAddress(): void
    {
        $payload = $this->validPayload() + [
            'postage' => true,
            'dispatch_address' => [
                'address_line_1' => '1 Scout Way',
                'address_line_2' => 'Gilwell Park',
                'town' => 'Chingford',
                'county' => 'London',
                'postcode' => 'E4 7QW',
            ],
        ];

        $this->assertSame([], $this->validate($payload));
    }

    public function testRejectsDispatchAddressMissingMandatoryField(): void
    {
        $payload = $this->validPayload() + [
            'dispatch_address' => [
                'address_line_1' => '1 Scout Way',
                'town' => 'Chingford',
            ],
        ];

        $errors = $this->validate($payload);
        $this->assertNotEmpty($errors);
        $this->assertSame('dispatch_address.postcode', $errors[0]['property']);
    }

    public function testRejectsNonBooleanPostage(): void
    {
        $errors = $this->validate($this->validPayload() + ['postage' => 'yes']);

        $this->assertNotEmpty($errors);
        $this->assertSame('postage', $errors[0]['property']);
    }

    public function testRejectsInvalidUkPostcode(): void
    {
        $payload = $this->validPayload() + [
            'dispatch_address' => [
                'address_line_1' => '1 Scout Way',
                'town' => 'Chingford',
                'postcode' => 'ABC 12DE',
            ],
        ];

        $errors = $this->validate($payload);
        $this->assertNotEmpty($errors);
        $this->assertSame('dispatch_address.postcode', $errors[0]['property']);
    }

    /**
     * @param array<string, mixed> $payload Order payload.
     * @return array<int, array<string, mixed>>
     */
    private function validate(array $payload): array
    {
        $schema = json_decode(
            (string)file_get_contents(CONFIG . 'schema/scout-shop-order-v2.json'),
            flags: JSON_THROW_ON_ERROR,
        );
        $data = json_decode(json_encode($payload, JSON_THROW_ON_ERROR));
        $validator = new Validator();
        $validator->validate($data, $schema);

        return $validator->getErrors();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'idempotency_key' => 'ef479a61-9278-4d83-b1ca-b86680f59d0e',
            'first_name' => 'Queue',
            'last_name' => 'Leader',
            'email' => 'queue@example.org',
            'group_id' => '4d5149f3-6214-4457-a04d-e428dc1200d7',
            'section_id' => 'd9534dcb-a846-5a22-a2fe-b67580555563',
            'lines' => [[
                'badge_id' => 'f525eb6d-021c-4ef2-811f-feac8db8d35d',
                'quantity' => 2,
                'unit_price' => 1.5,
            ]],
        ];
    }
}
