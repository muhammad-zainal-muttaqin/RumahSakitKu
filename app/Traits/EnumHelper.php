<?php

declare(strict_types=1);

namespace App\Traits;

use ValueError;

/**
 * Trait EnumHelper
 *
 * Helper methods for working with enums in Filament and other contexts.
 * Provides conversion to arrays, select options, and label generation.
 *
 * @package App\Traits
 */
trait EnumHelper
{
    /**
     * Get all enum cases as an array.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_reduce(
            self::cases(),
            function (array $carry, self $case): array {
                $carry[$case->value] = $case->label();
                return $carry;
            },
            []
        );
    }

    /**
     * Get enum cases for Filament select options.
     *
     * Returns array formatted for Filament Forms\Components\Select::options()
     *
     * @return array<string, string>
     */
    public static function toSelectOptions(): array
    {
        return self::toArray();
    }

    /**
     * Get enum cases for Filament table filters.
     *
     * @return array<string, string>
     */
    public static function toFilterOptions(): array
    {
        return self::toArray();
    }

    /**
     * Get enum cases for Filament toggle buttons.
     *
     * @return array<string, string>
     */
    public static function toToggleOptions(): array
    {
        return self::toArray();
    }

    /**
     * Get the label for the enum case.
     *
     * Default implementation converts case name to title case.
     * Override in enum for custom labels.
     *
     * @return string
     */
    public function label(): string
    {
        // Check if enum has a labels() method defined
        if (method_exists($this, 'labels')) {
            $labels = $this->labels();
            if (isset($labels[$this->value])) {
                return $labels[$this->value];
            }
        }

        // Default: convert case name to title case
        return str_replace('_', ' ', ucfirst(strtolower($this->name)));
    }

    /**
     * Get the color for the enum case.
     *
     * Used for Filament badges and indicators.
     * Override in enum for custom colors.
     *
     * @return string
     */
    public function color(): string
    {
        // Check if enum has a colors() method defined
        if (method_exists($this, 'colors')) {
            $colors = $this->colors();
            if (isset($colors[$this->value])) {
                return $colors[$this->value];
            }
        }

        // Default colors based on common patterns
        return match ($this->value) {
            'active', 'enabled', 'success', 'completed', 'approved', 'paid', 'yes', 'true' => 'success',
            'inactive', 'disabled', 'pending', 'waiting', 'draft', 'no', 'false' => 'warning',
            'deleted', 'blocked', 'failed', 'rejected', 'cancelled', 'error' => 'danger',
            'archived', 'expired', 'unknown' => 'gray',
            default => 'primary',
        };
    }

    /**
     * Get the icon for the enum case.
     *
     * Used for Filament badges and indicators.
     * Override in enum for custom icons.
     *
     * @return string
     */
    public function icon(): string
    {
        // Check if enum has an icons() method defined
        if (method_exists($this, 'icons')) {
            $icons = $this->icons();
            if (isset($icons[$this->value])) {
                return $icons[$this->value];
            }
        }

        // Default icons based on common patterns
        return match ($this->value) {
            'active', 'enabled', 'success', 'completed', 'approved', 'paid', 'yes', 'true' => 'heroicon-o-check-circle',
            'inactive', 'disabled', 'pending', 'waiting', 'draft' => 'heroicon-o-clock',
            'deleted', 'blocked', 'failed', 'rejected', 'cancelled', 'error' => 'heroicon-o-x-circle',
            'archived', 'expired' => 'heroicon-o-archive-box',
            'unknown' => 'heroicon-o-question-mark-circle',
            default => 'heroicon-o-flag',
        };
    }

    /**
     * Get the description for the enum case.
     *
     * Override in enum for custom descriptions.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        // Check if enum has a descriptions() method defined
        if (method_exists($this, 'descriptions')) {
            $descriptions = $this->descriptions();
            if (isset($descriptions[$this->value])) {
                return $descriptions[$this->value];
            }
        }

        return null;
    }

    /**
     * Get all enum values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum names.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get all labels.
     *
     * @return array<string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $case) => $case->label(), self::cases());
    }

    /**
     * Try to create enum from value, return null if not found.
     *
     * @param string $value
     * @return static|null
     */
    public static function tryFromValue(string $value): ?static
    {
        return self::tryFrom($value);
    }

