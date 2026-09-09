<?php

test('redirects the home page to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
