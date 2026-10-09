<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\TallyBooksServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
];
