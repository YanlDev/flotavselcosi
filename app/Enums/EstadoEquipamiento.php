<?php

namespace App\Enums;

enum EstadoEquipamiento: string
{
    case Si = 'si';
    case No = 'no';
    case Renovar = 'renovar';
    case Reparar = 'reparar';
    case NoAplica = 'no_aplica';

    public function label(): string
    {
        return match ($this) {
            self::Si => 'SÍ',
            self::No => 'NO',
            self::Renovar => 'RENOVAR',
            self::Reparar => 'REPARAR',
            self::NoAplica => 'NO APLICA',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Si => 'green',
            self::No => 'red',
            self::Renovar => 'amber',
            self::Reparar => 'amber',
            self::NoAplica => 'zinc',
        };
    }

    public function esAlerta(): bool
    {
        return match ($this) {
            self::No, self::Renovar, self::Reparar => true,
            default => false,
        };
    }
}
