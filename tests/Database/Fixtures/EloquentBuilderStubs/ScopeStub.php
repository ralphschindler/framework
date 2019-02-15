<?php

namespace Illuminate\Tests\Database\Fixtures\EloquentBuilderStubs;

use Illuminate\Database\Eloquent\Model;

class ScopeStub extends Model
{
    public function scopeApproved($query)
    {
        $query->where('foo', 'bar');
    }
}