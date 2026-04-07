<?php

/**
 * El registro abierto está deshabilitado.
 * El flujo completo de registro por invitación está en RegistroInvitacionTest.
 */
test('registro directo está deshabilitado y redirige al login', function () {
    $this->get(route('register'))
        ->assertRedirect(route('login'));
});
