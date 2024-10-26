<?php
/**
 * @wordpress-plugin
 * Plugin Name: 1Qlick
 * Plugin URI: https://www.1qlick.com/
 * Description: 1Qlick Checkout Plugin.
 * Version: 0.2.0
 * Author: 1Qlick Dev Team
 * Text Domain: 1qlick
 * Domain Path: /i18n
 */

namespace OneQlick;

use OneQlick\Admin\Config;
use OneQlick\Admin\Loader;
use OneQlick\Admin\Tab;
use OneQlick\Api\Api;
use OneQlick\Frontend\CheckoutButton;
use WC_Admin_Settings;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class OneQlick
{
    public static function bootstrap()
    {
        if (WC_Admin_Settings::get_option("1qlick_enabled", "yes") === "yes") {
            require_once __DIR__ . "/Frontend/CheckoutButton.php";
            CheckoutButton::hook();
            require_once __DIR__ . "/Api/Api.php";
            Api::instance()->hook(get_plugin_data(__FILE__)["Version"]);
        }

        add_action("woocommerce_get_settings_pages", function ($settings) {
            require_once __DIR__ . "/Admin/Tab.php";
            Tab::hook();
            require_once __DIR__ . "/Admin/Config.php";
            $settings[] = new Config();

            return $settings;
        });

        load_plugin_textdomain(
            "1qlick",
            false,
            basename(dirname(__DIR__)) . "/i18n"
        );
    }
}

add_filter("plugins_loaded", function () {
    OneQlick::bootstrap();
});

add_filter(
    "load_textdomain_mofile",
    function ($file, $domain) {
        if (
            "1qlick" === $domain &&
            false !== strpos($file, WP_LANG_DIR . "/plugins/")
        ) {
            $locale = apply_filters(
                "plugin_locale",
                determine_locale(),
                $domain
            );
            $file =
                WP_PLUGIN_DIR .
                "/" .
                dirname(plugin_basename(__FILE__)) .
                "/i18n/" .
                $domain .
                "-" .
                $locale .
                ".mo";
        }
        return $file;
    },
    10,
    2
);
