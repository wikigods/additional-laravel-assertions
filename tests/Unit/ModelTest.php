<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('model_others', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('slug')->nullable();
        });
    }

    /** @test */
    public function it_asserts_casts()
    {
        $this->assertCasts(ModelOther::class,  ['published_at' => 'datetime']);
    }

    /** @test */
    public function it_asserts_appends()
    {
        $this->assertAppends(ModelOther::class,  ['full_name']);
    }

    /** @test */
    public function it_asserts_get_route_key_name()
    {
        $this->assertGetRouteKeyName(ModelOther::class, 'slug');
    }

    /** @test */
    public function it_asserts_set_timestamp_attribute()
    {
        $this->assertSetTimestampAttribute(ModelOther::class, 'published_at');
    }

}

class ModelOther extends Model {
    protected $guarded = [];
    protected $casts = [
        'published_at' => 'datetime'
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return 'Test';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}