<?php

namespace App\Support;

class DeploySmokeRunner
{
    public function __construct(private SystemHealthChecker $checker) {}

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function run(?string $baseUrl = null): array
    {
        return $this->checker->deploySmokeChecks($baseUrl);
    }
}
