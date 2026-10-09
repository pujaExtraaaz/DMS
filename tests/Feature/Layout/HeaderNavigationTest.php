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

        // 5. Ensure numbered sections match specification
        $this->assertStringNotContainsString('OD Limit &amp; Interest', $sidebarContent);
        $this->assertStringContainsString('8 · Commercials', $sidebarContent);
        $this->assertStringContainsString('9 · HR · CRM · TALLY', $sidebarContent);
        $this->assertStringContainsString('10 · REPORTS', $sidebarContent);
        $this->assertStringNotContainsString('Operations & HR', $sidebarContent);
        $this->assertStringNotContainsString('Intelligence & Reports', $sidebarContent);

        // 6. Ensure user actions section is placed after 10 · REPORTS in the document flow inside <nav>
        $posReports = strpos($sidebarContent, '10 · REPORTS');
        $posProfile = strpos($sidebarContent, $user->name);
        $posUsers = strpos($sidebarContent, 'Users &amp; Roles');
        $posLogout = strpos($sidebarContent, 'Log Out');

        $this->assertNotFalse($posReports, 'Sidebar must contain 10 · REPORTS');
        $this->assertNotFalse($posProfile, 'Sidebar must contain user profile');
        $this->assertNotFalse($posUsers, 'Sidebar must contain Users & Roles');
        $this->assertNotFalse($posLogout, 'Sidebar must contain Log Out');

        $this->assertTrue($posReports < $posProfile, '10 · REPORTS must appear before user profile in sidebar');
        $this->assertTrue($posProfile < $posUsers, 'User profile must appear before Users & Roles in sidebar');
        $this->assertTrue($posUsers < $posLogout, 'Users & Roles must appear before Log Out in sidebar');

        // Ensure user actions are inside <nav>
        preg_match('/<nav[\s\S]*?<\/nav>/i', $sidebarContent, $navMatch);
        $navContent = $navMatch[0] ?? '';
        $this->assertStringContainsString($user->name, $navContent, 'User profile must be inside <nav>');
        $this->assertStringContainsString('Users &amp; Roles', $navContent, 'Users & Roles must be inside <nav>');
        $this->assertStringContainsString('Log Out', $navContent, 'Log Out must be inside <nav>');
    }
}
