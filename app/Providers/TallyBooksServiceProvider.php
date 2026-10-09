<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\ConfiguredTax;
use Tally\Invoicing\InventoryEffect;
use Tally\Invoicing\InvoiceStock;
use Tally\Invoicing\LedgerPartyDirectory;
use Tally\Invoicing\PartyDirectory;
use Tally\Invoicing\TaxCalculator;
use Tally\Keyboard\ShortcutRegistry;
use Tally\Support\Shell\ShellComposer;

class TallyBooksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(\Tally\Models\User::class)) {
            class_alias(\App\Models\User::class, \Tally\Models\User::class);
        }

        $this->app->scoped(WorkspaceContext::class);
        $this->app->singleton(ShortcutRegistry::class);
        $this->app->bind(PartyDirectory::class, LedgerPartyDirectory::class);
        $this->app->bind(TaxCalculator::class, ConfiguredTax::class);
        $this->app->bind(InventoryEffect::class, InvoiceStock::class);
    }

    public function boot(): void
    {
        View::addNamespace('tally', resource_path('views/tally'));
        View::composer('tally::components.layouts.app', ShellComposer::class);
    }
}
