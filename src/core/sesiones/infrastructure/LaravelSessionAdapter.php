<?php

declare(strict_types=1);

namespace Ventas\Core\Sesiones\Infrastructure;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

final class LaravelSessionAdapter
{
    public function peticion(): Request
    {
        $peticion = request();

        return $peticion;
    }

    public function sesion(): Store
    {
        /** @var Store $sesion */
        $sesion = session();

        return $sesion;
    }

    public function autenticacion(): AuthFactory
    {
        /** @var AuthFactory $autenticacion */
        $autenticacion = auth();

        return $autenticacion;
    }
}
