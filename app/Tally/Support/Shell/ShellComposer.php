<?php

namespace Tally\Support\Shell;

use Tally\Keyboard\ShortcutRegistry;
use Illuminate\View\View;

class ShellComposer
{
    public function __construct(
        private readonly Navigation $navigation,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly ShortcutRegistry $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'navigation' => $this->navigation->sections(),
            'shellNav' => $this->navigation,
            'breadcrumbs' => $this->breadcrumbs->items(),
            'shortcuts' => $this->registry->resolved(),
            'searchIndex' => $this->navigation->searchIndex(),
        ];
    }

    public function compose(View $view): void
    {
        $view->with($this->data());
    }
}
