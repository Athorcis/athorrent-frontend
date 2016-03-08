<?php

namespace Athorrent\Utils\Cache;

class ApcCache extends Cache {
    public function exists($key) {
        return apcu_exists($key);
    }

    public function fetch($key) {
        if (apcu_exists($key)) {
            return apcu_fetch($key);
        }

        return false;
    }

    public function store($key, $value, $lifetime = 0) {
        return apcu_store($key, $value, $lifetime);
    }
}

?>
