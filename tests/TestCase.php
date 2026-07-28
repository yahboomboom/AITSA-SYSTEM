<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // App\Models\Setting memoizes DB lookups in a static property for the lifetime of the
        // PHP process to avoid repeated queries within a single request. RefreshDatabase resets
        // the database between tests but not PHP statics, so without this reset a value seeded/put
        // by one test can bleed into the next test's assertions via the stale in-memory cache.
        Setting::clearCache();
    }
}
