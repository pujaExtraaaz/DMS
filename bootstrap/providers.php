<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\TallyBooksServiceProvider::class,
    App\Domains\TallySync\TallySyncServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
];
