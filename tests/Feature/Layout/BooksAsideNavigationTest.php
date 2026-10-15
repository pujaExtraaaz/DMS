<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Support\BooksWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BooksAsideNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'name' => 'Books Nav Admin',
            'email' => 'books-nav@dms.test',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_books_dashboard_shows_monochrome_books_aside(): void
    {
        $user = $this->actingSuperAdmin();

        $response = $this->actingAs($user)->get(route('books.tally.dashboard'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('books-aside', $content);
        $this->assertStringContainsString('Search in Accounting', $content);
        $this->assertStringContainsString('Company &amp; Foundation', $content);
        $this->assertStringContainsString('Sales &amp; Receivables', $content);
        $this->assertStringNotContainsString('books-aq-nav__letter', $content);
        $this->assertStringNotContainsString('Create Unit', $content);
        $this->assertStringNotContainsString('Create Godown', $content);
        $this->assertTrue(session(BooksWorkspace::SESSION_KEY));
    }

    public function test_reused_dms_page_keeps_books_aside_while_workspace_active(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get(route('books.tally.dashboard'))->assertStatus(200);
        $this->assertTrue(session(BooksWorkspace::SESSION_KEY));

        $response = $this->actingAs($user)->get(route('masters.customers.index'));
        $response->assertStatus(200);
        $this->assertStringContainsString('books-aside', $response->getContent());
        $this->assertTrue(session(BooksWorkspace::SESSION_KEY));
    }

    public function test_dashboard_clears_books_workspace_and_hides_aside(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get(route('books.tally.dashboard'))->assertStatus(200);
        $this->assertTrue(session(BooksWorkspace::SESSION_KEY));

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $this->assertFalse((bool) session(BooksWorkspace::SESSION_KEY));
        $this->assertStringNotContainsString('class="books-aside', $response->getContent());
    }

    public function test_books_sidebar_config_routes_all_exist(): void
    {
        $config = config('books_sidebar');
        $missing = [];

        $items = $config['gateway'] ?? [];
        foreach ($config['categories'] ?? [] as $category) {
            foreach ($category['items'] ?? [] as $item) {
                $items[] = $item;
            }
        }

        foreach ($items as $item) {
            $route = $item['route'] ?? null;
            if ($route && ! Route::has($route)) {
                $missing[] = $route.' ('.$item['label'].')';
            }
        }

        $this->assertSame([], $missing, 'Missing routes in books_sidebar config: '.implode(', ', $missing));
    }
}
