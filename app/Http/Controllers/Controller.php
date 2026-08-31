<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesMessages;

abstract class Controller
{
    use FlashesMessages;
}
