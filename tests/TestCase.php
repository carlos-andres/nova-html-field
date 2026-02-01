<?php

namespace Vendor\NovaHtmlField\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Vendor\NovaHtmlField\FieldServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            NovaCoreServiceProvider::class,
            FieldServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
