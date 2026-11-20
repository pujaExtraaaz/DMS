<?php

namespace App\Domains\Hrms\Services\Essl;

use Carbon\Carbon;

interface EsslAttendanceDriver
{
    /**
     * @return list<array{biometric_code:string,punched_at:string,direction?:string,device_id?:string,external_id?:string}>
     */
    public function pullPunches(Carbon $from, Carbon $to): array;
}
