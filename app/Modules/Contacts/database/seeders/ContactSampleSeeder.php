<?php

namespace App\Modules\Contacts\database\seeders;

use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSampleSeeder extends Seeder
{
    private const TENANT_ID = 1;

    public function run(): void
    {
        $company = Contact::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'type' => 'company', 'name' => 'PT Demo Nusantara'],
            [
                'tenant_id' => self::TENANT_ID,
                'company_id' => 1,
                'branch_id' => null,
                'email' => 'hello@demo-nusantara.test',
                'phone' => '0215550001',
                'mobile' => '628111000100',
                'website' => 'https://demo-nusantara.test',
                'industry' => 'Retail',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'street' => 'Jl. Jenderal Sudirman No. 1',
                'notes' => 'Perusahaan contoh untuk modul Contacts, Sales, dan Email Marketing.',
                'is_active' => true,
            ]
        );

        Contact::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'type' => 'individual', 'email' => 'procurement@demo-nusantara.test'],
            [
                'tenant_id' => self::TENANT_ID,
                'company_id' => 1,
                'branch_id' => null,
                'parent_contact_id' => $company->id,
                'name' => 'Rina Procurement',
                'job_title' => 'Procurement Lead',
                'phone' => '0215550002',
                'mobile' => '628111000101',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'notes' => 'PIC contoh untuk transaksi penjualan dan campaign email.',
                'is_active' => true,
            ]
        );
    }
}


