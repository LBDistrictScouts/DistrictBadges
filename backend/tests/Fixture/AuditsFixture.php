<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AuditsFixture
 */
class AuditsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '003b39f5-34f6-4f49-b1ff-97204ffc4336',
                'user_id' => '30350fc5-a8b7-4b3e-85ae-9f2f5f3a30e1',
                'audit_timestamp' => 1771723146,
                'audit_completed' => 1,
            ],
        ];
        parent::init();
    }
}
