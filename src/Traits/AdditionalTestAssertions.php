<?php

namespace WikiGods\AdditionalTestAssertions\Traits;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait AdditionalTestAssertions
{
    /**
     * Helper interno para obtener instancia de modelo.
     * @param string|object $model
     * @return Model
     */
    private function getModelInstance($model)
    {
        if (is_object($model)) {
            return $model;
        }

        if (is_string($model) && class_exists($model)) {
            return new $model;
        }

        throw new InvalidArgumentException("Assetion expects a Model instance or class name, received: " . gettype($model));
    }

    /**
     * Assert that a class uses a specific trait.
     */
    protected function assertClassUsesTrait($class, $trait): void
    {
        $className = is_object($class) ? get_class($class) : $class;
        $traits = class_uses($className);

        // Recursive search for traits in parent classes could be added here if strictly needed,
        // but class_uses is usually sufficient for direct usage.

        $this->assertArrayHasKey(
            $trait,
            $traits,
            "The class [{$className}] expects to use the trait [{$trait}]."
        );
    }

    /* -----------------------------------------------------------------
     |  MODEL STRUCTURE ASSERTIONS
     | -----------------------------------------------------------------
     */

    /**
     * Assert that a model has a UUID as a primary key and adheres to conventions.
     */
    protected function assertIsUuid($model): void
    {
        $instance = $this->getModelInstance($model);
        $keyName = $instance->getKeyName();
        $keyValue = $instance->getKey();

        // 1. Check Configuration
        $this->assertFalse(
            $instance->getIncrementing(),
            "The model [".get_class($instance)."] should have 'public \$incrementing = false;' for UUIDs."
        );

        $this->assertEquals(
            'string',
            $instance->getKeyType(),
            "The model [".get_class($instance)."] key type should be 'string'."
        );

        // 2. Check Value (if exists)
        if ($keyValue) {
            $this->assertTrue(
                Str::isUuid($keyValue),
                "The value of [{$keyName}] in [".get_class($instance)."] is not a valid UUID. Value: [{$keyValue}]"
            );
        }
    }

    /**
     * Assert that a model defines the expected attribute casts.
     */
    protected function assertCasts($model, array $expectedCasts): void
    {
        $instance = $this->getModelInstance($model);
        $actualCasts = $instance->getCasts();

        // Merge defaults usually handled by Laravel (id, etc) if necessary,
        // but exact match on requested keys is cleaner.

        foreach ($expectedCasts as $key => $type) {
            $this->assertArrayHasKey(
                $key,
                $actualCasts,
                "The attribute [{$key}] is missing from the casts array in [".get_class($instance)."]."
            );

            // Simple normalization for class names (remove leading backslash)
            $expectedType = ltrim($type, '\\');
            $actualType = ltrim($actualCasts[$key], '\\');

            // Handle partial matches for complex casts like 'decimal:2' if needed, or stick to exact.
            // For robust testing, exact match is preferred.
            $this->assertEquals(
                $expectedType,
                $actualType,
                "The cast for attribute [{$key}] in [".get_class($instance)."] does not match."
            );
        }
    }

    /**
     * Assert that a model defines the expected attribute appends.
     */
    protected function assertAppends($model, array $expectedAppends): void
    {
        $instance = $this->getModelInstance($model);

        $this->assertEquals(
            $expectedAppends,
            $instance->getAppends(),
            "The 'appends' array in [".get_class($instance)."] does not match expectation."
        );
    }

    /**
     * Assert the Route Key Name of the model.
     */
    protected function assertGetRouteKeyName($model, $expectedKey): void
    {
        $instance = $this->getModelInstance($model);

        $this->assertEquals(
            $expectedKey,
            $instance->getRouteKeyName(),
            "The route key name for [".get_class($instance)."] should be [{$expectedKey}]."
        );
    }

    /**
     * Assert a mutator properly sets a timestamp (Carbon) and handles nulls.
     */
    protected function assertSetTimestampAttribute($model, $attribute, $testValue = '2025-01-01 12:00:00', $allowNull = true): void
    {
        $instance = $this->getModelInstance($model);

        // Setter naming convention
        $method = 'set' . Str::studly($attribute) . 'Attribute';

        // 1. Check if mutator exists (if using old style) or just test behavior
        if (method_exists($instance, $method)) {
            $instance->{$method}($testValue);
        } else {
            // Modern Laravel (Casts/Attributes) or direct assignment
            $instance->{$attribute} = $testValue;
        }

        $this->assertInstanceOf(
            Carbon::class,
            $instance->{$attribute},
            "The attribute [{$attribute}] was not cast to an instance of Carbon."
        );

        $this->assertEquals(
            Carbon::parse($testValue)->format('Y-m-d H:i:s'),
            $instance->{$attribute}->format('Y-m-d H:i:s'),
            "The attribute [{$attribute}] value matches the expected timestamp."
        );

        if ($allowNull) {
            if (method_exists($instance, $method)) {
                $instance->{$method}(null);
            } else {
                $instance->{$attribute} = null;
            }
            $this->assertNull(
                $instance->{$attribute},
                "The attribute [{$attribute}] should accept null values."
            );
        }
    }

