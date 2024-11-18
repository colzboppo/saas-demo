<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;

class OAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function socialite_mocker(string $providerType, string $email, int $id)
    {
        // Mock the Socialite user
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class, function (MockInterface $mock) use ($id, $email) {
            $mock->shouldReceive('getId')->andReturn($id);
            $mock->shouldReceive('getName')->andReturn('John Doe');
            $mock->shouldReceive('getEmail')->andReturn($email);
            $mock->shouldReceive('token')->andReturn('sample-token');
            $mock->shouldReceive('refreshToken')->andReturn('refresh-token');
        });

        $this->mock_provider($providerType, $socialiteUser);
    }

    protected function mock_provider(string $providerType, $socialiteUser)
    {
        // Mock Socialite provider
        $provider = $this->mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider
            ->shouldReceive('user')
            ->andReturn($socialiteUser);
        $provider
            ->shouldReceive('stateless')
            ->andReturn($provider);

        // Mock the Socialite driver
        Socialite::shouldReceive('driver')
            ->with($providerType)
            ->andReturn($provider)
            ->once();
    }

    #[Group("oauth")]
    public function test_redirect_to_provider()
    {
        $providers = collect([
            'github' => 'https://github.com/login/oauth/authorize',
            'google' => 'https://accounts.google.com/o/oauth2/auth',
        ]);
        $providers->each(function ($auth_url, $provider_type) {
            // Mock the Socialite driver
            $provider = $this->mock(\Laravel\Socialite\Contracts\Provider::class);
            $provider->shouldReceive('driver')
                ->with($provider_type);
            $provider->shouldReceive('stateless')
                ->andReturn($provider);
                
            $auth_redirect = config('oauth.enabled_providers.auth_url').'/redirect';
            $response = $this->get($auth_redirect);

            $response->assertStatus(302);

            $this->assertStringStartsWith($auth_url, $response->headers->get('Location'));
        });
    }

    #[Group("oauth")]
    public function test_handle_provider_callback_creates_user_and_logs_in()
    {
        $this->socialite_mocker('google', 'johndoe@example.com', 12345);

        // Ensure the user does not exist initially
        $this->assertDatabaseMissing('users', [
            'email' => 'johndoe@example.com',
        ]);

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert the user is created
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
            'name' => 'John Doe',
            'oauth_provider' => 'google',
            'oauth_provider_id' => '12345',
        ]);

        // Assert the user is authenticated
        $this->assertAuthenticated();

        // Assert redirection to home
        $response->assertRedirect('/dashboard');
    }

    #[Group("oauth")]
    public function test_handle_authorization_logs_in_existing_user()
    {
        // Create an existing user
        $user = User::factory()->create([
            'email' => 'existinguser@example.com',
            'password' => 'sample-token',
            'oauth_provider' => 'google',
            'oauth_provider_id' => '123',
        ]);
        
        $this->socialite_mocker('google', 'existinguser@example.com', 123);

        // Call the route
        $response = $this->get('/auth/google/callback');

        // Assert the user is still in the database (no duplicates)
        $this->assertDatabaseCount('users', 1);

        // Assert the user is authenticated
        $this->assertAuthenticatedAs($user);

        // Assert the user is redirected to the dashboard
        $response->assertRedirect('/dashboard');
    }
}
