<?php

namespace App\Enums;

enum VisitStatus: string
{
    case PENDAFTARAN = 'pendaftaran';
    case MENUNGGU = 'menunggu';
    case PROSES = 'proses';
    case SELESAI = 'selesai';
    case BATAL = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::PENDAFTARAN => 'Pendaftaran',
            self::MENUNGGU => 'Menunggu',
            self::PROSES => 'Dalam Proses',
            self::SELESAI => 'Selesai',
            self::BATAL => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDAFTARAN => 'gray',
            self::MENUNGGU => 'warning',
            self::PROSES => 'info',
            self::SELESAI => 'success',
            self::BATAL => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDAFTARAN => 'heroicon-o-pencil',
            self::MENUNGGU => 'heroicon-o-clock',
            self::PROSES => 'heroicon-o-play',
            self::SELESAI => 'heroicon-o-check-circle',
            self::BATAL => 'heroicon-o-x-circle',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }

    public static function activeStatuses(): array
    {
        return [
            self::PENDAFTARAN->value,
            self::MENUNGGU->value,
            self::PROSES->value,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->value, self::activeStatuses());
    }
}
