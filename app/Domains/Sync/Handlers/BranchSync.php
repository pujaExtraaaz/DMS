<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Organization\Models\Branch;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Branch as BooksBranch;

class BranchSync
{
    public const KEY = 'branch';

    public function sync(Model $model): void
    {
        if ($model instanceof Branch) {
            $this->push($model);
        }
    }

    public function push(Branch $branch): void
    {
        if (! $branch->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $branch->company_id);
        if (! $company) {
            return;
        }

        $books = BooksBranch::query()->find(SyncLinks::acctId(self::KEY, $branch));
        $code = SyncNames::unique(
            BooksBranch::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($branch->code ?: ('B'.$branch->id), 30),
            $books?->id,
            30
        );
        $name = SyncNames::unique(
            BooksBranch::query()->where('company_id', $company->id),
            'name',
            $branch->name,
            $books?->id
        );

        $attributes = [
            'company_id' => $company->id,
            'name' => $name,
            'code' => $code,
            'address' => $branch->address,
            'state' => $branch->state,
            'country' => 'India',
            'pincode' => $branch->pincode,
            'phone' => $branch->phone,
            'is_active' => (bool) $branch->is_active,
        ];

        $books ? $books->update($attributes) : $books = BooksBranch::query()->create($attributes);
        SyncLinks::store(self::KEY, $branch, $books);
        BooksCompany::putBranchMap((int) $branch->company_id, (int) $branch->id, (int) $books->id);
    }

    public function remove(Model $source): void
    {
        if (! $source instanceof Branch) {
            return;
        }

        $booksId = SyncLinks::acctId(self::KEY, $source);
        if ($booksId) {
            BooksBranch::query()->whereKey($booksId)->update(['is_active' => false]);
        }

        SyncLinks::forget(self::KEY, $source);
    }
}
