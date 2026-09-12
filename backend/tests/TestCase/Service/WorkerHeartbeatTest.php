<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\WorkerHeartbeat;
use Cake\TestSuite\TestCase;

class WorkerHeartbeatTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/district-badges-worker-test-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $path = $this->directory . '/heartbeat';
        if (is_file($path)) {
            unlink($path);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function testTouchCreatesHeartbeatFile(): void
    {
        $path = $this->directory . '/heartbeat';

        (new WorkerHeartbeat($path))->touch();

        $this->assertFileExists($path);
        $this->assertSame(time() . PHP_EOL, file_get_contents($path));
    }
}
