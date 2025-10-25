<?php

namespace WikiGods\AdditionalTestAssertions;

use Illuminate\Support\ServiceProvider;
use WikiGods\AdditionalTestAssertions\Facades\AdditionalTestAssertions;

class AdditionalTestAssertionsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register(): void
    {
        $this->app->bind('additional-test-assertions', function () {
            return new AdditionalTestAssertions;
        });
    }
}