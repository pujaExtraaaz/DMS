<x-tally::layouts.app :title="$page['label']">
    <x-tally::shell.page :title="$page['label']" :section="$page['section']">
        <section class="panel">
            <h2>Not available yet</h2>
            <p>{{ $page['label'] }} is reserved for a later module. The screen is wired into the menu so it can be replaced without changing the shell.</p>
            <dl class="kv">
                <dt>Area</dt>
                <dd>{{ config('domains.'.$page['domain'], $page['domain']) }}</dd>
                <dt>Route</dt>
                <dd>{{ $page['route'] }}</dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
