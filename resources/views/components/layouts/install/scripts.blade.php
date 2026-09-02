@stack('scripts_start')

{!! \App\View\Components\Script::coreSource('', 'install') !!}

@stack('body_css')

@stack('body_stylesheet')

@stack('body_js')

@stack('body_scripts')

@stack('scripts_end')
