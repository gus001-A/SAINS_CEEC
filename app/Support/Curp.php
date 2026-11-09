<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validación de la CURP y consulta de sus datos (nombre, fecha de nacimiento, sexo).
 *
 * 1. Validación local: estructura oficial, fecha real y dígito verificador.
 *    De la propia CURP se obtienen la fecha de nacimiento y el sexo.
 * 2. Consulta a RENAPO a través del proveedor configurado en config/services.php
 *    (CURP_API_URL / CURP_API_TOKEN). Si no hay proveedor o falla, se usa sólo la
 *    validación local y el nombre lo captura la persona.
 */
class Curp
{
    private const PATRON = '/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HMX]'
        . '(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)'
        . '[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/';

    private const DICCIONARIO = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';

    public static function normalizar(?string $curp): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $curp));
    }

    /** Mensaje de error de la validación local, o null si la CURP es válida. */
    public static function errorFormato(string $curp): ?string
    {
        if (strlen($curp) !== 18) {
            return 'La CURP debe tener exactamente 18 caracteres.';
        }
        if (!preg_match(self::PATRON, $curp) || !self::fechaNacimiento($curp)) {
            return 'La CURP no tiene un formato válido.';
        }
        if ((int) $curp[17] !== self::digitoVerificador($curp)) {
            return 'La CURP no es válida (el dígito verificador no coincide).';
        }

        return null;
    }

    /** Regla de validación de Laravel para el formato oficial de la CURP. */
    public static function regla(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($error = self::errorFormato(self::normalizar($value))) {
                $fail($error);
            }
        };
    }

    public static function esValida(string $curp): bool
    {
        return self::errorFormato($curp) === null;
    }

    private static function digitoVerificador(string $curp): int
    {
        $suma = 0;
        for ($i = 0; $i < 17; $i++) {
            $suma += mb_strpos(self::DICCIONARIO, $curp[$i]) * (18 - $i);
        }

        return (10 - $suma % 10) % 10;
    }

    /** Fecha de nacimiento (Y-m-d) codificada en la CURP. */
    public static function fechaNacimiento(string $curp): ?string
    {
        $aa = (int) substr($curp, 4, 2);
        $mm = (int) substr($curp, 6, 2);
        $dd = (int) substr($curp, 8, 2);
        // Posición 17: dígito para nacidos antes del 2000, letra a partir del 2000.
        $anio = (ctype_digit($curp[16] ?? '') ? 1900 : 2000) + $aa;

        return checkdate($mm, $dd, $anio) ? sprintf('%04d-%02d-%02d', $anio, $mm, $dd) : null;
    }

    /** Sexo en el formato del sistema: M (masculino) / F (femenino). */
    public static function sexo(string $curp): ?string
    {
        return ['H' => 'M', 'M' => 'F'][$curp[10] ?? ''] ?? null;
    }

    public static function apiConfigurada(): bool
    {
        return (bool) config('services.curp.url');
    }

    /**
     * Valida la CURP y devuelve sus datos:
     * ['valida' => bool, 'mensaje' => ?string, 'fuente' => 'renapo'|'local', 'datos' => [...]]
     */
    public static function consultar(string $curp): array
    {
        $curp = self::normalizar($curp);

        if ($error = self::errorFormato($curp)) {
            return ['valida' => false, 'mensaje' => $error, 'fuente' => 'local', 'datos' => null];
        }

        $local = [
            'curp' => $curp,
            'nombre' => null,
            'paterno' => null,
            'materno' => null,
            'fecha_nacimiento' => self::fechaNacimiento($curp),
            'sexo' => self::sexo($curp),
        ];

        if (!self::apiConfigurada()) {
            return ['valida' => true, 'mensaje' => null, 'fuente' => 'local', 'datos' => $local];
        }

        $renapo = self::consultarApi($curp);

        if ($renapo === false) {
            return [
                'valida' => false,
                'mensaje' => 'Esta CURP no está registrada en RENAPO. Verifica que esté bien escrita.',
                'fuente' => 'renapo',
                'datos' => null,
            ];
        }

        if ($renapo === null) {
            // Proveedor caído: no se bloquea el registro, se completa a mano.
            return ['valida' => true, 'mensaje' => null, 'fuente' => 'local', 'datos' => $local];
        }

        return [
            'valida' => true,
            'mensaje' => null,
            'fuente' => 'renapo',
            'datos' => array_merge($local, array_filter($renapo, fn ($v) => $v !== null && $v !== '')),
        ];
    }

    /**
     * @return array|false|null  datos de RENAPO; false si la CURP no existe; null si el servicio no respondió
     */
    private static function consultarApi(string $curp): array|false|null
    {
        $cacheKey = 'curp:renapo:' . $curp;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $token = (string) config('services.curp.token');
        $url = str_replace(['{curp}', '{token}'], [urlencode($curp), urlencode($token)], config('services.curp.url'));

        try {
            $peticion = Http::timeout((int) config('services.curp.timeout', 8))->acceptJson();
            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }
            $respuesta = $peticion->get($url);
        } catch (\Throwable $e) {
            Log::warning('[CURP] No se pudo consultar el servicio: ' . $e->getMessage());
            return null;
        }

        if (in_array($respuesta->status(), [404, 422], true)) {
            Cache::put($cacheKey, false, now()->addHour());
            return false;
        }

        if (!$respuesta->successful() || !is_array($respuesta->json())) {
            Log::warning('[CURP] Respuesta inesperada del servicio: HTTP ' . $respuesta->status());
            return null;
        }

        $json = $respuesta->json();
        $datos = self::extraerDatos($json);

        if (!$datos['nombre'] || !$datos['paterno']) {
            // Respondió, pero sin persona: la CURP no existe en RENAPO.
            if (self::indicaError($json)) {
                Cache::put($cacheKey, false, now()->addHour());
                return false;
            }
            Log::warning('[CURP] El servicio respondió sin nombre ni apellidos.');
            return null;
        }

        Cache::put($cacheKey, $datos, now()->addDays(30));

        return $datos;
    }

    /**
     * Extrae los datos sin depender de un proveedor en particular: busca en toda la
     * respuesta las llaves de nombre, apellidos, fecha y sexo con sus nombres más comunes.
     */
    private static function extraerDatos(array $json): array
    {
        $planos = [];
        array_walk_recursive($json, function ($valor, $llave) use (&$planos) {
            $llave = strtolower(preg_replace('/[^a-z]/i', '', (string) $llave));
            if (is_scalar($valor) && !isset($planos[$llave])) {
                $planos[$llave] = trim((string) $valor);
            }
        });

        $buscar = function (array $llaves) use ($planos) {
            foreach ($llaves as $llave) {
                if (!empty($planos[$llave])) {
                    return $planos[$llave];
                }
            }
            return null;
        };

        $sexo = strtoupper((string) $buscar(['clavesexo', 'sexo', 'sex', 'gender', 'genero']));
        $fecha = $buscar(['fechanacimiento', 'fechadenacimiento', 'fechanac', 'birthdate', 'dateofbirth', 'fecnac']);

        return [
            'nombre' => self::mayusculas($buscar(['nombres', 'nombre', 'names', 'name', 'firstname', 'givenname'])),
            'paterno' => self::mayusculas($buscar(['apellidopaterno', 'primerapellido', 'paterno', 'paternalsurname', 'apellido1', 'firstlastname'])),
            'materno' => self::mayusculas($buscar(['apellidomaterno', 'segundoapellido', 'materno', 'mothersmaidenname', 'maternalsurname', 'apellido2', 'secondlastname'])),
            'fecha_nacimiento' => self::fecha($fecha),
            'sexo' => match (true) {
                in_array($sexo, ['H', 'HOMBRE', 'MASCULINO', 'MALE'], true) => 'M',
                in_array($sexo, ['M', 'MUJER', 'FEMENINO', 'FEMALE'], true) => 'F',
                default => null,
            },
        ];
    }

    private static function indicaError(array $json): bool
    {
        foreach (['error', 'errors', 'success', 'valid', 'found'] as $llave) {
            if (array_key_exists($llave, $json)) {
                $valor = $json[$llave];
                return in_array($llave, ['success', 'valid', 'found'], true) ? !$valor : (bool) $valor;
            }
        }

        return false;
    }

    private static function mayusculas(?string $texto): ?string
    {
        return $texto === null ? null : mb_strtoupper(preg_replace('/\s+/', ' ', $texto));
    }

    private static function fecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $formato) {
            $f = \DateTime::createFromFormat('!' . $formato, substr($fecha, 0, 10));
            if ($f && $f->format($formato) === substr($fecha, 0, 10)) {
                return $f->format('Y-m-d');
            }
        }

        return null;
    }
}
