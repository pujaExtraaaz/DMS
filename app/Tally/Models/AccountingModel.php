<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AccountingModel extends Model
{
    public function getTable(): string
    {
        $table = $this->table ?? Str::snake(Str::pluralStudly(class_basename(static::class)));

        return str_starts_with($table, 'acct_') ? $table : 'acct_'.$table;
    }
}
