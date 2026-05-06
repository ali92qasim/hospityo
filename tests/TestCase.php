<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable multitenancy switching during tests
        config([
            'multitenancy.switch_tenant_tasks' => [],
            'permission.testing' => true,
        ]);
    }
}
