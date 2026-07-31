<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home redirects to products page', function () {
    $this->get('/')
        ->assertRedirect('/products');
});

test('products page is accessible', function () {
    $this->get('/products')
        ->assertSuccessful();
});
