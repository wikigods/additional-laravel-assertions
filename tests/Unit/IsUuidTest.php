<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class IsUuidTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('model_with_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        // Schema for a correctly configured model with a UUID
        Schema::create('model_with_uuids', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });

        // Schema for a model with an incorrect column type for UUIDs
        Schema::create('model_with_invalid_ids', function (Blueprint $table) {
            $table->integer('id')->primary();
        });
    }

    /** @test */
    public function it_asserts_a_model_is_correctly_configured_for_uuids(): void
    {
        $model = ModelWithUuid::create(); // This model is correctly configured

        $this->assertIsUuid($model);
    }

    /** @test */
    public function it_fails_if_the_model_is_not_configured_for_uuids(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/must have \'public \$incrementing = false;\'/');

        $model = new ModelWithIncorrectUuidSettings();

        $this->assertIsUuid($model);
    }

    /** @test */
    public function it_fails_if_the_database_column_type_is_not_a_uuid_compatible_type(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/is not suitable for UUIDs/');

        $model = new ModelWithInvalidIdColumn();

        $this->assertIsUuid($model);
    }
}

// Correctly configured model
class ModelWithId extends Model{
    public $timestamps = false;
}
class ModelWithUuid extends Model
{
    protected $table = 'model_with_uuids';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
}

// Incorrectly configured model
class ModelWithIncorrectUuidSettings extends Model
{
    protected $table = 'model_with_uuids';
    protected $keyType = 'string';
    public $incrementing = true; // This is incorrect for UUIDs
    public $timestamps = false;
}

// Model pointing to a table with an incorrect column type for UUIDs
class ModelWithInvalidIdColumn extends Model
{
    protected $table = 'model_with_invalid_ids';
    protected $keyType = 'string'; // Configuration is correct on the model
    public $incrementing = false; // but the database schema is wrong.
    public $timestamps = false;
}
