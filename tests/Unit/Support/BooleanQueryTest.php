<?php

namespace Tests\Unit\Support;

use App\Support\BooleanQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BooleanQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('boolean_query_parents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('boolean_query_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('boolean_query_parents')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('boolean_query_children');
        Schema::dropIfExists('boolean_query_parents');

        parent::tearDown();
    }

    public function test_apply_accepts_relation_instances_used_in_eager_load_callbacks(): void
    {
        $parent = BooleanQueryTestParent::query()->create([
            'name' => 'Parent',
        ]);

        $parent->children()->create([
            'name' => 'Child Active',
            'is_active' => true,
        ]);

        $parent->children()->create([
            'name' => 'Child Inactive',
            'is_active' => false,
        ]);

        $loaded = BooleanQueryTestParent::query()
            ->with([
                'children' => fn ($query) => BooleanQuery::apply(
                    $query->whereNull('deleted_at'),
                    'is_active'
                ),
            ])
            ->findOrFail($parent->id);

        $this->assertCount(1, $loaded->children);
        $this->assertSame('Child Active', $loaded->children->first()->name);
    }
}

class BooleanQueryTestParent extends Model
{
    protected $table = 'boolean_query_parents';

    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(BooleanQueryTestChild::class, 'parent_id');
    }
}

class BooleanQueryTestChild extends Model
{
    protected $table = 'boolean_query_children';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
