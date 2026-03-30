<?php

namespace App\Modules\Crm\database\seeders;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmStageCatalog;
use Illuminate\Database\Seeder;

class CrmSampleSeeder extends Seeder
{
    public function run(): void
    {
        $contact = Contact::query()->where('tenant_id', 1)->orderBy('id')->first();

        if (!$contact) {
            return;
        }

        foreach (array_keys(CrmStageCatalog::options()) as $index => $stage) {
            CrmLead::query()->firstOrCreate([
                'tenant_id' => 1,
                'contact_id' => $contact->id,
                'stage' => $stage,
            ], [
                'title' => 'Sample ' . str($stage)->replace('_', ' ')->title(),
                'priority' => 'medium',
                'lead_source' => 'sample_data',
                'estimated_value' => 1000000 + ($index * 500000),
                'currency' => 'IDR',
                'probability' => min(100, 20 + ($index * 15)),
                'position' => $index + 1,
            ]);
        }
    }
}


