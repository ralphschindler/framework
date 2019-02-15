<?php

namespace Illuminate\Tests\Database\Fixtures\EloquentBuilderStubs;

use Illuminate\Database\Eloquent\Model;

class ModelParentStub extends Model
{
    public function foo()
    {
        return $this->belongsTo(ModelCloseRelatedStub::class);
    }

    public function address()
    {
        return $this->belongsTo(ModelCloseRelatedStub::class, 'foo_id');
    }

    public function activeFoo()
    {
        return $this->belongsTo(ModelCloseRelatedStub::class, 'foo_id')->where('active', true);
    }
}
