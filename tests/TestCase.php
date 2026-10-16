<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * SAFETY GUARD — runs before any test touches the database.
     *
     * Feature tests use RefreshDatabase, which DROPS AND RECREATES EVERY
     * TABLE. phpunit.xml points tests at a throwaway in-memory SQLite DB,
     * but Laravel ignores phpunit.xml when the config is cached
     * (bootstrap/cache/config.php from `php artisan config:cache`). On the
     * production server that would silently aim the tests at the live RDS
     * database and wipe it. So: refuse to run unless we really are on the
     * in-memory SQLite database in the "testing" environment.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default  = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$default}.database");
        $env      = $app->environment();

        if ($env !== 'testing' || $default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests: they would use database connection '{$default}' "
                . "(database '{$database}', APP_ENV '{$env}') instead of the in-memory SQLite test database, "
                . "and RefreshDatabase would wipe it. "
                . ($app->configurationIsCached()
                    ? "The config is cached — run `php artisan config:clear` first. "
                    : '')
                . "Tests need the pdo_sqlite extension; see the deploy guide."
            );
        }

        return $app;
    }
}
