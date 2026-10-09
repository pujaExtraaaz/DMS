@props(['title', 'message' => null])
<div class="empty">
    <h2>{{ $title }}</h2>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="empty-actions">{{ $slot }}</div>
    @endif
</div>
