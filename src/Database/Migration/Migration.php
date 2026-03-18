<?php

namespace Swilen\Database\Migration;

use Swilen\Database\Connection;

abstract class Migration
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    abstract public function up(): void;

    abstract public function down(): void;
}
