<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class DatabaseEloquentModelCustomCastingTest extends DatabaseTestCase
{
    public function testBasicCustomCasting()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->uppercase = 'taylor';

        $this->assertSame('TAYLOR', $model->uppercase);
        $this->assertSame('TAYLOR', $model->getAttributes()['uppercase']);
        $this->assertSame('TAYLOR', $model->toArray()['uppercase']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('TAYLOR', $unserializedModel->uppercase);
        $this->assertSame('TAYLOR', $unserializedModel->getAttributes()['uppercase']);
        $this->assertSame('TAYLOR', $unserializedModel->toArray()['uppercase']);

        $model->syncOriginal();
        $model->uppercase = 'dries';
        $this->assertEquals('TAYLOR', $model->getOriginal('uppercase'));

        $model = new TestEloquentModelWithCustomCast;
        $model->uppercase = 'taylor';
        $model->syncOriginal();
        $model->uppercase = 'dries';
        $model->getOriginal();

        $this->assertEquals('DRIES', $model->uppercase);

        $model = new TestEloquentModelWithCustomCast;

        $model->address = $address = new Address('110 Kingsbrook St.', 'My Childhood House');
        $address->lineOne = '117 Spencer St.';
        $this->assertSame('117 Spencer St.', $model->getAttributes()['address_line_one']);

        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);
        $this->assertSame('My Childhood House', $model->address->lineTwo);

        $this->assertSame('110 Kingsbrook St.', $model->toArray()['address_line_one']);
        $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

        $model->address->lineOne = '117 Spencer St.';

        $this->assertFalse(isset($model->toArray()['address']));
        $this->assertSame('117 Spencer St.', $model->toArray()['address_line_one']);
        $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

        $this->assertSame('117 Spencer St.', json_decode($model->toJson(), true)['address_line_one']);
        $this->assertSame('My Childhood House', json_decode($model->toJson(), true)['address_line_two']);

        $model->address = null;

        $this->assertNull($model->toArray()['address_line_one']);
        $this->assertNull($model->toArray()['address_line_two']);

        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $model->options = ['foo' => 'bar'];
        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);

        $this->assertEquals(json_encode(['foo' => 'bar']), $model->getAttributes()['options']);

        $model = new TestEloquentModelWithCustomCast(['options' => []]);
        $model->syncOriginal();
        $model->options = ['foo' => 'bar'];
        $this->assertTrue($model->isDirty('options'));

        $model = new TestEloquentModelWithCustomCast;
        $model->birthday_at = now();
        $this->assertTrue(is_string($model->toArray()['birthday_at']));
    }

    public function testOneWayCasting()
    {
        // CastsInboundAttributes is used for casting that is unidirectional... only use case I can think of is one-way hashing...
        $model = new TestEloquentModelWithCustomCast;

        $model->password = 'secret';

        $this->assertEquals(hash('sha256', 'secret'), $model->password);
        $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret'), $model->password);

        $model->password = 'secret2';

        $this->assertEquals(hash('sha256', 'secret2'), $model->password);
        $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret2'), $model->password);
    }

    public function testSettingRawAttributesClearsTheCastCache()
    {
        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);

        $model->setRawAttributes([
            'address_line_one' => '117 Spencer St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
    }

    public function testWithCastableInterface()
    {
        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'value_object_with_caster' => serialize(new ValueObject('hello')),
        ]);

        $this->assertInstanceOf(ValueObject::class, $model->value_object_with_caster);

        $model->setRawAttributes([
            'value_object_caster_with_argument' => null,
        ]);

        $this->assertEquals('argument', $model->value_object_caster_with_argument);

        $model->setRawAttributes([
            'value_object_caster_with_caster_instance' => serialize(new ValueObject('hello')),
        ]);

        $this->assertInstanceOf(ValueObject::class, $model->value_object_caster_with_caster_instance);
    }

    public function testGetFromUndefinedCast()
    {
        $this->expectException(InvalidCastException::class);

        $model = new TestEloquentModelWithCustomCast;
        $model->undefined_cast_column;
    }

    public function testSetToUndefinedCast()
    {
        $this->expectException(InvalidCastException::class);

        $model = new TestEloquentModelWithCustomCast;
        $this->assertTrue($model->hasCast('undefined_cast_column'));

        $model->undefined_cast_column = 'Glāžšķūņu rūķīši';
    }

    public function testCastingIsNotExcessive()
    {
        Schema::create('places', function (Blueprint $table) {
            $table->increments('id');
            $table->string('coordinates');
        });

        $place = new TestEloquentPlaceModelWithCustomCast;
        $place->coordinates->x = 1;
        $place->coordinates->y = 2;

        // $this->assertEquals(0, PointCaster::$setCount); // this fails as set count is already at 2

        $place->save();

        $this->assertDatabaseHas('places', ['id' => 1, 'coordinates' => '{"x":1,"y":2}']);
        $this->assertEquals(1, PointCaster::$setCount); // fails with 6 set calls
    }
}

class TestEloquentModelWithCustomCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'address' => AddressCaster::class,
        'password' => HashCaster::class,
        'other_password' => HashCaster::class.':md5',
        'uppercase' => UppercaseCaster::class,
        'options' => JsonCaster::class,
        'value_object_with_caster' => ValueObject::class,
        'value_object_caster_with_argument' => ValueObject::class.':argument',
        'value_object_caster_with_caster_instance' => ValueObjectWithCasterInstance::class,
        'undefined_cast_column' => UndefinedCast::class,
        'birthday_at' => DateObjectCaster::class,
    ];
}

class TestEloquentPlaceModelWithCustomCast extends Model
{
    public $timestamps = false;
    protected $table = 'places';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'coordinates' => PointCaster::class,
    ];
}

class HashCaster implements CastsInboundAttributes
{
    public function __construct($algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => hash($this->algorithm, $value)];
    }
}

class UppercaseCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return strtoupper($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => strtoupper($value)];
    }
}

class AddressCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Address($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
    }
}

class JsonCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value, true);
    }

    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }
}

class ValueObjectCaster implements CastsAttributes
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function get($model, $key, $value, $attributes)
    {
        if ($this->argument) {
            return $this->argument;
        }

        return unserialize($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return serialize($value);
    }
}

class ValueObject implements Castable
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function castUsing(array $arguments)
    {
        return new class(...$arguments) implements CastsAttributes {
            private $argument;

            public function __construct($argument = null)
            {
                $this->argument = $argument;
            }

            public function get($model, $key, $value, $attributes)
            {
                if ($this->argument) {
                    return $this->argument;
                }

                return unserialize($value);
            }

            public function set($model, $key, $value, $attributes)
            {
                return serialize($value);
            }
        };
    }
}

class ValueObjectWithCasterInstance extends ValueObject
{
    public static function castUsing(array $arguments)
    {
        return new ValueObjectCaster();
    }
}

class Address
{
    public $lineOne;
    public $lineTwo;

    public function __construct($lineOne, $lineTwo)
    {
        $this->lineOne = $lineOne;
        $this->lineTwo = $lineTwo;
    }
}

class DateObjectCaster implements CastsAttributes
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function get($model, $key, $value, $attributes)
    {
        return Carbon::parse($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value->format('Y-m-d');
    }
}

class PointCaster implements CastsAttributes
{
    static $setCount = 0;

    public function get($model, $key, $value, $attributes)
    {
        $vo = new Point();

        $vo->setFromSerialization($value);

        return $vo;
    }

    public function set($model, $key, $value, $attributes)
    {
        static::$setCount++;

        return [$key => $value->toSerialization()];
    }
}

class Point {
    public $x;
    public $y;

    public function setFromSerialization($value)
    {
        [$this->x, $this->y] = json_decode($value);
    }

    public function toSerialization()
    {
        return json_encode(['x' => $this->x, 'y' => $this->y]);
    }
}

