<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration;

use App\Http\Session\PostgresSession;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\TableLocator;
use PHPUnit\Framework\TestCase;

class PostgresSessionTest extends TestCase
{
    /**
     * Exercise two independent connections against the same session, as two pods would.
     *
     * @return void
     */
    public function testSessionIsSharedAndLockedAcrossConnections(): void
    {
        $first = new PostgresSession();
        $connection = new Connection(['persistent' => false] + ConnectionManager::getConfig('test'));
        $locator = new TableLocator();
        $locator->setConfig('Sessions', ['connection' => $connection]);
        $second = new PostgresSession(['tableLocator' => $locator]);
        $id = bin2hex(random_bytes(16));
        $payload = "Auth|s:3:\"a\0b\";";

        try {
            $this->assertSame('', $first->read($id));
            $lock = $connection->execute(
                "SELECT pg_try_advisory_lock(hashtextextended('district-badges-session:' || :id, 0))::text",
                ['id' => $id],
            );
            $this->assertSame('false', $lock->fetchColumn(0));
            $lock->closeCursor();

            $this->assertTrue($first->write($id, $payload));
            $first->close();
            $this->assertSame($payload, $second->read($id));
            $this->assertTrue($second->write($id, $payload . 'updated'));
            $second->close();

            $this->assertSame($payload . 'updated', $first->read($id));
            $this->assertTrue($first->destroy($id));
            $first->close();
            $this->assertSame('', $second->read($id));
        } finally {
            $second->close();
            $first->destroy($id);
            $first->close();
            $connection->getDriver()->disconnect();
        }
    }
}
