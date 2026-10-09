@props([
    'view' => null,
    'edit' => null,
    'active' => null,
    'toggle' => null,
    'delete' => null,
    'deleteReason' => null,
    'lifecycle' => false,
    'confirm' => 'Delete this record? This cannot be undone.',
])
<span class="row-actions">
    @if ($view)
        <a href="{{ $view }}">View</a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}">Edit</a>
    @endif
    @if ($lifecycle && $toggle !== null && $active !== null)
        <form method="POST" action="{{ $toggle }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="is_active" value="{{ $active ? '0' : '1' }}">
            <button type="submit" data-lifecycle="toggle">{{ $active ? 'Deactivate' : 'Activate' }}</button>
        </form>
    @endif
    @if ($delete)
        <form method="POST" action="{{ $delete }}" onsubmit="return confirm(@js($confirm));">
            @csrf
            @method('DELETE')
            <button type="submit">Delete</button>
        </form>
    @endif
</span>
