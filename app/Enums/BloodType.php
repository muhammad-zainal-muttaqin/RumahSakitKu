<?php

namespace App\Enums;

enum BloodType: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::A_POSITIVE => 'A+',
            self::A_NEGATIVE => 'A-',
            self::B_POSITIVE => 'B+',
            self::B_NEGATIVE => 'B-',
            self::AB_POSITIVE => 'AB+',
            self::AB_NEGATIVE => 'AB-',
            self::O_POSITIVE => 'O+',
            self::O_NEGATIVE => 'O-',
            self::UNKNOWN => 'Tidak Diketahui',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::A_POSITIVE, self::A_NEGATIVE => 'danger',
            self::B_POSITIVE, self::B_NEGATIVE => 'warning',
            self::AB_POSITIVE, self::AB_NEGATIVE => 'info',
            self::O_POSITIVE, self::O_NEGATIVE => 'success',
            self::UNKNOWN => 'gray',
        };
    }

    public function isUniversalDonor(): bool
    {
        return $this === self::O_NEGATIVE;
    }

    public function isUniversalRecipient(): bool
    {
        return $this === self::AB_POSITIVE;
    }

    public function canDonateTo(self $recipient): bool
    {
        return match ($this) {
            self::O_NEGATIVE => true,
            self::O_POSITIVE => in_array($recipient, [self::A_POSITIVE, self::B_POSITIVE, self::AB_POSITIVE, self::O_POSITIVE]),
            self::A_NEGATIVE => in_array($recipient, [self::A_POSITIVE, self::A_NEGATIVE, self::AB_POSITIVE, self::AB_NEGATIVE]),
            self::A_POSITIVE => in_array($recipient, [self::A_POSITIVE, self::AB_POSITIVE]),
            self::B_NEGATIVE => in_array($recipient, [self::B_POSITIVE, self::B_NEGATIVE, self::AB_POSITIVE, self::AB_NEGATIVE]),
            self::B_POSITIVE => in_array($recipient, [self::B_POSITIVE, self::AB_POSITIVE]),
            self::AB_NEGATIVE => in_array($recipient, [self::AB_POSITIVE, self::AB_NEGATIVE]),
            self::AB_POSITIVE => $recipient === self::AB_POSITIVE,
            self::UNKNOWN => false,
        };
    }

    public function canReceiveFrom(self $donor): bool
    {
        return $donor->canDonateTo($this);
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }

    public static function commonOptions(): array
    {
        return [
            self::A_POSITIVE->value => self::A_POSITIVE->label(),
            self::A_NEGATIVE->value => self::A_NEGATIVE->label(),
            self::B_POSITIVE->value => self::B_POSITIVE->label(),
            self::B_NEGATIVE->value => self::B_NEGATIVE->label(),
            self::AB_POSITIVE->value => self::AB_POSITIVE->label(),
            self::AB_NEGATIVE->value => self::AB_NEGATIVE->label(),
            self::O_POSITIVE->value => self::O_POSITIVE->label(),
            self::O_NEGATIVE->value => self::O_NEGATIVE->label(),
        ];
    }
}
