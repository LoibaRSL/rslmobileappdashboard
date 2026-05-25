@php
    $avatarUser = $user ?? auth()->user();
    $avatarSize = $size ?? 32;
    $avatarClasses = $class ?? '';
    $avatarName = $avatarUser?->name ?? 'User';
    $avatarUrl = $avatarUser?->avatar_url;
    $avatarInitials = $avatarUser?->initials ?? 'U';
@endphp

@if($avatarUrl)
    <img
        alt="{{ $avatarName }} avatar"
        class="rounded-circle user-avatar {{ $avatarClasses }}"
        src="{{ $avatarUrl }}"
        style="width: {{ $avatarSize }}px; height: {{ $avatarSize }}px; object-fit: cover;"
    />
@else
    <span
        aria-label="{{ $avatarName }} avatar"
        class="rounded-circle user-avatar-initials bg-primary-subtle text-primary fw-bold d-inline-flex align-items-center justify-content-center {{ $avatarClasses }}"
        style="width: {{ $avatarSize }}px; height: {{ $avatarSize }}px; font-size: {{ max(11, round($avatarSize * 0.38)) }}px;"
    >
        {{ $avatarInitials }}
    </span>
@endif
