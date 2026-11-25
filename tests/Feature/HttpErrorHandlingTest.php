<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class HttpErrorHandlingTest extends TestCase
{
    public function test_production_rejects_state_changes_without_a_csrf_token(): void
    {
        $this->app->instance('env', 'production');
        config(['app.debug' => false]);

        foreach (['register.store', 'login.store', 'logout', 'listings.store'] as $name) {
            $this->post(route($name))->assertStatus(419)->assertSeeText('Page Expired');
        }
    }

    public function test_unexpected_production_errors_keep_private_details_out_of_the_response(): void
    {
        $this->app->instance('env', 'production');
        config(['app.debug' => false]);
        Route::get('/test-unexpected-error', function (): never {
            throw new RuntimeException('Private exception marker.');
        });

        $this->get('/test-unexpected-error')->assertServerError()
            ->assertSeeText('Server Error')->assertDontSee('Private exception marker.');
        $this->getJson('/test-unexpected-error')->assertServerError()
            ->assertExactJson(['message' => 'Server Error']);
    }
}
