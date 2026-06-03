<?php

namespace Tests\Feature\Core;

use App\Multitenancy\QueryReadinessAuditService;
use Tests\TestCase;

class TenantQueryReadinessAuditTest extends TestCase
{
    public function test_query_readiness_audit_exposes_expected_sections(): void
    {
        $result = app(QueryReadinessAuditService::class)->audit();

        $this->assertArrayHasKey('ownership_manifest', $result);
        $this->assertArrayHasKey('central_models', $result);
        $this->assertArrayHasKey('storage_control_plane', $result);
        $this->assertArrayHasKey('storage_routing', $result);
        $this->assertArrayHasKey('queue_topology', $result);
        $this->assertArrayHasKey('tenant_models', $result);
        $this->assertArrayHasKey('raw_queries', $result);
        $this->assertArrayHasKey('migration_manifest', $result);

        foreach ($result as $issues) {
            $this->assertIsArray($issues);
        }
    }
}
