<?php

namespace Tests\Feature\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        foreach (['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['hrms.view', 'hrms.create', 'hrms.edit', 'hrms.manage']);
        return $user;
    }

    public function test_authorized_user_can_upload_employee_document(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-301', 'name' => 'Deepak Jain', 'status' => 'active']);

        $file = UploadedFile::fake()->create('aadhaar_card.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('hrms.employees.documents.store', $employee), [
            'document_type' => 'Aadhaar Card',
            'document_number' => '1234-5678-9012',
            'document_file' => $file,
        ]);

        $response->assertRedirect(route('hrms.employees.show', $employee));

        $doc = EmployeeDocument::first();
        $this->assertNotNull($doc);
        $this->assertEquals('Aadhaar Card', $doc->document_type);
        $this->assertEquals('1234-5678-9012', $doc->document_number);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_document_download_and_deletion(): void
    {
        $admin = $this->makeUser();
        $employee = Employee::create(['employee_code' => 'EMP-302', 'name' => 'Sara Khan', 'status' => 'active']);

        $path = 'employee_documents/' . $employee->id . '/test.pdf';
        Storage::disk('local')->put($path, 'dummy document content');

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'PAN Card',
            'document_number' => 'ABCDE1234F',
            'file_path' => $path,
        ]);

        // Download
        $downloadResponse = $this->actingAs($admin)->get(route('hrms.documents.download', $doc));
        $downloadResponse->assertOk();

        // Delete
        $deleteResponse = $this->actingAs($admin)->delete(route('hrms.documents.destroy', $doc));
        $deleteResponse->assertRedirect(route('hrms.employees.show', $employee));

        $this->assertDatabaseMissing('employee_documents', ['id' => $doc->id]);
        Storage::disk('local')->assertMissing($path);
    }
}

