<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('model_others', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('carbon_at')->nullable();
            $table->timestamp('other_at')->nullable();
            $table->timestamp('date_at')->nullable();
            $table->timestamps();
        });
    }

    /** @test */
    public function it_asserts_casts()
    {
        $this->assertCasts(ModelOther::class,  ['published_at' => 'datetime']);
    }

    /** @test */
    public function it_fails_if_asserts_casts(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The 'casts' array in");

        $this->assertCasts(ModelOther::class,  ['published_at' => 'date']);
    }

    /** @test */
    public function it_asserts_appends()
    {
        $this->assertAppends(ModelOther::class,  ['full_name']);
    }

    /** @test */
    public function it_fails_if_asserts_appends()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The 'appends' array in");

        $this->assertAppends(ModelOther::class,  ['last_name']);
    }

    /** @test */
    public function it_asserts_get_route_key_name()
    {
        $this->assertGetRouteKeyName(ModelOther::class, 'slug');
    }

    /** @test */
    public function it_fails_if_model_asserts_get_route_key_name()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The route key name for ModelOther should be title.");

        $this->assertGetRouteKeyName(ModelOther::class, 'title');
    }

    /** @test */
    public function it_fails_if_database_asserts_get_route_key_name()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("The table 'model_others' does not contain the expected column 'content'");

        $this->assertGetRouteKeyName(ModelOther::class, 'content');
    }

    /** @test */
    public function it_fails_if_asserts_set_timestamp_attribute()
    {
        $this->assertSetTimestampAttribute(ModelOther::class, 'published_at');
    }

    /** @test */
    public function it_fails_if_asserts_not_exists_method_set_timestamp_attribute()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The method setCarbonAtAttribute does not exist in the model ModelCarbon.");

        $this->assertSetTimestampAttribute(ModelCarbon::class, 'carbon_at');
    }


    /** @test */
    public function it_fails_if_asserts_instance_of_carbon_set_timestamp_attribute()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The attribute other_at was not cast to an instance of Carbon.");

        $this->assertSetTimestampAttribute(ModelOther::class, 'other_at');
    }

    /** @test */
    public function it_fails_if_asserts_not_null_set_timestamp_attribute()
    {
        $this->assertSetTimestampAttribute(ModelCarbon::class, 'date_at', null);
    }

    /** @test */
    public function it_fails_if_asserts_exist_column_database_timestamp_attribute()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The table 'model_others' does not contain the expected column 'database_at'.");

        $this->assertSetTimestampAttribute(ModelOther::class, 'database_at', '2025-01-01');
    }

}

class ModelOther extends Model
{
    protected $guarded = [];
    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return 'Test';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setPublishedAtAttribute($value)
    {
        return $this->attributes['published_at'] = $value ? Carbon::parse($value) :  null;
    }

    public function setOtherAtAttribute($value)
    {
        return  $value;
    }
}

class ModelCarbon extends Model
{
    protected $fillable = [
        'date_at',
    ];
    protected $table = 'model_others';

    protected $casts = [
        'carbon_at' => 'datetime',
        'date_at' => 'datetime',
    ];

    public function setDateAtAttribute($value)
    {
        return null;
    }
}
