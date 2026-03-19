<?php

namespace App\Modules\TaskManagement\Database\Seeders;

use App\Modules\TaskManagement\Models\Memo;
use App\Modules\TaskManagement\Models\Subtask;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskTemplate;
use App\Modules\TaskManagement\Models\TaskTemplateItem;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class TaskManagementSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;
        $userName = optional($user)->name;

        $memo = Memo::query()->updateOrCreate(
            ['title' => 'Memo Demo Aktivasi Campaign Ramadan'],
            [
                'company_name' => 'PT Demo Nusantara',
                'brand_name' => 'Demo Mart',
                'contact_name' => 'Rina Procurement',
                'job_title' => 'Procurement Lead',
                'phone' => '628111000101',
                'email' => 'procurement@demo-nusantara.test',
                'address' => 'Jakarta',
                'deadline' => now()->addWeek()->toDateString(),
                'account_executive' => $userName,
                'note' => 'Memo contoh untuk memperlihatkan task, subtask, dan progress.',
            ]
        );

        $task = Task::query()->updateOrCreate(
            ['memo_id' => $memo->id, 'title' => 'Siapkan materi presentasi'],
            [
                'description' => 'Buat deck singkat untuk campaign dan estimasi biaya.',
                'status' => 'in_progress',
                'due_date' => now()->addDays(3)->toDateString(),
                'assigned_to' => $userId,
            ]
        );

        Subtask::query()->updateOrCreate(
            ['task_id' => $task->id, 'title' => 'Kumpulkan kebutuhan promo'],
            [
                'status' => 'done',
                'pic' => $userId,
                'due_date' => now()->addDay()->toDateString(),
            ]
        );

        Subtask::query()->updateOrCreate(
            ['task_id' => $task->id, 'title' => 'Susun timeline campaign'],
            [
                'status' => 'pending',
                'pic' => $userId,
                'due_date' => now()->addDays(2)->toDateString(),
            ]
        );

        $template = TaskTemplate::query()->updateOrCreate(
            ['title' => 'Template Launch Campaign'],
            [
                'description' => 'Template tugas dasar untuk campaign baru.',
                'meta' => ['seeded' => true],
            ]
        );

        TaskTemplateItem::query()->updateOrCreate(
            ['task_template_id' => $template->id, 'title' => 'Riset target audience'],
            ['position' => 1]
        );

        TaskTemplateItem::query()->updateOrCreate(
            ['task_template_id' => $template->id, 'title' => 'Finalisasi visual promosi'],
            ['position' => 2]
        );
    }
}
