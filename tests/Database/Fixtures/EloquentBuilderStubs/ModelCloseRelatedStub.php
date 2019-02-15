<?php

namespace Illuminate\Tests\Database\Fixtures\EloquentBuilderStubs;

use Illuminate\Database\Eloquent\Model;

class ModelCloseRelatedStub extends Model
{
    public function bar()
    {
        return $this->hasMany(ModelFarRelatedStub::class);
    }

    public function baz()
    {
        return $this->hasMany(ModelFarRelatedStub::class);
    }
}