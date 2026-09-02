@push('scripts_start')
    @if ($alias == 'core')
        {!! $source !!}
    @else
        <script src="{{ $source }}"></script>
    @endif
@endpush
