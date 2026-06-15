{{-- Tick partial: $status = 'sent' | 'delivered' | 'read' --}}
@if($status === 'read')
  {{-- Double blue tick --}}
  <svg class="tick tick--read" width="16" height="11" viewBox="0 0 16 11" fill="none" aria-label="Read">
    <path d="M1 5.5L4.5 9 10 3" stroke="#4fc3f7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M5 5.5L8.5 9 14 3" stroke="#4fc3f7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@elseif($status === 'delivered')
  {{-- Double grey tick --}}
  <svg class="tick tick--delivered" width="16" height="11" viewBox="0 0 16 11" fill="none" aria-label="Delivered">
    <path d="M1 5.5L4.5 9 10 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M5 5.5L8.5 9 14 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@else
  {{-- Single grey tick --}}
  <svg class="tick tick--sent" width="10" height="11" viewBox="0 0 10 11" fill="none" aria-label="Sent">
    <path d="M1 5.5L4.5 9 9 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
@endif
