<div>

@if($count > 0)
    <span class="nav-request-badge" aria-label="{{ $count }} pending requests">
        {{ $count > 9 ? '9+' : $count }}
    </span>
@endif

</div>