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

        Schema::create('mechanics', function ($table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('cars', function ($table) {
            $table->id();
            $table->foreignId('mechanic_id')->nullable()->constrained('mechanics')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('car_id')->nullable()->constrained('cars')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('tags', function ($table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('posts', function ($table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('post_tag', function ($table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('applications', function ($table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('environments', function ($table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('deployments', function ($table) {
            $table->id();
            $table->foreignId('environment_id')->nullable()->constrained('environments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    #[Test]
    public function it_passes_when_relationship_is_has_one(): void
    {
        $user = User::create();

        Post::create(['user_id' => $user->id]);

        $this->assertHasOne(Post::class, $user->post);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_has_one(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The hasOne relationship is not an instance of the model Post'
        );

        $user = User::create();

        Post::create(['user_id' => $user->id]);

        $this->assertHasOne(Post::class, $user->other);
    }

    #[Test]
    public function it_passes_when_relationship_is_belongs_to(): void
    {
        $category = Category::create();

        $post = Post::create(['category_id' => $category->id]);

        $this->assertBelongsTo(Category::class, $post->category);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_belongs_to(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The belongsTo relationship is not an instance of the model Category'
        );

        $category = Category::create();

        $post = Post::create(['category_id' => $category->id]);

        $this->assertBelongsTo(Category::class, $post->other);
    }

    #[Test]
    public function it_passes_when_relationship_is_belongs_to_many(): void
    {
        $tag = Tag::create();
        $post = Post::create();

        $post->tags()->attach($tag);

        $this->assertBelongsToMany(Tag::class, $post->tags, 1);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_belongs_to_many(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The belongsToMany relationship is not an instance of the model Tag'
        );

        Tag::create();

        $post = Post::create();

        $this->assertBelongsToMany(Tag::class, $post->comments, 1);
    }

    #[Test]
    public function it_passes_when_relationship_is_has_many(): void
    {
        $category = Category::create();

        Post::create(['category_id' => $category->id]);

        $this->assertHasMany( Post::class, $category->posts);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_has_many(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The hasMany relationship is not an instance of the model Post'
        );

        $category = Category::create();

        Post::create(['category_id' => $category->id]);

        $this->assertHasMany( Post::class, $category->others);
    }

    #[Test]
    public function it_passes_when_relationship_is_has_as_one_through(): void
    {
        $mechanic = Mechanic::create();

        $car = Car::create(['mechanic_id' => $mechanic->id]);

        User::create(['car_id' => $car->id]);

        $this->assertHasOneThrough(User::class, $mechanic->carUser);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_has_as_one_through(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The hasOneThrough relationship is not an instance of the model User'
        );

        $mechanic = Mechanic::create();

        $car = Car::create(['mechanic_id' => $mechanic->id]);

        User::create(['car_id' => $car->id]);

        $this->assertHasOneThrough(User::class, $mechanic->carOther);
    }

    #[Test]
    public function it_passes_when_relationship_is_has_many_through(): void
    {
        $application = Application::create();

        $environment = Environment::create(['application_id' => $application->id]);

        Deployment::create(['environment_id' => $environment->id]);

        $this->assertHasManyThrough(Deployment::class, $application->deployments);
    }

    #[Test]
    public function fails_if_relationship_does_not_exist_has_many_through(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->expectExceptionMessage(
            'The hasManyThrough relationship is not an instance of the model Deployment'
        );

        $application = Application::create();

        $environment = Environment::create(['application_id' => $application->id]);

        Deployment::create(['environment_id' => $environment->id]);

        $this->assertHasManyThrough(Deployment::class, $application->others);
    }
}

class User extends Model
{
    protected $fillable = ['car_id'];

    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }
}

class Post extends Model
{
    protected $fillable = ['user_id', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}

class Category extends Model
{
    protected $fillable = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

class Tag extends Model
{
    protected $fillable = [];

}

class Mechanic extends Model
{
    /**
     * Get the car's user.
     */
    public function carUser(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Car::class);
    }
}

class Car extends Model
{
    protected $fillable = ['mechanic_id'];
}

class Application extends Model
{
    /**
     * Get all the deployments for the application.
     */
    public function deployments(): HasManyThrough
    {
        return $this->hasManyThrough(Deployment::class, Environment::class);
    }
}

class Environment extends Model
{
    protected $fillable = ['application_id'];
}

class Deployment extends Model
{
    protected $fillable = ['environment_id'];

}

