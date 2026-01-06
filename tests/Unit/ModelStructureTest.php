<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;
use WikiGods\AdditionalTestAssertions\Traits\AdditionalTestAssertions;

class ModelStructureTest extends TestCase
{
    use AdditionalTestAssertions;

    /** @test */
    public function it_asserts_casts_correctly()
    {
        $model = new class extends Model {
            protected $casts = ['is_admin' => 'boolean', 'options' => 'array'];
        };

        $this->assertCasts($model, ['is_admin' => 'boolean']);

        // Test failure scenario
        try {
            $this->assertCasts($model, ['missing_field' => 'integer']);
            $this->fail('Should have failed due to missing cast.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('missing from the casts', $e->getMessage());
        }
    }

    /** @test */
    public function it_asserts_uuid_configuration()
    {
        $model = new class extends Model {
            public $incrementing = false;
            protected $keyType = 'string';
        };

        $this->assertIsUuid($model);

        // Test bad config
        try {
            $badModel = new class extends Model { public $incrementing = true; };
            $this->assertIsUuid($badModel);
            $this->fail('Should have failed due to incrementing true.');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('incrementing = false', $e->getMessage());
        }
    }
}