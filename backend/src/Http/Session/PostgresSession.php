<?php
declare(strict_types=1);

namespace App\Http\Session;

use Cake\Http\Session\DatabaseSession;

class PostgresSession extends DatabaseSession
{
    private ?string $lockedId = null;

    /**
     * Preserve PHP's per-session locking across pods, including during Cognito token refresh.
     *
     * @param string $id Session ID.
     * @return string|false
     */
    public function read(string $id): string|false
    {
        if ($this->lockedId !== $id) {
            $this->close();
            $this->_table->getConnection()->execute(
                "SELECT pg_advisory_lock(hashtextextended('district-badges-session:' || :id, 0))",
                ['id' => $id],
                ['id' => 'string'],
            )->closeCursor();
            $this->lockedId = $id;
        }

        return parent::read($id);
    }

    /**
     * Release the lock on write-close, logout, or session ID regeneration.
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->lockedId !== null) {
            $this->_table->getConnection()->execute(
                "SELECT pg_advisory_unlock(hashtextextended('district-badges-session:' || :id, 0))",
                ['id' => $this->lockedId],
                ['id' => 'string'],
            )->closeCursor();
            $this->lockedId = null;
        }

        return true;
    }
}
