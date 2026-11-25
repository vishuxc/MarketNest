<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class DatabaseConnectionErrorTest extends TestCase
{
    public function test_production_connection_errors_render_generic_web_and_json_responses(): void
    {
        $this->app->instance('env', 'production');
        config(['app.debug' => true]);

        Route::get('/test-database-connection-error', function (): never {
            $cause = new PDOException('Access denied for a private database account.');
            $cause->errorInfo = ['HY000', 1045, 'Access denied'];

            throw new QueryException(
                'mysql',
                'select * from users where email = ?',
                ['private@example.test'],
                $cause,
            );
        });

        $message = 'MarketNest is temporarily unavailable. Please try again later.';

        $this->get('/test-database-connection-error')
            ->assertStatus(503)
            ->assertContent($message);

        $this->getJson('/test-database-connection-error')
            ->assertStatus(503)
            ->assertExactJson(['message' => $message]);
    }
}
