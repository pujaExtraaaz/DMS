<?php

namespace Tally\Http\Requests;

class PartyLedgerRequest extends StoreLedgerRequest
{
    protected function prepareForValidation(): void
    {
        $company = $this->company();
        $role = (string) $this->input('role');

        if ($company && in_array($role, ['customer', 'supplier'], true) && ! $this->filled('account_group_id')) {
            $code = $role === 'customer' ? 'DEBTORS' : 'CREDITORS';
            $group = $company->accountGroups()->where('code', $code)->first();

            if ($group) {
                $this->merge(['account_group_id' => $group->id]);
            }
        }

        parent::prepareForValidation();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:customer,supplier'],
            ...parent::rules(),
        ];
    }
}
