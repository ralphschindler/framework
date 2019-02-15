<?php

namespace Illuminate\Tests\Database\Fixtures\EloquentBuilderStubs;

use Illuminate\Database\Eloquent\Model;

class PluckDatesStub extends Model
{
    protected $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    protected function asDateTime($value)
    {
        return 'date_'.$value;
    }
}