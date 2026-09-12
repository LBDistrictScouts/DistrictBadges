<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/** Records progress for the Kubernetes worker liveness probe. */
class WorkerHeartbeat
{
    private string $path;

    /**
     * @param string|null $path Path used for the heartbeat file.
     */
    public function __construct(?string $path = null)
    {
        $this->path = $path ?? sys_get_temp_dir() . '/district-badges-worker/heartbeat';
    }

    /**
     * Mark the current queue-polling loop as healthy.
     */
    public function touch(): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create worker heartbeat directory "%s".', $directory));
        }

        if (file_put_contents($this->path, (string)time() . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Could not write worker heartbeat file "%s".', $this->path));
        }
    }
}
