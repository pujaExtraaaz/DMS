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

        preg_match('/<header[\s\S]*?<\/header>/i', $content, $headerMatch);
        $headerContent = $headerMatch[0] ?? '';

        $this->assertDoesNotMatchRegularExpression(
            '/Log out/i',
            $headerContent,
            'Top header must not contain a Log out button'
        );

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

        $this->assertStringContainsString('Super Admin', $headerContent, 'Header must contain role badge');
        $this->assertStringContainsString('Search...', $headerContent, 'Header must contain search input');
        $this->assertStringContainsString('Install App', $headerContent, 'Header must contain install app button');

        preg_match('/<aside[^>]*class="[^"]*dms-sidebar[^"]*"[\s\S]*?<\/aside>/i', $content, $sidebarMatch);
        $sidebarContent = $sidebarMatch[0] ?? '';

        $this->assertStringContainsString($user->name, $sidebarContent, 'Sidebar must contain user name');
        $this->assertStringContainsString($user->email, $sidebarContent, 'Sidebar must contain user email');
        $this->assertMatchesRegularExpression(
            '/action="[^"]*\/logout"[\s\S]*?Log Out/i',
            $sidebarContent,
            'Left sidebar must still contain the Log Out button'
        );

        $this->assertStringNotContainsString('OD Limit &amp; Interest', $sidebarContent);
        $sidebarText = strip_tags($sidebarContent);
        $this->assertStringContainsString('Commercials', $sidebarText);
        $this->assertStringContainsString('Sales Target Management', $sidebarText);
        $this->assertStringContainsString('HR · CRM', $sidebarText);
        $this->assertStringContainsString('Accounting (Books)', $sidebarText);
        $this->assertStringContainsString('Reports', $sidebarText);
        // Dual Books aside is not shown outside the Accounting workspace
        $this->assertStringNotContainsString('books-aside', $content);
        $this->assertStringNotContainsString('books-aq-nav', $sidebarContent);
        $this->assertStringNotContainsString('Operations & HR', $sidebarContent);
        $this->assertStringNotContainsString('Intelligence & Reports', $sidebarContent);
        $this->assertStringNotContainsString('Create Unit', $sidebarContent);
        $this->assertStringNotContainsString('Create Godown', $sidebarContent);

        $posReports = strpos($sidebarText, 'Reports');
        $posProfile = strpos($sidebarText, $user->name);
        $posUsers = strpos($sidebarText, 'Users &amp; Roles');
        $posLogout = strpos($sidebarText, 'Log Out');

        $this->assertNotFalse($posReports, 'Sidebar must contain Reports');
        $this->assertNotFalse($posProfile, 'Sidebar must contain user profile');
        $this->assertNotFalse($posUsers, 'Sidebar must contain Users & Roles');
        $this->assertNotFalse($posLogout, 'Sidebar must contain Log Out');

        $this->assertTrue($posReports < $posProfile, 'Reports must appear before user profile in sidebar');
        $this->assertTrue($posProfile < $posUsers, 'User profile must appear before Users & Roles in sidebar');
        $this->assertTrue($posUsers < $posLogout, 'Users & Roles must appear before Log Out in sidebar');

        preg_match('/<nav[\s\S]*?<\/nav>/i', $sidebarContent, $navMatch);
        $navContent = $navMatch[0] ?? '';
        $this->assertStringContainsString($user->name, $navContent, 'User profile must be inside <nav>');
        $this->assertStringContainsString('Users &amp; Roles', $navContent, 'Users & Roles must be inside <nav>');
        $this->assertStringContainsString('Log Out', $navContent, 'Log Out must be inside <nav>');
    }
}
