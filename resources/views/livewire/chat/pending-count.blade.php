<div>

{{-- resources/views/livewire/chat/pending-count.blade.php --}}
{{-- Renders just the badge span. Only shown when count > 0. --}}
@if($count > 0)
    <span class="nav-request-badge">{{ $count > 99 ? '99+' : $count }}</span>
@endif

</div>