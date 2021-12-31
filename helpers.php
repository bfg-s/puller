<?php

if (!function_exists('pullEmit')) {

    function pullEmit (string $name, $detail = null) {
        return compact('name', 'detail');
    }
}
