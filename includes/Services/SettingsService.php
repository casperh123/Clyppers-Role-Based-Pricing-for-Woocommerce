<?php

namespace ClypperTechnology\RolePricing\Services;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's settings, stored as WordPress options.
 */
class SettingsService
{
    public const SHOW_VOLUME_BADGE = 'crbp_show_volume_badge';

    /**
     * Whether product cards and product pages show the "Volume discount!" badge. On by default.
     */
    public static function show_volume_badge(): bool
    {
        return 'no' !== get_option( self::SHOW_VOLUME_BADGE, 'yes' );
    }

    /**
     * Stored as 'yes'/'no' like WooCommerce's own options: update_option() ignores a `false` for an option that
     * doesn't exist yet, so switching off from the default would silently not be saved.
     */
    public static function set_show_volume_badge( bool $show ): void
    {
        update_option( self::SHOW_VOLUME_BADGE, $show ? 'yes' : 'no' );
    }

    /**
     * @return array{show_volume_badge: bool}
     */
    public static function all(): array
    {
        return [
            'show_volume_badge' => self::show_volume_badge(),
        ];
    }
}
