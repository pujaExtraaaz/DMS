<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyProfileLogoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Corporation',
            'code' => 'ACM001',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_company_profile_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('organization.company-profile'));

        $response->assertOk();
        $response->assertSee('Company Logo');
        $response->assertSee('PNG, JPG, SVG or WEBP up to 2MB');
    }

    public function test_can_upload_png_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('organization.company-profile'));

        $this->company->refresh();
        $this->assertNotNull($this->company->logo_path);
        Storage::disk('public')->assertExists($this->company->logo_path);
        $this->assertNotNull($this->company->logo_url);
    }

    public function test_can_upload_jpg_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.jpg', 300, 300);

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNotNull($this->company->logo_path);
        Storage::disk('public')->assertExists($this->company->logo_path);
    }

    public function test_can_upload_webp_logo(): void
    {
        $file = UploadedFile::fake()->create('logo.webp', 100, 'image/webp');

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNotNull($this->company->logo_path);
        Storage::disk('public')->assertExists($this->company->logo_path);
    }

    public function test_can_upload_svg_logo(): void
    {
        $file = UploadedFile::fake()->create('logo.svg', 50, 'image/svg+xml');

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNotNull($this->company->logo_path);
        Storage::disk('public')->assertExists($this->company->logo_path);
    }

    public function test_rejects_logo_exceeding_2mb(): void
    {
        $file = UploadedFile::fake()->create('large_logo.png', 2500, 'image/png'); // 2.5 MB

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasErrors(['logo']);

        $this->company->refresh();
        $this->assertNull($this->company->logo_path);
    }

    public function test_rejects_non_image_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $response->assertSessionHasErrors(['logo']);

        $this->company->refresh();
        $this->assertNull($this->company->logo_path);
    }

    public function test_updating_other_fields_preserves_existing_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png');
        $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $this->company->refresh();
        $initialLogoPath = $this->company->logo_path;
        $this->assertNotNull($initialLogoPath);

        // Update other fields without uploading new logo
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation Updated',
                'email' => 'info@acme.com',
                'phone' => '9876543210',
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertSame('Acme Corporation Updated', $this->company->name);
        $this->assertSame($initialLogoPath, $this->company->logo_path);
        Storage::disk('public')->assertExists($initialLogoPath);
    }

    public function test_uploading_new_logo_replaces_and_deletes_old_logo(): void
    {
        $oldFile = UploadedFile::fake()->image('old_logo.png');
        $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $oldFile,
            ]);

        $this->company->refresh();
        $oldLogoPath = $this->company->logo_path;
        Storage::disk('public')->assertExists($oldLogoPath);

        // Upload new logo
        $newFile = UploadedFile::fake()->image('new_logo.png');
        $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $newFile,
            ]);

        $this->company->refresh();
        $newLogoPath = $this->company->logo_path;
        $this->assertNotSame($oldLogoPath, $newLogoPath);
        Storage::disk('public')->assertMissing($oldLogoPath);
        Storage::disk('public')->assertExists($newLogoPath);
    }

    public function test_can_remove_existing_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.png');
        $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'logo' => $file,
            ]);

        $this->company->refresh();
        $logoPath = $this->company->logo_path;
        Storage::disk('public')->assertExists($logoPath);

        // Remove logo
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'remove_logo' => '1',
            ]);

        $response->assertSessionHasNoErrors();

        $this->company->refresh();
        $this->assertNull($this->company->logo_path);
        $this->assertNull($this->company->logo_url);
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_logo_url_returns_null_when_file_does_not_exist_on_disk(): void
    {
        $this->company->update(['logo_path' => 'companies/logos/missing-file.png']);

        $this->assertNull($this->company->logo_url);
        $this->assertFalse($this->company->hasLogo());
    }

    public function test_company_profile_logo_endpoint_serves_logo(): void
    {
        Storage::disk('public')->put('companies/logos/sample.png', 'fake image bytes');
        $this->company->update(['logo_path' => 'companies/logos/sample.png']);

        $response = $this->actingAs($this->user)
            ->get(route('organization.company-profile.logo'));
        $response->assertOk();

        $this->company->update(['logo_path' => null]);
        $missingResponse = $this->actingAs($this->user)
            ->get(route('organization.company-profile.logo'));
        $missingResponse->assertNotFound();
    }
}