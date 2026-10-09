<?php

namespace Tally\Preferences;

use Tally\Models\Company;
use Tally\Models\Preference;
use App\Models\User;

class PreferenceStore
{
    /**
     * Settings that change behaviour. Anything else is ignored.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'display.date_format' => 'd M Y',
        'display.currency_symbol' => '₹',
        'sales.enforce_credit_limit' => false,
        'inventory.repeat_scan_increments' => true,
        'print.amount_in_words' => true,
        'payroll.enabled' => false,
        'payroll.pf' => false,
        'payroll.esi' => false,
        'payroll.pf_rate' => '12',
        'payroll.esi_rate' => '0.75',
        'payroll.pf_employer_rate' => '12',
        'payroll.esi_employer_rate' => '3.25',
        'payroll.pf_ceiling' => '15000',
        'payroll.esi_ceiling' => '21000',
        'dashboard.as_on' => '',
        'dashboard.period_from' => '',
        'dashboard.period_to' => '',
    ];

    public function get(?Company $company, ?User $user, string $key): mixed
    {
        $userValue = $user
            ? Preference::query()->where('user_id', $user->id)->whereNull('company_id')->where('key', $key)->value('value')
            : null;

        if (is_array($userValue) && array_key_exists('v', $userValue)) {
            return $userValue['v'];
        }

        $companyValue = $company
            ? Preference::query()->where('company_id', $company->id)->whereNull('user_id')->where('key', $key)->value('value')
            : null;

        if (is_array($companyValue) && array_key_exists('v', $companyValue)) {
            return $companyValue['v'];
        }

        return self::DEFAULTS[$key] ?? null;
    }

    public function enabled(?Company $company, string $key): bool
    {
        return (bool) $this->get($company, null, $key);
    }

    public function put(?Company $company, ?User $user, string $key, mixed $value): void
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            return;
        }

        Preference::query()->updateOrCreate(
            [
                'company_id' => $company?->id,
                'user_id' => $user?->id,
                'key' => $key,
            ],
            ['value' => ['v' => $value]],
        );
    }

    public function dateFormat(?Company $company, ?User $user): string
    {
        $format = (string) $this->get($company, $user, 'display.date_format');

        return in_array($format, ['d M Y', 'd/m/Y', 'Y-m-d'], true) ? $format : 'd M Y';
    }
}
