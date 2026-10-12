<?php

namespace Tests\Unit\TallySync;

use App\Domains\TallySync\Support\SyncGate;
use Tests\TestCase;

class SyncGateTest extends TestCase
{
    public function test_sync_is_disabled_by_default(): void
    {
        config(['tally_sync.enabled' => false]);
        $this->assertFalse(SyncGate::enabled());
    }

    public function test_sync_can_be_enabled_via_config(): void
    {
        config(['tally_sync.enabled' => true]);
        $this->assertTrue(SyncGate::enabled());
    }
}
