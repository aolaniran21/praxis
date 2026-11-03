<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('uses the forwarded HTTPS scheme when behind a trusted proxy', function () {
    $response = $this->withHeader('X-Forwarded-Proto', 'https')
        ->get('/');

    $response->assertOk()
        ->assertSee('https://', escape: false)
        ->assertDontSee('http://', escape: false);
});
