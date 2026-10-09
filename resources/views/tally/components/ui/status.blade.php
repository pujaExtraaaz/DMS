@props(['active'])
<span @class(['status', 'is-on' => $active, 'is-off' => ! $active])>{{ $active ? 'Active' : 'Inactive' }}</span>
