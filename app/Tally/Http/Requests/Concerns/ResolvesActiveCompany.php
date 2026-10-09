<?php

namespace Tally\Http\Requests\Concerns;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;

trait ResolvesActiveCompany
{
    public function company(): ?Company
    {
        $routed = $this->route('company');

        if ($routed instanceof Company) {
            return $routed->is_active ? $routed : null;
        }

        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }
}
