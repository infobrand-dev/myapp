<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Illuminate\Support\Facades\DB;

trait RefreshesPgsqlDatabase
{
    use CanConfigureMigrationCommands;

    public function refreshDatabase()
    {
        $this->beforeRefreshingDatabase();

        if ($this->usingInMemoryDatabase()) {
            $this->restoreInMemoryDatabase();
        }

        $this->refreshTestDatabase();

        $this->afterRefreshingDatabase();
    }

    protected function usingInMemoryDatabase()
    {
        $default = config('database.default');

        return config("database.connections.$default.database") === ':memory:';
    }

    protected function restoreInMemoryDatabase()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            if (isset(RefreshDatabaseState::$inMemoryConnections[$name])) {
                $database->connection($name)->setPdo(RefreshDatabaseState::$inMemoryConnections[$name]);
            }
        }
    }

    protected function refreshTestDatabase()
    {
        if (config('database.default') !== 'pgsql') {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            $this->app[Kernel::class]->setArtisan(null);
            RefreshDatabaseState::$migrated = true;
            $this->beginDatabaseTransaction();

            return;
        }

        DB::unprepared('DROP SCHEMA IF EXISTS public CASCADE;');
        DB::unprepared('CREATE SCHEMA public;');
        DB::unprepared('GRANT ALL ON SCHEMA public TO public;');

        $this->artisan('migrate', $this->migrateFreshUsing());
        $this->app[Kernel::class]->setArtisan(null);
        RefreshDatabaseState::$migrated = false;

        $this->beginDatabaseTransaction();
    }

    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');
        $connections = $this->connectionsToTransact();

        $this->app->instance('db.transactions', $transactionsManager = new \Illuminate\Foundation\Testing\DatabaseTransactionsManager($connections));

        foreach ($connections as $name) {
            $connection = $database->connection($name);

            $connection->setTransactionManager($transactionsManager);

            if ($this->usingInMemoryDatabase()) {
                RefreshDatabaseState::$inMemoryConnections[$name] ??= $connection->getPdo();
            }

            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();

                if ($connection->getPdo() && ! $connection->getPdo()->inTransaction()) {
                    RefreshDatabaseState::$migrated = false;
                }

                $connection->rollBack();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
            ? $this->connectionsToTransact
            : [null];
    }

    protected function beforeRefreshingDatabase()
    {
        // no-op
    }

    protected function afterRefreshingDatabase()
    {
        if (config('database.default') !== 'pgsql') {
            return;
        }

        $columns = DB::select(<<<'SQL'
            select table_name, column_name
            from information_schema.columns
            where table_schema = 'public'
              and column_default like 'nextval(%'
        SQL);

        foreach ($columns as $column) {
            $table = (string) $column->table_name;
            $name = (string) $column->column_name;

            DB::statement(
                sprintf(
                    'select setval(pg_get_serial_sequence(\'"%s"\', \'%s\'), coalesce((select max("%s") from "%s"), 0) + 1, false)',
                    $table,
                    $name,
                    $name,
                    $table
                )
            );
        }
    }
}
