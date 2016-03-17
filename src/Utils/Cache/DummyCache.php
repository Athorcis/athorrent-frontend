<?php

namespace Athorrent\Utils\Cache;

class DummyCache extends Cache
{
    public function exists($key)
    {
        return false;
    }

    public function fetch($key)
    {
        return false;
    }

    public function store($key, $value, $lifetime = 0)
    {
        return true;
    }
}
