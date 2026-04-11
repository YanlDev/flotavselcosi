<?php

namespace App\Enums;

enum ItemEquipamiento: string
{
    case Extintor = 'extintor';
    case Botiquin = 'botiquin';
    case Conos = 'conos';
    case LlantaRepuesto = 'llanta_repuesto';
    case GataLlave = 'gata_llave';
    case CableArranque = 'cable_arranque';
    case Linterna = 'linterna';
    case SirenaAlarma = 'sirena_alarma';
    case SirenaPatrullaje = 'sirena_patrullaje';

    public function label(): string
    {
        return match ($this) {
            self::Extintor => 'Extintor PQS (1 kg mínimo, vigente)',
            self::Botiquin => 'Botiquín de primeros auxilios',
            self::Conos => 'Conos de seguridad',
            self::LlantaRepuesto => 'Llanta de repuesto (aro completo)',
            self::GataLlave => 'Gata hidráulica y llave de ruedas',
            self::CableArranque => 'Cable de arranque',
            self::Linterna => 'Linterna / luz de emergencia',
            self::SirenaAlarma => 'Sirena / alarma',
            self::SirenaPatrullaje => 'Sirena patrullaje (PATO)',
        };
    }

    public function cantidadMinima(): string
    {
        return match ($this) {
            self::Conos => '2',
            self::GataLlave => '1-Set',
            self::SirenaAlarma, self::SirenaPatrullaje => 'Según tipo',
            default => '1',
        };
    }

    public function tieneVencimiento(): bool
    {
        return $this === self::Extintor;
    }
}
