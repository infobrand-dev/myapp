<?php

namespace Tests\Feature\Core;

use App\Support\TenantContext;
use RuntimeException;
use Tests\TestCase;

class TenantContextGuardrailTest extends TestCase
{
    public function test_current_id_throws_when_strict_mode_is_enabled_and_context_is_missing(): void
    {
        config()->set('multitenancy.strict', true);
        TenantContext::forget();

        $this->expectException(RuntimeException::class);

        TenantContext::currentId();
    }
}
