<?php

namespace App\Domains\Master\Imports;

use RuntimeException;

class ImportCancelledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Import cancelled.');
    }
}
