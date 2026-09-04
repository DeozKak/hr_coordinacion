<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz no tiene pantalla propia: el ERP no es público, así que manda
     * al login. Se comprueba la redirección, no un 200, porque un 200 aquí
     * significaría que se coló contenido antes de autenticar.
     */
    public function test_la_raiz_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
