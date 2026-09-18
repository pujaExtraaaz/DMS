<?php

return [
    /*
    | Allow stock levels to go below zero on sales / adjustments.
    | Client requirement: direct billing must proceed even when available stock is 0.
    */
    'allow_negative_stock' => env('DMS_ALLOW_NEGATIVE_STOCK', true),
];
