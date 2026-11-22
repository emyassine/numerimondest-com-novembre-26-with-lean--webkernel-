@props(['notifications' => [], 'alignment' => 'end', 'vertical_alignment' => 'top'])
<div class="fi-no fi-align-{{ $alignment }} fi-vertical-align-{{ $vertical_alignment }}" role="status" aria-atomic="false" {{ $attributes }}>
    @foreach ($notifications as $notification)
        <x-webkernel-platform-notifications::notification
            :title="$notification['title'] ?? null"
            :body="$notification['body'] ?? null"
            :status="$notification['status'] ?? 'info'"
            :duration="$notification['duration'] ?? 5000"
        />
    @endforeach
</div>
