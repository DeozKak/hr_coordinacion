<?php
namespace Tests\Feature;
use Tests\TestCase;
class TmpWebhookTest extends TestCase
{
    public function test_sin_secreto_configurado_el_webhook_rechaza_todo(): void
    {
        config(['services.meta_whatsapp.verify_token' => null,
                'services.meta_whatsapp.app_secret'   => null]);

        /* Contraprueba del fallo: una verificación SIN hub_verify_token pasaba
           porque el token configurado también era null. */
        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_challenge=ABC123')
             ->assertStatus(403)
             ->assertDontSee('ABC123');

        // Y un POST firmado con secreto vacío, que cualquiera puede calcular.
        $cuerpo = json_encode(['entry' => []]);
        $this->call('POST', '/webhook/whatsapp', [], [], [],
                    ['HTTP_X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $cuerpo, ''),
                     'CONTENT_TYPE' => 'application/json'], $cuerpo)
             ->assertStatus(403);
    }

    public function test_con_secreto_solo_pasa_quien_lo_conoce(): void
    {
        config(['services.meta_whatsapp.verify_token' => 'token-de-prueba',
                'services.meta_whatsapp.app_secret'   => 'secreto-de-prueba']);

        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=token-de-prueba&hub_challenge=ABC123')
             ->assertOk()->assertSee('ABC123');

        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=otro&hub_challenge=ABC123')
             ->assertStatus(403);
        $this->get('/webhook/whatsapp?hub_mode=subscribe&hub_challenge=ABC123')
             ->assertStatus(403);

        $cuerpo = json_encode(['entry' => []]);
        $firmar = fn (string $secreto) => 'sha256=' . hash_hmac('sha256', $cuerpo, $secreto);
        $enviar = fn (string $firma) => $this->call('POST', '/webhook/whatsapp', [], [], [],
            ['HTTP_X-Hub-Signature-256' => $firma, 'CONTENT_TYPE' => 'application/json'], $cuerpo);

        $enviar($firmar('secreto-de-prueba'))->assertOk();
        $enviar($firmar(''))->assertStatus(403);
        $enviar('sha256=loquesea')->assertStatus(403);
    }

    public function test_ningun_secreto_queda_en_el_codigo(): void
    {
        $sospechosos = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));
        foreach ($it as $f) {
            if ($f->getExtension() !== 'php') continue;
            if (preg_match('/Bearer [A-Za-z0-9_\-]{20,}|EAAQ[A-Za-z0-9]{20,}/', file_get_contents($f->getPathname()))) {
                $sospechosos[] = $f->getPathname();
            }
        }
        $this->assertEquals([], $sospechosos);

        // Y los valores salen de la configuración, no de env() en caliente.
        foreach (['WhatsAppWebhookController.php' => 'Http/Controllers',
                  'WhatsAppBotService.php' => 'Services'] as $archivo => $dir) {
            $fuente = file_get_contents(app_path("{$dir}/{$archivo}"));
            $this->assertStringNotContainsString("env('META", $fuente, $archivo);
        }
    }
}
