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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait AdditionalTestAssertions
{
    /**
     * Assert that a class uses a specific trait.
     */
    protected function assertClassUsesTrait($trait, $class)
    {
        $modelName = class_basename($class);
        $traitName = class_basename($trait);

        // Recursive search for traits in parent classes could be added here if strictly needed,
        // but class_uses is usually sufficient for direct usage.

        $this->assertArrayHasKey(
            $trait,
            class_uses($class),
            "The class {$modelName} does not use {$traitName}."
        );
    }

    /* -----------------------------------------------------------------
     |  MODEL STRUCTURE ASSERTIONS
     | -----------------------------------------------------------------
     */

    /**
     * Assert that a model is configured to use UUIDs for its primary key.
     *
     * This assertion checks three things:
     * 1. The model's properties (`$incrementing`, `$keyType`).
     * 2. The primary key value of an existing model.
     * 3. The database column type.
     */
    protected function assertIsUuid($model)
    {
        $instance = (new $model);
        $keyName = $instance->getKeyName();
        $className = get_class($instance);

        // 1. Check Model Configuration first, as it's the fastest check.
        $this->assertFalse(
            $instance->getIncrementing(),
            "The model {$className}] must have 'public \$incrementing = false;' to use UUIDs."
        );

        $this->assertEquals(
            'string',
            $instance->getKeyType(),
            "The model {$className} must have 'protected \$keyType = \"string\";' to use UUIDs."
        );

        // 2. If the model exists, check if its key is a valid UUID.
        // This should happen before the schema check, as it's a common failure point.
        if ($instance->exists) {
            $keyValue = $instance->getKey();
            $this->assertTrue(
                Str::isUuid($keyValue),
                "The key {$keyName} on model [{$className}] is not a valid UUID. Value: [{$keyValue}]"
            );
        }

        // 3. Check Database Column Type. This is a slower check and should come last.
        $connection = $instance->getConnection();
        $tableName = $instance->getTable();
        $columnType = Schema::connection($connection->getName())->getColumnType($tableName, $keyName);

        // We allow a range of string-like types that can store UUIDs.
        // This list is expanded to be more compatible with different database drivers.
        $allowedTypes = ['uuid', 'string', 'char', 'guid', 'varchar', 'text'];
        $this->assertContains(
            Str::lower($columnType),
            $allowedTypes,
            "The column {$keyName} on table {$tableName} is of type {$columnType}, which is not suitable for UUIDs. Use `\$table->uuid('{$keyName}');` in your migration."
        );
    }

    /**
     * Assert that a model defines the expected attribute casts.
     */
    protected function assertCasts($model, $attributes)
    {
        $model = (new $model);

        $arrayMerge = array_merge([$model->getKeyName() => $model->getKeyType()], $attributes);

        $diffExport = var_export(array_diff($arrayMerge, $model->getCasts()), true);

        $short = str_replace(['array (', ')'], ['[', ']'], $diffExport);

        $this->assertEquals(
            $arrayMerge,
            $model->getCasts(),
            "The 'casts' array in ".PHP_EOL.$short.PHP_EOL." does not match expectation."
        );
    }

    /**
     * Assert that a model defines the expected attribute appends.
     */
    protected function assertAppends($model, $attributes)
    {
        $model = (new $model);

        $diffExport = var_export(array_diff($attributes, $model->getCasts()), true);

        $short = str_replace(['array (', ')'], ['[', ']'], $diffExport);

        $this->assertEquals(
            $attributes,
            $model->getAppends(),
            "The 'appends' array in ".PHP_EOL.$short.PHP_EOL." does not match expectation."
        );

    }

    /**
     * Assert the Route Key Name of the model.
     */
    protected function assertGetRouteKeyName($model, $field)
    {
        $newModel = (new $model);

        $this->assertTrue(
            Schema::hasColumn($newModel->getTable(), $field),
            "The table '{$newModel->getTable()}' does not contain the expected column '{$field}'."
        );

        $this->assertEquals(
            $field,
            $newModel->getRouteKeyName(),
            "The route key name for ".class_basename($model)." should be {$field}."
        );


    }

    /**
     * Assert a mutator properly sets a timestamp (Carbon) and handles nulls.
     */
    protected function assertSetTimestampAttribute($model, $field, $data = '13-07-2025')
    {

        $method = 'set' . Str::studly($field) . 'Attribute';

        $model = new $model;

        if (!Schema::hasColumn($model->getTable(), $field)){
            $this->fail("The table '{$model->getTable()}' does not contain the expected column '{$field}'.");
        }

        if (!method_exists($model, $method)){
            $this->fail("The method {$method} does not exist in the model ".class_basename($model).".");
        }

        if (!$data){
            $model->{$method}(null);

            $this->assertNull($model->{$field});
        }else{
            $model->{$method}($data);

            $this->assertInstanceOf(Carbon::class,
                $model->{$field},
                "The attribute {$field} was not cast to an instance of Carbon."
            );

            $this->assertEquals(Carbon::parse($data)->format('Y-m-d H:i:s'), $model->{$field});
        }

    }

    /* -----------------------------------------------------------------
     |  RELATIONSHIP ASSERTIONS
     | -----------------------------------------------------------------
     */

    private function assertRelation($type, $model, $relation, $customName)
    {
        $relations = ['HasMany', 'BelongsToMany', 'HasManyThrough'];
        $typeClassName = class_basename($type);
        $modelClassName = class_basename($model);
        $relationClassName = class_basename($relation);

        $plural = in_array($typeClassName, $relations)
            ? Str::plural($modelClassName) : $modelClassName;

        $customNameFinal = $customName ? $customName : Str::lower($plural);

        $relationCustomFinal = in_array($typeClassName, $relations)
            ? $relation->{$customNameFinal}->first() : $relation->{$customNameFinal};

        $this->assertInstanceOf(
            $model,
            $relationCustomFinal,
            'The model ' . $relationClassName . ' was expected to define a '. lcfirst($typeClassName) .' relationship "' . $customNameFinal . '", but it was not found.'
        );

    }

    protected function assertBelongsTo($model, $relation, $customName = null)
    {
        $this->assertRelation(BelongsTo::class, $model, $relation, $customName);
    }

    protected function assertHasOne($model, $relation, $customName = null)
    {
        $this->assertRelation(HasOne::class, $model, $relation, $customName);
    }

    protected function assertHasMany($model, $relation, $customName = null)
    {
        $this->assertRelation(HasMany::class, $model, $relation, $customName);
    }

    protected function assertBelongsToMany($model, $relation, $customName = null)
    {
        $this->assertRelation(BelongsToMany::class, $model, $relation, $customName);
    }

    protected function assertHasOneThrough($model, $relation, $customName = null)
    {
        $this->assertRelation(HasOneThrough::class, $model, $relation, $customName);
    }

    protected function assertHasManyThrough($model, $relation, $customName = null)
    {
        $this->assertRelation(HasManyThrough::class, $model, $relation, $customName);
    }

    /* -----------------------------------------------------------------
     |  EVENT & BROADCAST ASSERTIONS
     | -----------------------------------------------------------------
     */

    /**
     * Assert that an event does not broadcast to the current user.
     */
    protected function assertDontBroadcastToCurrentUser($event, $socketId = 'socket-id')
    {
        $this->assertInstanceOf(ShouldBroadcast::class, $event);

        $this->assertEquals(
            $socketId, // Generated by Broadcast::shouldReceive('socket')->andReturn('socket-id');
            $event->socket,
            'The event ' . get_class($event) . ' must call the method "dontBroadcastToCurrentUser" in the constructor.'
        );
    }

    /**
     * Assert the type of broadcast channel.
     */
    protected function assertEventChannelType($channelType, $event)
    {
        $types = [
            'public' => Channel::class,
            'private' => PrivateChannel::class,
            'presence' => PresenceChannel::class,
        ];

        if (!array_key_exists($channelType, $types)) {
            $this->fail("The channel type '{$channelType}' is not valid. Valid types are: " . implode(', ', array_keys($types)));
        }

        $channels = $event->broadcastOn();
        $channels = is_array($channels) ? $channels : [$channels];
        $channel = $channels[0] ?? null;

        $this->assertNotNull($channel, "The event did not return any channel from broadcastOn().");

        $this->assertInstanceOf(
            $types[$channelType],
            $channel,
            "The channel returned is not of type {$channelType}."
        );
    }

    /**
     * Assert the name of the broadcast channel.
     */
    protected function assertEventChannelName($channelName, $event)
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
