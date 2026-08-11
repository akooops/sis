{{--
    Collapsible card used by the download listings (forms, calendars,
    newsletters, guidelines). Open by default; the toggle is keyboard-operable
    and reports its state through aria-expanded.
--}}
@props(['id', 'title', 'open' => true])

<div class="card">
    <div class="card-header">
        <button type="button"
            class="panel-toggle"
            data-toggle="collapse"
            data-target="#{{ $id }}"
            aria-expanded="{{ $open ? 'true' : 'false' }}"
            aria-controls="{{ $id }}">
            {{ $title }}
        </button>
    </div>

    <div id="{{ $id }}" class="collapse-panel {{ $open ? 'is-open' : '' }}" aria-hidden="{{ $open ? 'false' : 'true' }}">
        <div>
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
