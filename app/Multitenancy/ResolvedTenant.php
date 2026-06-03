<?php

namespace App\Multitenancy;

use App\Models\Tenant;

class ResolvedTenant
{
    /** @var Tenant */
    public $tenant;

    /** @var string */
    public $connectionName;

    /** @var string */
    public $schemaName;

    /** @var string */
    public $databaseName;

    /** @var string */
    public $host;

    /** @var int */
    public $port;

    /** @var string|null */
    public $username;

    /** @var string|null */
    public $password;

    /** @var string */
    public $sslmode;

    /** @var array */
    public $options;

    public function __construct(
        Tenant $tenant,
        string $connectionName,
        string $schemaName,
        string $databaseName,
        string $host,
        int $port,
        ?string $username,
        ?string $password,
        string $sslmode = 'prefer',
        array $options = []
    ) {
        $this->tenant = $tenant;
        $this->connectionName = $connectionName;
        $this->schemaName = $schemaName;
        $this->databaseName = $databaseName;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->sslmode = $sslmode;
        $this->options = $options;
    }

    public function cachePrefix(): string
    {
        return 'tenant:' . $this->tenant->getKey() . ':' . $this->schemaName;
    }
}