    /**
     * Create enum from value, throw exception if not found.
     *
     * @param string $value
     * @return static
     * @throws ValueError
     */
    public static function fromValue(string $value): static
    {
        return self::from($value);
    }

    /**
     * Check if value exists in enum.
     *
     * @param string $value
     * @return bool
     */
    public static function hasValue(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Check if name exists in enum.
     *
     * @param string $name
     * @return bool
     */
    public static function hasName(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Get enum case by label (case-insensitive partial match).
     *
     * @param string $label
     * @return static|null
     */
    public static function fromLabel(string $label): ?static
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->label(), $label) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get default enum case.
     *
     * Override in enum to specify default.
     *
     * @return static|null
     */
    public static function default(): ?static
    {
        // Check if enum has a DEFAULT_CASE constant
        if (defined('static::DEFAULT_CASE')) {
            $defaultValue = static::DEFAULT_CASE;
            return self::tryFrom($defaultValue);
        }

        // Return first case as default
        $cases = self::cases();
        return $cases[0] ?? null;
    }

    /**
     * Get random enum case.
     *
     * @return static
     */
    public static function random(): static
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    /**
     * Get enum cases except specified values.
     *
     * @param array<string> $except
     * @return array<static>
     */
    public static function except(array $except): array
    {
        return array_filter(
            self::cases(),
            fn (self $case) => !in_array($case->value, $except, true)
        );
    }

    /**
     * Get enum cases only for specified values.
     *
     * @param array<string> $only
     * @return array<static>
     */
    public static function only(array $only): array
    {
        return array_filter(
            self::cases(),
            fn (self $case) => in_array($case->value, $only, true)
        );
    }

    /**
     * Get enum cases as array for Filament checkbox list.
     *
     * @return array<string, string>
     */
    public static function toCheckboxOptions(): array
    {
        return self::toArray();
    }

    /**
     * Get enum cases as array for Filament radio buttons.
     *
     * @return array<string, string>
     */
    public static function toRadioOptions(): array
    {
        return self::toArray();
    }

    /**
     * Get enum cases grouped by category.
     *
     * Override in enum with categories() method for grouping.
     *
     * @return array<string, array<string, string>>
     */
    public static function toGroupedOptions(): array
    {
        if (!method_exists(self::class, 'categories')) {
            return ['General' => self::toArray()];
        }

        $grouped = [];
        $categories = (self::cases()[0])->categories();

        foreach (self::cases() as $case) {
            $category = $categories[$case->value] ?? 'General';
            $grouped[$category][$case->value] = $case->label();
        }

        return $grouped;
    }

    /**
     * Check if enum case is active/enabled.
     *
     * Override in enum for custom active states.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return in_array($this->value, ['active', 'enabled', 'success', 'completed', 'approved', 'paid', 'yes', 'true'], true);
    }

    /**
     * Check if enum case is inactive/pending.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return in_array($this->value, ['inactive', 'disabled', 'pending', 'waiting', 'draft', 'no', 'false'], true);
    }

    /**
     * Check if enum case is in error/cancelled state.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return in_array($this->value, ['deleted', 'blocked', 'failed', 'rejected', 'cancelled', 'error'], true);
    }

    /**
     * Get CSS class for the enum case.
     *
     * @return string
     */
    public function cssClass(): string
    {
        return match ($this->color()) {
            'success' => 'text-green-600 bg-green-100',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'danger' => 'text-red-600 bg-red-100',
            'gray' => 'text-gray-600 bg-gray-100',
            'primary' => 'text-blue-600 bg-blue-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * Format enum for API response.
     *
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'value' => $this->value,
            'name' => $this->name,
            'label' => $this->label(),
            'color' => $this->color(),
            'icon' => $this->icon(),
            'description' => $this->description(),
        ];
    }

    /**
     * Get all enum cases formatted for API response.
     *
     * @return array<array>
     */
    public static function toApiResponseArray(): array
    {
        return array_map(
            fn (self $case) => $case->toApiResponse(),
            self::cases()
        );
    }
}
