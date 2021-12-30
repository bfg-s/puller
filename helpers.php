<?php

if (!function_exists('pullEmit')) {

    function pullEmit (string $name, array $detail = []) {
        return compact('name', 'detail');
    }
}
