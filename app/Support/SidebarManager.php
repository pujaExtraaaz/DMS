<?php

namespace App\Support;

use App\Models\User;

class SidebarManager
{
    public const PREFERENCE_KEY = 'sidebar_sections_order';

    public const DEFAULT_SECTIONS = [
        'setup',
        'masters',
        'purchasing',
        'inventory',
        'sales_orders',
        'logistics',
        'receivables',
        'commercials',
        'sales_targets',
        'hr_crm',
        'accounting_books',
        'reports',
        'tally_connector',
        'guide',
    ];

    public const SECTION_LABELS = [
        'setup' => 'Setup',
        'masters' => 'Masters',
        'purchasing' => 'Purchases',
        'inventory' => 'Inventory',
        'sales_orders' => 'Sales & Orders',
        'logistics' => 'Logistics & Trips',
        'receivables' => 'Receivables',
        'commercials' => 'Commercials',
        'sales_targets' => 'Sales Target Management',
        'hr_crm' => 'HR · CRM',
        'accounting_books' => 'Accounting (Books)',
        'reports' => 'Reports',
        'tally_connector' => 'Tally Connector',
        'guide' => 'Guide',
    ];

    /**
     * Get the ordered section keys for the given user, falling back to defaults.
     *
     * @return list<string>
     */
    public static function getOrderedSections(?User $user): array
    {
        $default = self::DEFAULT_SECTIONS;

        if (! $user) {
            return $default;
        }

        $saved = $user->getPreference(self::PREFERENCE_KEY);

        if (! is_array($saved) || empty($saved)) {
            return $default;
        }

        // Keep only valid known sections from user preference (unique, preserving order)
        $validSaved = [];
        foreach ($saved as $key) {
            if (is_string($key) && in_array($key, $default, true) && ! in_array($key, $validSaved, true)) {
                $validSaved[] = $key;
            }
        }

        if (empty($validSaved)) {
            return $default;
        }

        // Append any newly added / missing default sections at the end
        $missing = array_diff($default, $validSaved);

        return array_values(array_merge($validSaved, $missing));
    }

    /**
     * Save the user's custom section order after strict validation.
     *
     * @param  list<string>  $newOrder
     * @return list<string>
     */
    public static function saveUserOrder(User $user, array $newOrder): array
    {
        $default = self::DEFAULT_SECTIONS;

        $sanitized = [];
        foreach ($newOrder as $key) {
            if (is_string($key) && in_array($key, $default, true) && ! in_array($key, $sanitized, true)) {
                $sanitized[] = $key;
            }
        }

        // If user submitted an incomplete list, preserve the missing sections at the end
        $missing = array_diff($default, $sanitized);
        $finalOrder = array_values(array_merge($sanitized, $missing));

        $user->setPreference(self::PREFERENCE_KEY, $finalOrder);

        return $finalOrder;
    }

    /**
     * Reset user's custom section order back to default.
     */
    public static function resetUserOrder(User $user): void
    {
        $user->preferences()->where('key', self::PREFERENCE_KEY)->delete();
    }
}