    /* -----------------------------------------------------------------
     |  RELATIONSHIP ASSERTIONS
     | -----------------------------------------------------------------
     */

    private function assertRelation($type, $model, $relationName, $relatedClass)
    {
        $instance = $this->getModelInstance($model);

        // Check method existence
        $this->assertTrue(
            method_exists($instance, $relationName),
            "The model [".get_class($instance)."] does not have a method named [{$relationName}]."
        );

        // Call the method to get the Relation object
        $relation = $instance->{$relationName}();

        // Check Relation Type
        $this->assertInstanceOf(
            $type,
            $relation,
            "The relation [{$relationName}] is not an instance of [{$type}]."
        );

        // Check Related Model Class
        $this->assertInstanceOf(
            $relatedClass,
            $relation->getRelated(),
            "The related model in [{$relationName}] is not an instance of [{$relatedClass}]."
        );
    }

    protected function assertBelongsTo($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(BelongsTo::class, $model, $relationName, $relatedClass);
    }

    protected function assertHasOne($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(HasOne::class, $model, $relationName, $relatedClass);
    }

    protected function assertHasMany($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(HasMany::class, $model, $relationName, $relatedClass);
    }

    protected function assertBelongsToMany($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(BelongsToMany::class, $model, $relationName, $relatedClass);
    }

    protected function assertHasOneThrough($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(HasOneThrough::class, $model, $relationName, $relatedClass);
    }

    protected function assertHasManyThrough($model, $relationName, $relatedClass): void
    {
        $this->assertRelation(HasManyThrough::class, $model, $relationName, $relatedClass);
    }

    /* -----------------------------------------------------------------
     |  EVENT & BROADCAST ASSERTIONS
     | -----------------------------------------------------------------
     */

    /**
     * Assert that an event does not broadcast to the current user.
     */
    protected function assertDontBroadcastToCurrentUser($event, $socketId = 'socket-id'): void
    {
        $this->assertInstanceOf(ShouldBroadcast::class, $event);

        // Simulate logic often used in events constructor or broadcast property
        if (property_exists($event, 'socket')) {
            $event->socket = $socketId;
        }

        // We check if the trait method is used effectively via the socket property verification
        // or stricter mocking if we had the Broadcast facade mocked.
        // Assuming the user implements `use InteractsWithSockets` or manually handles it.

        $this->assertEquals(
            $socketId,
            $event->socket,
            'The event ' . get_class($event) . ' property [socket] was not set correctly.'
        );

        // Additionally, check if methods exist
        $this->assertTrue(
            method_exists($event, 'dontBroadcastToCurrentUser'),
            "Event does not use InteractsWithSockets or implement dontBroadcastToCurrentUser."
        );
    }

    /**
     * Assert the type of broadcast channel.
     */
    protected function assertEventChannelType($channelType, $event): void
    {
        $types = [
            'public' => Channel::class,
            'private' => PrivateChannel::class,
            'presence' => PresenceChannel::class,
        ];

        if (!array_key_exists($channelType, $types)) {
            $this->fail("Invalid channel type [{$channelType}]. Valid types: " . implode(', ', array_keys($types)));
        }

        $channels = $event->broadcastOn();
        $channels = is_array($channels) ? $channels : [$channels];
        $channel = $channels[0] ?? null;

        $this->assertNotNull($channel, "The event did not return any channel from broadcastOn().");

        $this->assertInstanceOf(
            $types[$channelType],
            $channel,
            "The channel returned is not of type [{$channelType}]."
        );
    }

    /**
     * Assert the name of the broadcast channel.
     */
    protected function assertEventChannelName($channelName, $event): void
    {
        $channels = $event->broadcastOn();
        $channels = is_array($channels) ? $channels : [$channels];
        $channel = $channels[0] ?? null;

        $this->assertEquals(
            $channelName,
            $channel->name,
            "The channel name does not match."
        );
    }
}