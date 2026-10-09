<?php

namespace Tally\Accounting\Events;

use Tally\Models\Voucher;
use Illuminate\Foundation\Events\Dispatchable;

class VoucherWritten
{
    use Dispatchable;

    public function __construct(public Voucher $voucher) {}
}
