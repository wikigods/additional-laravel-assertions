<?php

namespace WikiGods\AdditionalTestAssertions\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use WikiGods\AdditionalTestAssertions\Traits\AdditionalTestAssertions;


abstract class TestCase extends BaseTestCase
{
    use AdditionalTestAssertions;
}