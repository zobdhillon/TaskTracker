<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setup(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
