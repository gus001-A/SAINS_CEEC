<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Curp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurpTest extends TestCase
{
    // CURP con formato y dígito verificador válidos.
    private const CURP = 'HEGG560427MVZRRL04';

    protected function setUp(): void
    {
        parent::setUp();

        // Base en memoria sólo con las tablas que toca el flujo de cuenta.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('usuario', function (Blueprint $t) {
            $t->id();
            $t->string('curp', 18)->nullable()->unique();
            $t->string('correo')->nullable()->unique();
            $t->string('contraseña')->nullable();
            $t->string('rol');
        });
        Schema::create('estudiante', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('paterno');
            $t->string('materno')->nullable();
            $t->date('fecha_nacimiento');
            $t->string('sexo')->nullable();
            $t->boolean('plan_activo')->default(false);
            $t->date('fecha_inscripcion');
            $t->string('curp', 18)->nullable();
            $t->unsignedBigInteger('usuario');
        });
        Schema::create('notificaciones', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_usuario');
            $t->string('tipo')->nullable();
            $t->string('titulo')->nullable();
            $t->text('mensaje')->nullable();
            $t->string('url')->nullable();
            $t->string('icono')->nullable();
            $t->string('color')->nullable();
            $t->timestamp('leida_at')->nullable();
            $t->timestamps();
        });

        config(['services.curp.url' => null, 'services.curp.token' => null]);
    }

    public function test_valida_formato_y_digito_verificador(): void
    {
        $this->assertNull(Curp::errorFormato(self::CURP));
        $this->assertSame('1956-04-27', Curp::fechaNacimiento(self::CURP));
        $this->assertSame('F', Curp::sexo(self::CURP));

        $this->assertNotNull(Curp::errorFormato('HEGG560427MVZRRL05')); // dígito verificador
        $this->assertNotNull(Curp::errorFormato('HEGG561327MVZRRL04')); // mes 13
        $this->assertNotNull(Curp::errorFormato('HEGG56042'));
    }

    public function test_sin_proveedor_solo_valida_localmente(): void
    {
        $this->getJson('/curp/validar?curp=' . strtolower(self::CURP))
            ->assertOk()
            ->assertJson([
                'valida' => true,
                'fuente' => 'local',
                'datos' => ['curp' => self::CURP, 'fecha_nacimiento' => '1956-04-27', 'sexo' => 'F', 'nombre' => null],
            ]);
    }

    public function test_autocompleta_con_renapo(): void
    {
        config(['services.curp.url' => 'https://curp.test/{curp}', 'services.curp.token' => 'secreto']);
        Http::fake(['curp.test/*' => Http::response([
            'error' => false,
            'response' => ['Solicitante' => [
                'Nombres' => 'Gabriela', 'ApellidoPaterno' => 'Hernández', 'ApellidoMaterno' => 'García',
                'FechaNacimiento' => '27/04/1956', 'ClaveSexo' => 'M',
            ]],
        ])]);

        $this->getJson('/curp/validar?curp=' . self::CURP)
            ->assertOk()
            ->assertJson([
                'valida' => true,
                'fuente' => 'renapo',
                'datos' => ['nombre' => 'GABRIELA', 'paterno' => 'HERNÁNDEZ', 'materno' => 'GARCÍA', 'fecha_nacimiento' => '1956-04-27', 'sexo' => 'F'],
            ]);

        Http::assertSent(fn ($r) => $r->url() === 'https://curp.test/' . self::CURP && $r->hasHeader('Authorization', 'Bearer secreto'));
    }

    public function test_curp_inexistente_en_renapo(): void
    {
        config(['services.curp.url' => 'https://curp.test/{curp}']);
        Http::fake(['curp.test/*' => Http::response(['error' => true, 'message' => 'No encontrada'], 404)]);

        $this->getJson('/curp/validar?curp=' . self::CURP)
            ->assertOk()
            ->assertJson(['valida' => false, 'fuente' => 'renapo']);
    }

    public function test_registro_con_curp(): void
    {
        $this->post('/register', [
            'curp' => self::CURP,
            'nombre' => 'gabriela', 'paterno' => 'hernández', 'materno' => 'garcía',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertRedirect();

        $user = User::where('curp', self::CURP)->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secreto123', $user->contraseña));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('GABRIELA', session('registro_curp')['nombre']);
    }

    public function test_inicio_de_sesion_con_curp(): void
    {
        $user = User::create(['curp' => self::CURP, 'contraseña' => Hash::make('secreto123'), 'rol' => 'estudiante']);

        $this->post('/login', ['usuario' => self::CURP, 'password' => 'incorrecta'])->assertSessionHasErrors('usuario');
        $this->assertGuest();

        $this->post('/login', ['usuario' => strtolower(self::CURP), 'password' => 'secreto123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_administrador_inicia_sesion_con_correo(): void
    {
        $admin = User::create(['correo' => 'admin@sistema.com', 'contraseña' => Hash::make('secreto123'), 'rol' => 'Administrador']);

        $this->post('/login', ['usuario' => 'admin@sistema.com', 'password' => 'secreto123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_estudiante_no_inicia_sesion_con_correo(): void
    {
        User::create(['curp' => self::CURP, 'correo' => 'alumno@correo.com', 'contraseña' => Hash::make('secreto123'), 'rol' => 'estudiante']);

        $this->post('/login', ['usuario' => 'alumno@correo.com', 'password' => 'secreto123'])
            ->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_curp_ya_registrada(): void
    {
        User::create(['curp' => self::CURP, 'contraseña' => Hash::make('secreto123'), 'rol' => 'estudiante']);

        $this->getJson('/curp/validar?curp=' . self::CURP)->assertJson(['valida' => false]);
        $this->post('/register', [
            'curp' => self::CURP, 'nombre' => 'Gabriela', 'paterno' => 'Hernández',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('curp');
    }

    public function test_registro_rechaza_curp_invalida(): void
    {
        $this->post('/register', [
            'curp' => 'HEGG560427MVZRRL05',
            'nombre' => 'Gabriela', 'paterno' => 'Hernández',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('curp');

        $this->assertSame(0, User::count());
    }
}
