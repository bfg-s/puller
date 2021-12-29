<?php

namespace Bfg\Puller\Core;

class BladeDirectiveAlpineStore
{
    public static function generator(string $name = null, array $attributes = [])
    {
        if (!$name) {
            return "";
        }

        $json = json_encode($attributes);
        return <<<JS
Alpine.store("$name", $json);
JS;

    }
    public static function manyGenerator(array $stores = [], bool $needleEventListener = true)
    {
        $data = "";
        foreach ($stores as $name => $attributes) {
            $data .= static::generator($name, $attributes);
        }
        return $needleEventListener ? "document.addEventListener('alpine:init', function () {" . $data . "})" : $data;
    }
    public static function oneGenerator(string $name = null, array $attributes = [], bool $needleEventListener = true)
    {
        $data = static::generator($name, $attributes);
        return $needleEventListener ? "document.addEventListener('alpine:init', function () {" . $data . "})" : $data;

    }

    public static function manyDirective($expression)
    {
        return "<script type='text/javascript'><?php echo \\" . static::class . "::manyGenerator($expression); ?></script>";
    }

    public static function directive($expression)
    {
        return "<script type='text/javascript'><?php echo \\" . static::class . "::oneGenerator($expression); ?></script>";
    }
}
