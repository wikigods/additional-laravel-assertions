<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class HasUuidTest extends TestCase
{
    /** @test */
    public function it_asserts_model_is_uuid()
    {
        $model = new ModelWithUuid(['id' => Str::uuid()->toString()]);

        $this->assertIsUuid($model);
    }

    /** @test */
    public function it_fails_if_key_is_not_valid_uuid()
    {
        $model = new ModelWithUuid(['id' => 'not-uuid']);

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('must be Uuid');

        $this->assertIsUuid($model);
    }
}

class ModelWithUuid extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
}