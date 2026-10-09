<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Masters" :description="$company->name">
        <form class="tally-master stack" method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <div class="form-grid">
                @foreach ($fields as $field)
                    <x-tally::form.field :name="$field['name']" :label="$field['label']" :required="empty($field['optional'])">
                        @if ($field['type'] === 'select')
                            @include('tally::masters._picker', [
                                'name' => $field['name'],
                                'options' => $field['options'],
                                'current' => old($field['name'], $record->{$field['name']} ?? ''),
                                'optional' => ! empty($field['optional']),
                                'placeholder' => $field['label'],
                                'create' => $field['create'] ?? null,
                            ])
                        @elseif ($field['type'] === 'check')
                            <label class="check"><input type="checkbox" name="{{ $field['name'] }}" value="1" @checked(old($field['name'], $record->{$field['name']} ?? false))> {{ $field['label'] }}</label>
                        @else
                            <input class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}" value="{{ old($field['name'], $record->{$field['name']} instanceof \DateTimeInterface ? $record->{$field['name']}->toDateString() : ($record->{$field['name']} ?? '')) }}" @required(empty($field['optional']))>
                        @endif
                    </x-form.field>
                @endforeach
            </div>
            @if ($errors->any())
                <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
    </x-shell.page>
</x-layouts.app>
