<?php

namespace App\Enums;

enum PaymentType: string
{
    case BPJS = 'bpjs';
    case UMUM = 'umum';
    case ASURANSI = 'asuransi';
    case PERUSAHAAN = 'perusahaan';
    case GRATIS = 'gratis';

    public function label(): string
    {
        return match ($this) {
            self::BPJS => 'BPJS Kesehatan',
            self::UMUM => 'Umum',
            self::ASURANSI => 'Asuransi',
            self::PERUSAHAAN => 'Perusahaan',
            self::GRATIS => 'Gratis',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BPJS => 'success',
            self::UMUM => 'primary',
            self::ASURANSI => 'info',
            self::PERUSAHAAN => 'warning',
            self::GRATIS => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BPJS => 'heroicon-o-identification',
            self::UMUM => 'heroicon-o-currency-dollar',
            self::ASURANSI => 'heroicon-o-shield-check',
            self::PERUSAHAAN => 'heroicon-o-building-office',
            self::GRATIS => 'heroicon-o-gift',
        };
    }

    public function requiresBpjsNumber(): bool
    {
        return $this === self::BPJS;
    }

    public function requiresInsurance(): bool
    {
        return in_array($this, [self::ASURANSI, self::PERUSAHAAN]);
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
