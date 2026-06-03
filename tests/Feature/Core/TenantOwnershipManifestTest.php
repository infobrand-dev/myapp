<?php

namespace Tests\Feature\Core;

use App\Multitenancy\TenantOwnershipManifest;
use Tests\TestCase;

class TenantOwnershipManifestTest extends TestCase
{
    public function test_central_models_are_classified_as_central_and_use_central_connection(): void
    {
        $manifest = app(TenantOwnershipManifest::class);

        foreach ($manifest->centralModelClasses() as $class) {
            $this->assertSame(TenantOwnershipManifest::CENTRAL, $manifest->classifyModel($class), $class . ' should be central.');
            $this->assertSame('central', (new $class())->getConnectionName(), $class . ' should use the central connection.');
        }
    }

    public function test_representative_tenant_models_are_not_misclassified_as_central(): void
    {
        $manifest = app(TenantOwnershipManifest::class);

        foreach ($manifest->tenantRouteBindingModelClasses() as $class) {
            $this->assertNotSame(TenantOwnershipManifest::CENTRAL, $manifest->classifyModel($class), $class . ' should remain tenant-scoped.');
        }
    }
}
