<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_marketnest_homepage_renders(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('MarketNest')
            ->assertSeeText('Discover local.')
            ->assertSeeText('Buy smart. Sell easy.');
    }
}
