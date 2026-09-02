<?php

namespace App\View\Components;

use App\Abstracts\View\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Vite;

class Script extends Component
{
    /** @var string */
    public $alias;

    /** @var string */
    public $folder;

    /** @var string */
    public $file;

    /** @var string|\Illuminate\Contracts\Support\Htmlable */
    public $source;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $alias = 'core', string $folder = '', string $file = ''
    ) {
        $this->alias = $alias;
        $this->folder = $folder;
        $this->file = $file;

        $this->source = $this->getSource($alias, $folder, $file);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.script');
    }

    protected function getSource($alias, $folder, $file)
    {
        if ($alias != 'core') {
            $path = 'public/js/';
            $version = version('short');

            try {
                $module = module($alias);

                if ($module) {
                    $path = 'modules/' . $module->getStudlyName() . '/Resources/assets/js/';
                    $version = module_version($alias);
                }
            } catch (\Exception $e) {

            }

            if (! empty($folder)) {
                $path .= $folder . '/';
            }

            $path .= $file . '.min.js?v=' . $version;

            return asset($path);
        }

        return static::coreSource($folder, $file);
    }

    /**
     * Resolve a core (non-module) JS entry's <script>/<link> tags via the
     * Vite manifest — including any CSS the entry (or a chunk it imports,
     * e.g. the shared `global` mixin) pulls in via a plain `import '*.css'`.
     * A bare Vite::asset() URL isn't enough here: transitive CSS is only
     * discoverable by walking the manifest's import graph, which is exactly
     * what the Vite instance's __invoke() (the same call @vite() makes)
     * already does — reimplementing that walk here would just duplicate it.
     *
     * @param  string  $folder
     * @param  string  $file
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public static function coreSource(string $folder, string $file): Htmlable
    {
        $key = $folder !== '' ? "$folder/$file" : $file;

        $entries = json_decode(file_get_contents(base_path('vite-entries.json')), true);

        if (! isset($entries[$key])) {
            throw new \RuntimeException("No Vite entry mapped for \"$key\" in vite-entries.json.");
        }

        return app(Vite::class)([$entries[$key]]);
    }
}
