<?php

namespace WikiGods\AdditionalTestAssertions\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use WikiGods\AdditionalTestAssertions\Tests\TestCase;

class RelationshipModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup schema for all tests
        Schema::create('mechanics', function ($table) { $table->id(); $table->timestamps(); });
        Schema::create('cars', function ($table) { $table->id(); $table->foreignId('mechanic_id')->nullable(); $table->timestamps(); });
        Schema::create('users', function ($table) { $table->id(); $table->foreignId('car_id')->nullable(); $table->timestamps(); });
        Schema::create('categories', function ($table) { $table->id(); $table->timestamps(); });
        Schema::create('tags', function ($table) { $table->id(); $table->timestamps(); });
        Schema::create('posts', function ($table) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('post_tag', function ($table) {
            $table->id();
            $table->foreignId('post_id');
            $table->foreignId('tag_id');
            $table->timestamps();
        });
        Schema::create('applications', function ($table) { $table->id(); $table->timestamps(); });
        Schema::create('environments', function ($table) { $table->id(); $table->foreignId('application_id')->nullable(); $table->timestamps(); });
        Schema::create('deployments', function ($table) { $table->id(); $table->foreignId('environment_id')->nullable(); $table->timestamps(); });
    }

    /** @test */
    public function it_asserts_belongs_to_relationship()
    {
        $category = Category::create();
        $post = Post::create(['category_id' => $category->id]);

        // Convention: Post belongsTo Category -> property 'category'
        $this->assertBelongsTo(Category::class, $post);
    }

    /** @test */
    public function it_asserts_belongs_to_relationship_with_custom_name()
    {
        $user = User::create();
        $post = Post::create(['user_id' => $user->id]);

        // Custom: Post belongsTo User via 'author' relation
        $this->assertBelongsTo(User::class, $post, 'author');
    }

    /** @test */
    public function it_asserts_has_many_relationship()
    {
        $category = Category::create();
        Post::create(['category_id' => $category->id]);

        // Convention: Category hasMany Post -> property 'posts'
        $this->assertHasMany(Post::class, $category);
    }

    /** @test */
    public function it_asserts_has_one_relationship()
    {
        $user = User::create();
        Post::create(['user_id' => $user->id]);

        // Convention: User hasOne Post -> property 'post' (singular)
        $this->assertHasOne(Post::class, $user);
    }

    /** @test */
    public function it_asserts_belongs_to_many_relationship()
    {
        $tag = Tag::create();
        $post = Post::create();
        $post->tags()->attach($tag);

        // Convention: Post belongsToMany Tag -> property 'tags'
        $this->assertBelongsToMany(Tag::class, $post);
    }

    /** @test */
    public function it_asserts_has_one_through_relationship()
    {
        $mechanic = Mechanic::create();
        $car = Car::create(['mechanic_id' => $mechanic->id]);
        User::create(['car_id' => $car->id]);

        // Custom Name required as 'user' implies belongsTo/hasOne usually, or specific naming
        // Mechanic hasOneThrough User -> method 'carUser'
        $this->assertHasOneThrough(User::class, $mechanic, 'carUser');
    }

    /** @test */
    public function it_asserts_has_many_through_relationship()
    {
        $app = Application::create();
        $env = Environment::create(['application_id' => $app->id]);
        Deployment::create(['environment_id' => $env->id]);

        // Convention: Application hasManyThrough Deployment -> property 'deployments'
        $this->assertHasManyThrough(Deployment::class, $app);
    }

    /** @test */
    public function it_fails_if_relationship_returns_null_or_wrong_type()
    {
        $post = Post::create(); // No category

        $this->expectException(ExpectationFailedException::class);

        // This fails because $post->category is null, so it's not an instance of Category
        $this->assertBelongsTo(Category::class, $post);
    }
}

// Models for Testing
class User extends Model {
    protected $guarded = [];
    public function post(): HasOne { return $this->hasOne(Post::class); }
    public function author(): HasOne { return $this->hasOne(Post::class, 'user_id'); } // Reuse for testing custom
}

class Post extends Model {
    protected $guarded = [];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function tags(): BelongsToMany { return $this->belongsToMany(Tag::class); }
}

class Category extends Model {
    protected $guarded = [];
    public function posts(): HasMany { return $this->hasMany(Post::class); }
}

class Tag extends Model { protected $guarded = []; }

class Mechanic extends Model {
    protected $guarded = [];
    public function carUser(): HasOneThrough { return $this->hasOneThrough(User::class, Car::class); }
}

class Car extends Model { protected $guarded = []; }

class Application extends Model {
    protected $guarded = [];
    public function deployments(): HasManyThrough { return $this->hasManyThrough(Deployment::class, Environment::class); }
}

class Environment extends Model { protected $guarded = []; }
class Deployment extends Model { protected $guarded = []; }