@props(['shortcuts'])
<div id="shortcut-dialog" class="dialog" hidden>
    <div class="dialog-card dialog-wide" role="dialog" aria-modal="true" aria-labelledby="shortcut-title">
        <header class="dialog-head">
            <h2 id="shortcut-title">Keyboard shortcuts</h2>
            <button class="icon-btn" type="button" data-close-dialog>Close</button>
        </header>
        <p class="muted"><a href="{{ tally_route('books.tally.settings.shortcuts') }}">Change shortcuts</a></p>
        @foreach (collect($shortcuts)->filter(fn ($shortcut) => ($shortcut['enabled'] ?? true) === true)->groupBy('group') as $group => $items)
            <section class="shortcut-group">
                <h3>{{ $group }}</h3>
                <dl>
                    @foreach ($items as $shortcut)
                        <div>
                            <dt>{{ $shortcut['label'] }}</dt>
                            <dd><kbd>{{ $shortcut['keys'] }}</kbd></dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach
    </div>
</div>
