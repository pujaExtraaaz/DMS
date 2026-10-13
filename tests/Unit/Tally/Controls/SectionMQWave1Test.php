<?php

namespace Tests\Unit\Tally\Controls;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Controls\ApprovalService;
use Tally\Controls\CloseChecklistService;

class SectionMQWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_close_checklist_creates_default_tasks(): void
    {
        $company = $this->makeCompany();

        $checklist = app(CloseChecklistService::class)->createMonthly($company, [
            'period_label' => '2025-04',
            'kind' => 'month',
        ]);

        $this->assertCount(count(CloseChecklistService::DEFAULT_TASKS), $checklist->tasks);
        $this->assertTrue($checklist->tasks->every(fn ($task) => $task->status === 'pending'));
        $this->assertSame('bank_recon', $checklist->tasks->first()->task_key);
        $this->assertSame('period_lock', $checklist->tasks->last()->task_key);
    }

    public function test_approval_request_is_pending(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();

        $request = app(ApprovalService::class)->request($company, $user, [
            'document_type' => 'voucher',
            'document_id' => 99,
            'amount' => '5000.00',
        ]);

        $this->assertSame('pending', $request->status);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertSame('voucher', $request->document_type);
        $this->assertSame(99, (int) $request->document_id);
    }
}
