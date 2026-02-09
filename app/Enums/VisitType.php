<?php

namespace App\Enums;

enum VisitType: string
{
    case RAWAT_JALAN = 'rawat_jalan';
    case RAWAT_INAP = 'rawat_inap';
    case IGD = 'igd';
    case MCU = 'mcu';

    public function label(): string
    {
        return match ($this) {
            self::RAWAT_JALAN => 'Rawat Jalan',
            self::RAWAT_INAP => 'Rawat Inap',
            self::IGD => 'IGD',
            self::MCU => 'Medical Check Up',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RAWAT_JALAN => 'primary',
            self::RAWAT_INAP => 'success',
            self::IGD => 'danger',
            self::MCU => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RAWAT_JALAN => 'heroicon-o-user',
            self::RAWAT_INAP => 'heroicon-o-home',
            self::IGD => 'heroicon-o-exclamation-triangle',
            self::MCU => 'heroicon-o-clipboard-document-check',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
