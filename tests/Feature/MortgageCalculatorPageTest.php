<?php

it('displays the mortgage calculator page', function () {
    $response = $this->get('/mortgage-calculator');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Mortgage/Calculator')
    );
});

it('mortgage calculator route is named correctly', function () {
    expect(route('mortgage.calculator'))->toBe(url('/mortgage-calculator'));
});
