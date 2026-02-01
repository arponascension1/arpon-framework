<?php

namespace Arpon\View;

use Arpon\Foundation\Application;

class View
{
    protected $app;
    protected $view;
    protected $data;
    protected $namespaces = [];
    protected $path;
    protected $compiledPath;
    protected $paths = [];
    protected $compiled;
    protected static $renderLevel = 0;

    public static function resetRenderLevel()
    {
        static::$renderLevel = 0;
    }

    public function __construct(Application $app, $view, $data = [], $namespaces = [], array $paths = [], $compiled = null)
    {
        $this->app = $app;
        $this->view = $view;
        $this->data = $data;
        $this->namespaces = $namespaces;
        $this->paths = $paths;
        $this->compiled = $compiled;
        $this->path = $this->findViewPath($view);
        $this->compiledPath = $this->getCompiledPath($view);
    }

    protected function findViewPath($view)
    {
        if (strpos($view, '::') !== false) {
            list($namespace, $viewName) = explode('::', $view);
            if (isset($this->namespaces[$namespace])) {
                return $this->findNamespacedViewPath($namespace, $viewName);
            }
        }

        $paths = $this->paths ?: [$this->app->basePath('resources/views')];

        foreach ($paths as $viewsPath) {
            // Try .blade.php first, then fallback to .php
            $bladeViewPath = str_replace('.', '/', $view) . '.blade.php';
            $phpViewPath = str_replace('.', '/', $view) . '.php';
            
            $bladeFullPath = $viewsPath . '/' . $bladeViewPath;
            $phpFullPath = $viewsPath . '/' . $phpViewPath;

            if (file_exists($bladeFullPath)) {
                return $bladeFullPath;
            } elseif (file_exists($phpFullPath)) {
                return $phpFullPath;
            }
        }

        throw new \Exception("View [{$view}] not found.");
    }

    protected function findNamespacedViewPath($namespace, $view)
    {
        $viewsPath = $this->namespaces[$namespace];
        
        $bladeViewPath = str_replace('.', '/', $view) . '.blade.php';
        $phpViewPath = str_replace('.', '/', $view) . '.php';

        $bladeFullPath = $viewsPath . '/' . $bladeViewPath;
        $phpFullPath = $viewsPath . '/' . $phpViewPath;

        if (file_exists($bladeFullPath)) {
            return $bladeFullPath;
        } elseif (file_exists($phpFullPath)) {
            return $phpFullPath;
        }

        throw new \Exception("View [{$view}] not found in namespace [{$namespace}].");
    }

