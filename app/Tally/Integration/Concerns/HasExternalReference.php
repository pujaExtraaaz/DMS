<?php

namespace Tally\Integration\Concerns;

use Tally\Models\IntegrationReference;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasExternalReference
{
    public function integrationReference(): MorphOne
    {
        return $this->morphOne(IntegrationReference::class, 'referenceable');
    }
}
