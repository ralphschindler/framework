<?php

namespace Illuminate\Tests\Database\Fixtures\EloquentBuilderStubs;

use Illuminate\Database\Eloquent\Model;

class NoUpdatedAtTimestampStub extends Model
{
    const UPDATED_AT = null;

    protected $table = 'table';
}
