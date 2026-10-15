<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Keeps Avit Digital tenant users separate from external users.
 * External emails listed here must NOT see Avit seeded company data.
 */
class UnlinkExternalUsersSeeder extends Seeder
{
    /** @var list<string> */
    protected array $externalEmails = [
        'rohit@rgddental.com',
    ];

    public function run(): void
    {
        foreach ($this->externalEmails as $email) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->command?->warn("External user not found: {$email}");

                continue;
            }

            $user->forceFill([
                'company_id' => null,
                'branch_id' => null,
            ])->save();

            if (\Illuminate\Support\Facades\Schema::hasTable('user_branch_access')) {
                DB::table('user_branch_access')->where('user_id', $user->id)->delete();
            }

            $this->command?->info("Unlinked {$email} from company/branch (credentials unchanged).");
        }
    }
}
