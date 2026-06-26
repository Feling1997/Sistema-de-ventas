<?php

declare(strict_types=1);

namespace Ventas\Core\Infrastructure\Persistence\Mysql;

use PDO;
use Throwable;
use Ventas\Core\Domain\Repositories\TransactionManager;

final class PdoTransactionManager implements TransactionManager
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(callable $operation): mixed
    {
        $result = null;

        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return $result;
    }
}
