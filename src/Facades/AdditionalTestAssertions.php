<?php

namespace WikiGods\AdditionalTestAssertions\Facades;

use Illuminate\Support\Facades\Facade;
class AdditionalTestAssertions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'additional-test-assertions';
    }
}