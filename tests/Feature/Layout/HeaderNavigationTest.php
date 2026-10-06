<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_header_does_not_contain_logout_or_user_name_email_and_sidebar_contains_them(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'name' => 'Test SuperAdmin',
            'email' => 'superadmin@dms.test',
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('organization.company-profile'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Extract <header> content
        preg_match('/<header[\s\S]*?<\/header>/i', $content, $headerMatch);
        $headerContent = $headerMatch[0] ?? '';

        // 1. Ensure the header does not contain "Log out" button
        $this->assertDoesNotMatchRegularExpression(
            '/Log out/i',
            $headerContent,
            'Top header must not contain a Log out button'
        );

        // 2. Ensure the top header does not contain user name or email
        $this->assertStringNotContainsString(
            $user->name,
            $headerContent,
            'Top header must not contain the user name'
        );
        $this->assertStringNotContainsString(
            $user->email,
            $headerContent,
            'Top header must not contain the user email'
        );

        // 3. Ensure the top header preserves role badge, search, and install app
        $this->assertStringContainsString('Super Admin', $headerContent, 'Header must contain role badge');
        $this->assertStringContainsString('Search...', $headerContent, 'Header must contain search input');
        $this->assertStringContainsString('Install App', $headerContent, 'Header must contain install app button');

        // Extract <aside class="dms-sidebar"...> content
        preg_match('/<aside[\s\S]*?<\/aside>/i', $content, $sidebarMatch);
        $sidebarContent = $sidebarMatch[0] ?? '';

        // 4. Ensure sidebar contains the user name, email, and pinned Log Out button
        $this->assertStringContainsString($user->name, $sidebarContent, 'Sidebar must contain user name');
        $this->assertStringContainsString($user->email, $sidebarContent, 'Sidebar must contain user email');
        $this->assertMatchesRegularExpression(
            '/action="[^"]*\/logout"[\s\S]*?Log Out/i',
            $sidebarContent,
            'Left sidebar must still contain the Log Out button'
        );

        // 5. Ensure Option 9 and Option 10 labels match new specification
        $this->assertStringContainsString('9 · HR · CRM · TALLY', $sidebarContent);
        $this->assertStringContainsString('10 · REPORTS', $sidebarContent);
        $this->assertStringNotContainsString('Operations & HR', $sidebarContent);
        $this->assertStringNotContainsString('Intelligence & Reports', $sidebarContent);
    }
}
