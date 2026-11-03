<?php

test('public registration is disabled so accounts cannot choose their own role', function () {
    $this->get('/register')->assertNotFound();
});
