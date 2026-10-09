<?php

namespace Tally\Exceptions;

use RuntimeException;

class SuperAdminAlreadyExists extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A Super Admin account already exists.');
    }
}
