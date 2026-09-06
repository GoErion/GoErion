<?php

declare(strict_types=1);

use Database\Factories\UserFactory;

it('welcomes guests with the approved marketing copy and working destinations', function () {
    $response = $this->get(route('marketing'));

    $response->assertOk()
        ->assertSee('Run your wholesale and hardware business with clarity.')
        ->assertSee('Wholesale and hardware operations, connected')
        ->assertSee('Get started')
        ->assertSee('Explore the product')
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('marketing').'"', false)
        ->assertDontSee('Open GoErion')
        ->assertDontSee('href="#"', false)
        ->assertDontSee('tailwindcss.com/plus-assets')
        ->assertSee('alt="GoErion"', false);
});

it('offers authenticated visitors company entry and a csrf protected post logout', function () {
    $response = $this->actingAs(UserFactory::new()->make())->get(route('marketing'));

    $response->assertOk()
        ->assertSee('Open GoErion')
        ->assertSee('href="'.route('companies.select').'"', false)
        ->assertSee('action="'.route('logout').'" method="POST"', false)
        ->assertSee('name="_token"', false)
        ->assertDontSee('href="'.route('login').'"', false)
        ->assertDontSee('href="'.route('register').'"', false);
});

it('renders accessible navigation and a uniquely addressable illustrative preview', function () {
    $response = $this->get(route('marketing'));

    $response->assertSee('aria-controls="marketing-mobile-menu"', false)
        ->assertSee(':aria-expanded="open.toString()"', false)
        ->assertSee('@keydown.escape.window', false)
        ->assertSee('x-cloak', false)
        ->assertSee('x-show="open"', false)
        ->assertSee('Illustrative preview')
        ->assertSee('No live company information is shown.')
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false);

    expect(substr_count($response->getContent(), 'id="product-preview"'))->toBe(1);
    expect(substr_count($response->getContent(), '<h1 '))->toBe(1);
    expect(substr_count($response->getContent(), '<main '))->toBe(1);
});
