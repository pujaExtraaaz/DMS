<?php

namespace Tests\Feature\Support;

use App\Console\Commands\UppercaseNamesCommand;
use App\Domains\Catalog\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UppercaseNamesCommandTest extends TestCase
{
    use RefreshDatabase;
    public function test_audit_reports_pending_uppercase_records(): void
    {
        // Insert a record directly via DB to bypass model mutators
        $id = DB::table('brands')->insertGetId([
            'name' => 'test brand lowercase',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('dms:uppercase-names', ['--audit' => true])
            ->expectsOutputToContain('brands')
            ->expectsOutputToContain('Pending Updates')
            ->assertExitCode(0);

        // Clean up
        DB::table('brands')->where('id', $id)->delete();
    }

    public function test_apply_updates_database_records_in_place(): void
    {
        $id = DB::table('brands')->insertGetId([
            'name' => 'sony pro display',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('dms:uppercase-names', ['--apply' => true])
            ->expectsOutputToContain('Completed! Total records updated in place')
            ->assertExitCode(0);

        $updatedValue = DB::table('brands')->where('id', $id)->value('name');
        $this->assertEquals(\App\Support\TitleCase::format('sony pro display'), $updatedValue);

        // Clean up
        DB::table('brands')->where('id', $id)->delete();
    }
}
