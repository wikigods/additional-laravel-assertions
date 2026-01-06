<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class ClassUsesTraitTest extends TestCase
{
    /** @test */
    public function it_asserts_class_uses_trait()
    {
        $this->assertClassUsesTrait(SampleTrait::class, ClassWithTrait::class);
    }

    /** @test */
    public function it_fails_if_class_does_not_use_trait()
    {
        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage(
            "The class ClassWithoutTrait does not use SampleTrait."
        );

        $this->assertClassUsesTrait(SampleTrait::class, ClassWithoutTrait::class);
    }
}

trait SampleTrait {}
class ClassWithTrait { use SampleTrait; }
class ClassWithoutTrait {}