    protected function getCompiledPath($view)
    {
        $compiledPath = $this->compiled ?: $this->app->storagePath('framework/views');
        
        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0755, true);
        }

        return $compiledPath . '/' . md5($this->path) . '.php';
    }

    public function render()
    {
        static::$renderLevel++;

        try {
            // Add errors to view data if not already present
            if (!isset($this->data['errors'])) {
                $session = app('session');
                
                $errors = $session->get('errors');
                
                if ($errors instanceof \Arpon\Support\MessageBag) {
                    $this->data['errors'] = $errors;
                } elseif (is_array($errors) && !empty($errors)) {
                    $this->data['errors'] = new \Arpon\Support\MessageBag($errors);
                } else {
                    $this->data['errors'] = new \Arpon\Support\MessageBag([]);
                }
            }
            
            // Only clear sections if this is the top-level rendering
            if (static::$renderLevel === 1) {
                ViewHelper::clear();
            }

            if (!file_exists($this->compiledPath) || filemtime($this->path) > filemtime($this->compiledPath)) {
                $this->compile();
            }

            // Make data available to the view
            extract($this->data);

            // Capture the view content
            ViewHelper::pushLayout(null);
            ob_start();
            include $this->compiledPath;
            $content = ob_get_clean();
            
            // If there's a layout, render it with the captured sections
            $layout = ViewHelper::popLayout();
            if ($layout) {
                $layoutView = new View($this->app, $layout, $this->data, $this->namespaces, $this->paths, $this->compiled);
                return $layoutView->render();
            }
            
            return $content;
        } finally {
            static::$renderLevel--;
        }
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    protected function compile()
    {
        $content = file_get_contents($this->path);
        $compiled = $this->compileContent($content);
        file_put_contents($this->compiledPath, $compiled);
    }

    protected function compileContent($content)
    {
        // Check if this is a Blade file
        $isBladeFile = strpos($this->path, '.blade.php') !== false;
        
        if ($isBladeFile) {
            // Protect @verbatim blocks
            $verbatims = [];
            $content = preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function ($matches) use (&$verbatims) {
                $placeholder = '__VERBATIM_PLACEHOLDER_' . count($verbatims) . '__';
                $verbatims[$placeholder] = $matches[1];
                return $placeholder;
            }, $content);

            // For Blade files, add namespace and compile Blade directives
            $content = "<?php use Arpon\View\ViewHelper; ?>\n" . $content;
            $content = $this->compileBladeDirectives($content);
            $content = $this->compileEchoes($content);

            // Restore @verbatim blocks
            foreach ($verbatims as $placeholder => $original) {
                $content = str_replace($placeholder, $original, $content);
            }
        } else {
            // For PHP files, use the old compilation method
            $content = $this->compileViewHelpers($content);
            $content = $this->compileEchoes($content);
            $content = $this->compileIncludes($content);
            $content = $this->compileIfStatements($content);
            $content = $this->compileLoops($content);
            $content = $this->compileComments($content);
        }

        return $content;
    }

    protected function compileBladeDirectives($content)
    {
        // Compile @extends
        $content = preg_replace_callback('/@extends\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $args = substr($matches[1], 1, -1);
            $parts = preg_split('/,\s*/', $args, 2);
            $layout = trim($parts[0], " '\"");
            return "<?php ViewHelper::extend('$layout'); ?>";
        }, $content);
        
        // Compile @section and @endsection
        $content = preg_replace_callback('/@section\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $args = substr($matches[1], 1, -1);
            $parts = preg_split('/,\s*/', $args, 2);
            $name = trim($parts[0], " '\"");
            if (count($parts) === 2) {
                return "<?php ViewHelper::section('$name'); echo {$parts[1]}; ViewHelper::endsection(); ?>";
            }
            return "<?php ViewHelper::section('$name'); ?>";
        }, $content);
        
        $content = preg_replace('/@endsection/', '<?php ViewHelper::endsection(); ?>', $content);
        
        // Compile @yield
        $content = preg_replace_callback('/@yield\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $args = substr($matches[1], 1, -1);
            $parts = preg_split('/,\s*/', $args, 2);
            $name = trim($parts[0], " '\"");
            if (count($parts) === 2) {
                return "<?php echo ViewHelper::yield('$name', {$parts[1]}); ?>";
            }
            return "<?php echo ViewHelper::yield('$name'); ?>";
        }, $content);
        
        // Compile @include
        $content = preg_replace_callback('/@include\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $args = substr($matches[1], 1, -1);
            $parts = preg_split('/,\s*/', $args, 2);
            $view = trim($parts[0], " '\"");
            if (count($parts) === 2) {
                return "<?php echo ViewHelper::include('$view', array_merge(get_defined_vars(), {$parts[1]})); ?>";
            }
            return "<?php echo ViewHelper::include('$view', get_defined_vars()); ?>";
        }, $content);
        
        // Compile @push and @endpush
        $content = preg_replace('/@push\s*\([\'"]([^\'"]+)[\'"]\s*\)/', '<?php ViewHelper::push(\'$1\'); ?>', $content);
        $content = preg_replace('/@endpush/', '<?php ViewHelper::endpush(); ?>', $content);
        
        // Compile @stack
        $content = preg_replace('/@stack\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"])?\s*\)/', '<?php echo ViewHelper::stack(\'$1\', \'$2\'); ?>', $content);
        $content = preg_replace('/@stack\s*\([\'"]([^\'"]+)[\'"]\s*\)/', '<?php echo ViewHelper::stack(\'$1\'); ?>', $content);
        
        // Compile @component and @endcomponent
        $content = preg_replace_callback('/@component\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $args = substr($matches[1], 1, -1);
            $parts = preg_split('/,\s*/', $args, 2);
            $view = trim($parts[0], " '\"");
            if (count($parts) === 2) {
                return "<?php ViewHelper::startComponent('$view', {$parts[1]}); ?>";
            }
            return "<?php ViewHelper::startComponent('$view', []); ?>";
        }, $content);
        
        $content = preg_replace('/@endcomponent/', '<?php echo ViewHelper::renderComponent(); ?>', $content);
        
        // Compile conditional directives - order matters!
        $content = preg_replace('/@switch\s*(\((?:[^()]+|(?1))*\))/', '<?php switch$1: ?>', $content);
        $content = preg_replace('/@case\s*(\((?:[^()]+|(?1))*\))/', '<?php case $1: ?>', $content);
        $content = preg_replace('/@break/', '<?php break; ?>', $content);
        $content = preg_replace('/@default/', '<?php default: ?>', $content);
        $content = preg_replace('/@endswitch/', '<?php endswitch; ?>', $content);
        
        // Remove whitespace between switch and first case to avoid syntax errors
        $content = preg_replace('/(\<\?php\s+switch\(.+?\):\s*\?>)\s+(\<\?php\s+case)/s', '$1$2', $content);
        
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        $content = preg_replace('/@elseif\s*(\((?:[^()]+|(?1))*\))/', '<?php elseif $1: ?>', $content);
        $content = preg_replace('/@else(?!\w)/', '<?php else: ?>', $content);
        $content = preg_replace('/@if\s*(\((?:[^()]+|(?1))*\))/', '<?php if $1: ?>', $content);

        // Standard Blade shortcuts
        $content = preg_replace('/@unless\s*(\((?:[^()]+|(?1))*\))/', '<?php if (! ($1)): ?>', $content);
        $content = preg_replace('/@endunless/', '<?php endif; ?>', $content);
        $content = preg_replace('/@isset\s*(\((?:[^()]+|(?1))*\))/', '<?php if (isset($1)): ?>', $content);
        $content = preg_replace('/@endisset/', '<?php endif; ?>', $content);
        $content = preg_replace('/@empty\s*(\((?:[^()]+|(?1))*\))/', '<?php if (empty($1)): ?>', $content);
        $content = preg_replace('/@endempty/', '<?php endif; ?>', $content);

        // Auth directives
        $content = preg_replace('/@auth\s*(\((?:[^()]+|(?1))*\))/', '<?php if (app(\'auth\')->guard($1)->check()): ?>', $content);
        $content = preg_replace('/@auth(?!\w)/', '<?php if (app(\'auth\')->check()): ?>', $content);
        $content = preg_replace('/@endauth/', '<?php endif; ?>', $content);
        $content = preg_replace('/@guest\s*(\((?:[^()]+|(?1))*\))/', '<?php if (app(\'auth\')->guard($1)->guest()): ?>', $content);
        $content = preg_replace('/@guest(?!\w)/', '<?php if (app(\'auth\')->guest()): ?>', $content);
        $content = preg_replace('/@endguest/', '<?php endif; ?>', $content);

        // JSON directive
        $content = preg_replace('/@json\s*(\((?:[^()]+|(?1))*\))/', '<?php echo json_encode$1; ?>', $content);

        // Class and Style directives
        $content = preg_replace('/@class\s*(\((?:[^()]+|(?1))*\))/', '<?php echo ViewHelper::classAttr$1; ?>', $content);
        $content = preg_replace('/@style\s*(\((?:[^()]+|(?1))*\))/', '<?php echo ViewHelper::styleAttr$1; ?>', $content);

        // Form state directives
        $content = preg_replace('/@checked\s*(\((?:[^()]+|(?1))*\))/', '<?php echo $1 ? "checked" : ""; ?>', $content);
        $content = preg_replace('/@selected\s*(\((?:[^()]+|(?1))*\))/', '<?php echo $1 ? "selected" : ""; ?>', $content);
        $content = preg_replace('/@disabled\s*(\((?:[^()]+|(?1))*\))/', '<?php echo $1 ? "disabled" : ""; ?>', $content);
        $content = preg_replace('/@readonly\s*(\((?:[^()]+|(?1))*\))/', '<?php echo $1 ? "readonly" : ""; ?>', $content);
        $content = preg_replace('/@required\s*(\((?:[^()]+|(?1))*\))/', '<?php echo $1 ? "required" : ""; ?>', $content);
        
        // Compile loop directives
        $content = preg_replace_callback('/@forelse\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $inner = substr($matches[1], 1, -1);
            preg_match('/(.+)\s+as\s+(.+)/s', $inner, $parts);
            return "<?php if(count({$parts[1]}) > 0): foreach({$parts[1]} as {$parts[2]}): ?>";
        }, $content);
        $content = preg_replace('/@empty/', '<?php endforeach; else: ?>', $content);
        $content = preg_replace('/@endforelse/', '<?php endif; ?>', $content);

        $content = preg_replace_callback('/@foreach\s*(\((?:[^()]+|(?1))*\))/', function ($matches) {
            $inner = substr($matches[1], 1, -1);
            preg_match('/(.+)\s+as\s+(.+)/s', $inner, $parts);
            return "<?php foreach ({$parts[1]} as {$parts[2]}): ?>";
        }, $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/@for\s*(\((?:[^()]+|(?1))*\))/', '<?php for $1: ?>', $content);
        $content = preg_replace('/@endfor(?!\w)/', '<?php endfor; ?>', $content);
        $content = preg_replace('/@while\s*(\((?:[^()]+|(?1))*\))/', '<?php while $1: ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);
        
        // Compile @error and @enderror directives
        $content = preg_replace('/@error\s*\([\'"]([^\'"]+)[\'"]\s*\)/', '<?php if (isset($errors) && $errors->has(\'$1\')): $message = $errors->first(\'$1\'); ?>', $content);
        $content = preg_replace('/@enderror/', '<?php endif; ?>', $content);
        
        // Compile other directives
        $content = preg_replace('/@csrf/', '<?php echo ViewHelper::csrf_field(); ?>', $content);
        $content = preg_replace('/@method\s*\([\'"]([^\'"]+)[\'"]\s*\)/', '<?php echo ViewHelper::method_field(\'$1\'); ?>', $content);
        
        // Compile @php and @endphp
        $content = preg_replace('/@php\s+(.+?)\s+@endphp/s', '<?php $1 ?>', $content);
        $content = preg_replace('/@php/s', '<?php ', $content);
        $content = preg_replace('/@endphp/s', ' ?>', $content);
        
        // Compile Blade comments (remove them entirely)
        $content = preg_replace('/\{\{\s*--\s*(.*?)\s*--\s*\}\}/', '', $content);
        
        return $content;
    }

    protected function compileViewHelpers($content)
    {
        // Compile ViewHelper::method calls
        $patterns = [
            '/ViewHelper::section\s*\([\'"]([^\'"]+)[\'"]\s*\)/' => '<?php ViewHelper::section(\'$1\'); ?>',
            '/ViewHelper::endsection\s*\(\s*\)/' => '<?php ViewHelper::endsection(); ?>',
            '/ViewHelper::yield\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"])?\s*\)/' => '<?php echo ViewHelper::yield(\'$1\', \'$2\'); ?>',
            '/ViewHelper::push\s*\([\'"]([^\'"]+)[\'"]\s*\)/' => '<?php ViewHelper::push(\'$1\'); ?>',
            '/ViewHelper::endpush\s*\(\s*\)/' => '<?php ViewHelper::endpush(); ?>',
            '/ViewHelper::stack\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]*)[\'"])?\s*\)/' => '<?php echo ViewHelper::stack(\'$1\', \'$2\'); ?>',
            '/ViewHelper::extend\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+?))?\s*\)/' => '<?php ViewHelper::extend(\'$1\', $2 ?? []); ?>',
            '/ViewHelper::include\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+?))?\s*\)/' => '<?php echo ViewHelper::include(\'$1\', $2 ?? []); ?>',
            '/ViewHelper::component\s*\([\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+?))?\s*\)/' => '<?php echo ViewHelper::component(\'$1\', $2 ?? []); ?>',
            '/ViewHelper::e\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::e($1); ?>',
            '/ViewHelper::link\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::link($1); ?>',
            '/ViewHelper::style\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::style($1); ?>',
            '/ViewHelper::script\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::script($1); ?>',
            '/ViewHelper::form\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::form($1); ?>',
            '/ViewHelper::endform\s*\(\s*\)/' => '<?php echo ViewHelper::endform(); ?>',
            '/ViewHelper::csrf_field\s*\(\s*\)/' => '<?php echo ViewHelper::csrf_field(); ?>',
            '/ViewHelper::method_field\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::method_field($1); ?>',
            '/ViewHelper::asset_url\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::asset_url($1); ?>',
            '/ViewHelper::url\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::url($1); ?>',
            '/ViewHelper::route\s*\((.+?)\s*\)/' => '<?php echo ViewHelper::route($1); ?>',
            '/ViewHelper::old\s*\((.+?)\s*(?:,\s*(.+?))?\s*\)/' => '<?php echo ViewHelper::old($1, $2 ?? \'\'); ?>',
            '/ViewHelper::error\s*\((.+?)\s*(?:,\s*(.+?))?\s*\)/' => '<?php echo ViewHelper::error($1, $2 ?? \'\'); ?>',
            '/ViewHelper::has_error\s*\((.+?)\s*\)/' => '<?php if (ViewHelper::has_error($1)): ?>',
            '/ViewHelper::request_is\s*\((.+?)\s*\)/' => '<?php if (ViewHelper::request_is($1)): ?>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    protected function compileEchoes($content)
    {
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo ViewHelper::e($1); ?>', $content);
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);

        return $content;
    }

    protected function compileIncludes($content)
    {
        $pattern = '/@include\s*\([\'"]([^\'"]+)[\'"]\)/';
        
        $content = preg_replace_callback($pattern, function ($matches) {
            $includedView = $matches[1];
            $includedPath = $this->findViewPath($includedView);
            $includedContent = file_get_contents($includedPath);
            return $this->compileContent($includedContent);
        }, $content);

        return $content;
    }

    protected function compileIfStatements($content)
    {
        $content = preg_replace('/@if\s*\((.+?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

        return $content;
    }

    protected function compileLoops($content)
    {
        $content = preg_replace('/@foreach\s*\((.+?)\s+as\s+(.+?)\)/', '<?php foreach ($1 as $2): ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/@for\s*\((.+?)\)/', '<?php for ($1): ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);
        $content = preg_replace('/@while\s*\((.+?)\)/', '<?php while ($1): ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);

        return $content;
    }

    protected function compileComments($content)
    {
        $content = preg_replace('/\{\{\s*--\s*(.*?)\s*--\s*\}\}/', '', $content);

        return $content;
    }

    public function __toString()
    {
        return $this->render();
    }
}
