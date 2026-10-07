@props([
    'user',
    'class'  => 'conv-avatar',
    'size'   => '42px',
    'imgClass' => '',
])

@php
    $initial  = strtoupper(substr($user->name ?? '?', 0, 1));
    $userId   = $user->id ?? '';
    $imgUrl   = $user->profile_image ? \Illuminate\Support\Facades\Storage::url($user->profile_image) : null;

    // Consistent onerror: hide img, show sibling initials span
    $onerror  = "this.onerror=null;this.style.display='none';var s=this.nextElementSibling;if(s)s.style.display='flex';";
@endphp

@if($imgUrl)
    <img src="{{ $imgUrl }}"
         alt="{{ $user->name }}"
         class="{{ $imgClass ?: $class }}"
         data-user-id="{{ $userId }}"
         data-initials="{{ $initial }}"
         style="width:{{ $size }};height:{{ $size }};border-radius:50%;object-fit:cover;display:block;"
         onerror="{{ $onerror }}">
    {{-- Fallback initials — hidden unless img fails --}}
    <span style="display:none;width:{{ $size }};height:{{ $size }};border-radius:50%;background:linear-gradient(135deg,#6c47ff,#5e3de8);color:#fff;font-weight:700;font-size:calc({{ $size }} * 0.4);align-items:center;justify-content:center;flex-shrink:0;"
          data-user-id="{{ $userId }}">{{ $initial }}</span>
@else
    <span style="display:flex;width:{{ $size }};height:{{ $size }};border-radius:50%;background:linear-gradient(135deg,#6c47ff,#5e3de8);color:#fff;font-weight:700;font-size:calc({{ $size }} * 0.4);align-items:center;justify-content:center;flex-shrink:0;"
          data-user-id="{{ $userId }}">{{ $initial }}</span>
@endif
