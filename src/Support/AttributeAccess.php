<?php

namespace XML\Support;

use Illuminate\Support\Str;

trait AttributeAccess
{
    protected $attributes = [];

    /**
     * Determine if a piece of data is bound.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        $key = Str::camel($key);

        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get a piece of bound data to the view.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $key = Str::camel($key);

        return $this->attributes[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $key = Str::camel($key);

        $this->attributes[$key] = $value;
    }

    /**
     * Unset a piece of data from the view.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $key = Str::camel($key);

        unset($this->attributes[$key]);
    }

    public function attributes($values = null)
    {
        if ($values === null) {
            return $this->attributes;
        }
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    public function hasAttribute($key)
    {
        return $this->offsetExists($key);
    }

    public function setAttribute($key, $value)
    {
        $key = Str::camel($key);
        $this[$key] = $value;

        return $this;
    }
}
