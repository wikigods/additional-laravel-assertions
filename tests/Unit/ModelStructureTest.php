<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class ModelStructureTest extends TestCase
{
    /** @test */
    public function it_asserts_casts()
    {
        $model = new class extends Model {
            protected $casts = ['is_active' => 'boolean', 'options' => 'array'];
        };

        // Signature: ($modelClassOrInstance, array $attributes)
        $this->assertCasts(get_class($model), ['is_active' => 'boolean']);
    }

    /** @test */
    public function it_asserts_appends()
    {
        $model = new class extends Model {
            protected $appends = ['full_name'];
            public function getFullNameAttribute() { return 'Test'; }
        };

        $this->assertAppends(get_class($model), ['full_name']);
    }

    /** @test */
    public function it_asserts_get_route_key_name()
    {
        $model = new class extends Model {
            public function getRouteKeyName() { return 'slug'; }
        };

        $this->assertGetRouteKeyName(get_class($model), 'slug');
    }

    /** @test */
    public function it_asserts_set_timestamp_attribute()
    {
        $model = new class extends Model {
            protected $guarded = [];
            protected $casts = ['published_at' => 'datetime'];
        };

        // Validates that setting the attribute converts it to Carbon
        $this->assertSetTimestampAttribute(get_class($model), 'published_at');
    }
}