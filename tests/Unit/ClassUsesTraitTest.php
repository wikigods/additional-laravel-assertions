<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
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
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('must use');

        $this->assertClassUsesTrait(SampleTrait::class, ClassWithoutTrait::class);
    }
}

trait SampleTrait {}
class ClassWithTrait { use SampleTrait; }
class ClassWithoutTrait {}