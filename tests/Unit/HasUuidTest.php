<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PDOException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class HasUuidTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('model_with_uuids', function ($table) {
            $table->uuid('id')->primary();
        });

        Schema::create('model_with_ids', function ($table) {
            $table->id('id');
        });
    }

    #[Test]
    public function it_passes_if_model_has_a_valid_uuid()
    {
        $model = ModelWithUuid::create();

        $this->assertHasUuid($model);
    }

    #[Test]
    public function it_fails_if_model_has_invalid_uuid()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage("The identifier 'id' of the ModelWithUuid model must be uuid.");

        $model = new ModelWithUuid(['id' => 'not-a-valid-uuid']);

        $this->assertHasUuid($model);
    }

    #[Test]
    public function it_fails_with_custom_message_if_database_column_is_not_uuid()
    {
        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage(
            "The getKeyName: id of the model_with_ids table is not UUID. Example: \$table->uuid('id')->primary();"
        );

        $model = new ModelWithId(['id' => 123]);

        $this->assertHasUuid($model, new PDOException("datatype mismatch", 0));
    }
}

class ModelWithUuid extends Model
{
    protected $table = 'model_with_uuids';

    protected $keyType = 'string';

    protected $fillable = ['id'];

    public $incrementing = false;

    public $timestamps = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Str::uuid()->toString();
        });
    }
}

class ModelWithId extends Model
{
    protected $table = 'model_with_ids';

    protected $fillable = ['id'];
}