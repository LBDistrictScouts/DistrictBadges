<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class PadExistingEntityNumbers extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        foreach ($this->numberFields() as $table => $field) {
            $this->execute(sprintf(
                "UPDATE %s SET %s = REGEXP_REPLACE(%s, '-([0-9])$', '-0\\1') "
                . "WHERE %s ~ '-[0-9]$'",
                $table,
                $field,
                $field,
                $field,
            ));
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        foreach ($this->numberFields() as $table => $field) {
            $this->execute(sprintf(
                "UPDATE %s SET %s = REGEXP_REPLACE(%s, '-0([0-9])$', '-\\1') "
                . "WHERE %s ~ '-0[0-9]$'",
                $table,
                $field,
                $field,
                $field,
            ));
        }
    }

    /**
     * @return array<string, string>
     */
    private function numberFields(): array
    {
        return [
            'orders' => 'order_number',
            'fulfilments' => 'fulfilment_number',
            'replenishments' => 'wholesale_order_number',
            'invoices' => 'invoice_number',
        ];
    }
}
