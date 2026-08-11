{{-- Session flash messages. Auto-dismissed by site/disclosure.js. --}}
@foreach (['success' => 'uil-check-circle', 'error' => 'uil-exclamation-triangle'] as $key => $icon)
    @if (session($key))
        <div class="alert alert-{{ $key === 'error' ? 'danger' : 'success' }} mb-4 flex items-center gap-2"
            role="alert" data-auto-dismiss>
            <i class="uil {{ $icon }}" aria-hidden="true"></i>
            <span class="flex-1">{{ session($key) }}</span>
            <button type="button" class="btn-close shrink-0" data-dismiss-alert
                aria-label="@lang('common.close')"></button>
        </div>
    @endif
@endforeach
