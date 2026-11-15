<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => 'test-only-secret-with-at-least-32-characters']);
        $payload = app('tymon.jwt.payload.factory')->customClaims(['sub' => '1', 'role' => 'admin'])->make();
        $this->withToken(app('tymon.jwt.manager')->encode($payload)->get());
    }
}
