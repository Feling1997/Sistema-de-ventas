<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Domain\Repositorios;

interface CuentaCorrienteRepository
{
    public function cuotasPendientesDetalle(string $buscar = '', string $estado = 'todos', string $orden = 'vencimiento', string $direccion = 'ASC'): array;

    public function resumenGeneral(): array;

    public function listarRecibos(int $limite = 50, string $ordenSql = 'r.fecha DESC'): array;

    public function saldosFavorClientes(): array;

    public function buscarCuenta(int $id): ?array;

    public function cuotasPendientes(int $idCuenta): array;

    public function buscarRecibo(int $id): ?array;

    public function cantidadVencidasNoLeidas(int $idUsuario): int;

    public function marcarCuotaPagada(int $idCuota): bool;

    public function cancelarCuenta(int $idCuenta): bool;

    public function marcarAlertasLeidas(int $idUsuario): void;

    public function registrarAnticipo(int $idCliente, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array;

    public function registrarPago(int $idCuenta, array $cuotas, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array;
}
