<?php

namespace App\Enums;

enum PatientGender: string
{
    case LAKI_LAKI = 'L';
    case PEREMPUAN = 'P';

    public function label(): string
    {
        return match ($this) {
            self::LAKI_LAKI => 'Laki-laki',
            self::PEREMPUAN => 'Perempuan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LAKI_LAKI => 'info',
            self::PEREMPUAN => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LAKI_LAKI => 'heroicon-o-user',
            self::PEREMPUAN => 'heroicon-o-user-circle',
        };
    }

    public function salutation(): string
    {
        return match ($this) {
            self::LAKI_LAKI => 'Tn.',
            self::PEREMPUAN => 'Ny.',
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
