<?php

namespace Tally\Http\Requests;

class UpdateAccountGroupRequest extends StoreAccountGroupRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->group()?->is_system) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);

            return;
        }

        parent::prepareForValidation();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->group()?->is_system) {
            return [
                'is_active' => ['required', 'boolean'],
            ];
        }

        return parent::rules();
    }
}
