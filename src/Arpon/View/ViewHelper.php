<?php

namespace Arpon\View;

use Arpon\Foundation\Application;

class ViewHelper
{
    protected static $sections = [];
    protected static $stacks = [];
    protected static $componentStack = [];
    protected static $currentSection = null;
    protected static $currentStack = null;
    protected static $layoutStack = [];

    public static function section($name)
    {
        static::$currentSection = $name;
        static::$sections[$name] = '';
        ob_start();
    }

    public static function endsection()
    {
        if (static::$currentSection) {
            static::$sections[static::$currentSection] = ob_get_clean();
            static::$currentSection = null;
        }
    }

    public static function yield($section, $default = null)
    {
        return static::$sections[$section] ?? ($default ?? '');
    }

    public static function push($stack)
    {
        static::$currentStack = $stack;
        if (!isset(static::$stacks[$stack])) {
            static::$stacks[$stack] = [];
        }
        ob_start();
    }

    public static function endpush()
    {
        if (static::$currentStack) {
            static::$stacks[static::$currentStack][] = ob_get_clean();
            static::$currentStack = null;
        }
    }

    public static function stack($stack, $default = null)
    {
        if (!isset(static::$stacks[$stack])) {
            return $default ?? '';
        }
        return implode('', static::$stacks[$stack]);
    }

    public static function extend($layout, $data = [])
    {
        if (!empty(static::$layoutStack)) {
            static::$layoutStack[count(static::$layoutStack) - 1] = $layout;
        }
        return $layout;
    }

    public static function pushLayout($layout = null)
    {
        static::$layoutStack[] = $layout;
    }

    public static function popLayout()
    {
        return array_pop(static::$layoutStack);
    }

    public static function getCurrentLayout()
    {
        return !empty(static::$layoutStack) ? static::$layoutStack[count(static::$layoutStack) - 1] : null;
    }

    public static function clearCurrentLayout()
    {
        if (!empty(static::$layoutStack)) {
            static::$layoutStack[count(static::$layoutStack) - 1] = null;
        }
    }

    public static function include($view, $data = [])
    {
        return app('view')->make($view, $data)->render();
    }

    public static function startComponent($name, $data = [])
    {
        static::$componentStack[] = ['name' => $name, 'data' => $data];
        ob_start();
    }

    public static function renderComponent()
    {
        $content = ob_get_clean();
        $component = array_pop(static::$componentStack);
        
        $data = $component['data'];
        $data['slot'] = $content;
        
        return static::component($component['name'], $data);
    }

    public static function component($component, $data = [])
    {
        $view = strpos($component, '::') !== false ? $component : "components.{$component}";
        
        return app('view')->make($view, $data)->render();
    }

    public static function e($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function asset_url($path)
    {
        $app = app();
        $baseUrl = $app->make('config')->get('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function url($path)
    {
        $app = app();
        $baseUrl = $app->make('config')->get('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function route($name, $parameters = [], $absolute = true)
    {
        return app('url')->route($name, $parameters, $absolute);
    }

    public static function csrf_field()
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    public static function method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }

    public static function link($url, $text = null, $attributes = [])
    {
        $text = $text ?: $url;
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . static::e($value) . '"';
        }
        return '<a href="' . static::e($url) . '"' . $attrs . '>' . static::e($text) . '</a>';
    }

    public static function style($url)
    {
        return '<link rel="stylesheet" href="' . static::e($url) . '">';
    }

    public static function script($url)
    {
        return '<script src="' . static::e($url) . '"></script>';
    }

    public static function form($action = null, $method = 'POST', $attributes = [])
    {
        $action = $action ?: '';
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . static::e($value) . '"';
        }
        return '<form action="' . static::e($action) . '" method="' . strtoupper($method) . '"' . $attrs . '>';
    }

    public static function endform()
    {
        return '</form>';
    }

    public static function old($key, $default = '')
    {
        if (function_exists('old')) {
            return old($key, $default);
        }
        return $default;
    }

    public static function error($field, $default = '')
    {
        $errors = session('errors');
        if ($errors && method_exists($errors, 'first')) {
            return $errors->first($field) ?: $default;
        }
        return $default;
    }

    public static function has_error($field)
    {
        $errors = session('errors');
        if ($errors && method_exists($errors, 'has')) {
            return $errors->has($field);
        }
        return false;
    }

    public static function request_is(...$patterns)
    {
        if (function_exists('request')) {
            return request()->is(...$patterns);
        }
        return false;
    }

    public static function classAttr($classes)
    {
        if (is_array($classes)) {
            $result = [];
            foreach ($classes as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif ($value) {
                    $result[] = $key;
                }
            }
            $classes = implode(' ', $result);
        }

        return $classes ? ' class="' . static::e($classes) . '"' : '';
    }

    public static function styleAttr($styles)
    {
        if (is_array($styles)) {
            $result = [];
            foreach ($styles as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif ($value) {
                    $result[] = $key;
                }
            }
            $styles = implode('; ', $result);
        }

        return $styles ? ' style="' . static::e($styles) . '"' : '';
    }

    public static function clear()
    {
        static::$sections = [];
        static::$stacks = [];
        static::$currentSection = null;
        static::$currentStack = null;
        static::$layoutStack = [];
    }

    public static function getSections()
    {
        return static::$sections;
    }
}
