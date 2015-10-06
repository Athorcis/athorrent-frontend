<?php

namespace Athorrent\Utils\Cache;

class ApcCache extends Cache {
    public function exists($key) {
        return apc_exists($key);
    }

    public function fetch($key) {
        if (apc_exists($key)) {
            return apc_fetch($key);
        }

        return false;
    }

    public function store($key, $value, $lifetime = 0) {
        return apc_store($key, $value, $lifetime);
    }
}

?>
