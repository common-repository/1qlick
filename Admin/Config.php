<?php

namespace OneQlick\Admin;

use WC_Admin_Settings;
use WC_Settings_Page;

class Config extends WC_Settings_Page
{
    public static function hook()
    {
        add_filter("woocommerce_page_wc-settings", function () {
            new self();
        });
    }

    public function __construct()
    {
        $this->id = "1qlick_settings";
        $this->label = __("1Qlick Settings", "1qlick");

        add_action("woocommerce_sections_" . $this->id, [
            $this,
            "output_sections",
        ]);

        add_action("woocommerce_get_settings_1qlick_settings", [
            $this,
            "get_config",
        ]);

        parent::__construct();
    }

    public function get_config()
    {
        return [
            "Enabled" => [
                "type" => "checkbox",
                "id" => "1qlick_enabled",
                "title" => _x("1Qlick Enabled", "", "1qlick"),
                "desc_tip" => _x(
                    "Enables 1Qlick button on cart",
                    "Is button visible in cart",
                    "1Qlick"
                ),
                "default" => "yes",
                "label" => _x("1Qlick Enabled", "", "1qlick"),
            ],
            "QR Code" => [
                "type" => "checkbox",
                "id" => "1qlick_qrcode",
                "title" => _x("Show 1Qlick QR", "", "1qlick"),
                "desc_tip" => _x(
                    "Shows QR code on desktop",
                    "Is QR code visible in cart on desktop",
                    "1Qlick"
                ),
                "default" => "no",
                "label" => _x("1Qlick QR Code", "", "1qlick"),
            ],
            "Button Location" => [
                "type" => "multiselect",
                "id" => "1qlick_button_location",
                "title" => _x("Hooks to add button to", "", "1qlick"),
                "desc_tip" => _x(
                    "Enables 1Qlick button on cart",
                    "Where to display the button in cart",
                    "1Qlick"
                ),
                "options" => [
                    "woocommerce_before_cart" => "woocommerce_before_cart",
                    "woocommerce_before_cart_table" =>
                        "woocommerce_before_cart_table",
                    "woocommerce_before_cart_contents" =>
                        "woocommerce_before_cart_contents",
                    "woocommerce_cart_contents" => "woocommerce_cart_contents",
                    "woocommerce_after_cart_contents" =>
                        "woocommerce_after_cart_contents",
                    "woocommerce_after_cart_table" =>
                        "woocommerce_after_cart_table",
                    "woocommerce_before_cart_totals" =>
                        "woocommerce_before_cart_totals",
                    "woocommerce_cart_totals_before_shipping" =>
                        "woocommerce_cart_totals_before_shipping",
                    "woocommerce_cart_totals_after_shipping" =>
                        "woocommerce_cart_totals_after_shipping",
                    "woocommerce_cart_totals_before_order_total" =>
                        "woocommerce_cart_totals_before_order_total",
                    "woocommerce_cart_totals_after_order_total" =>
                        "woocommerce_cart_totals_after_order_total",
                    "woocommerce_proceed_to_checkout" =>
                        "woocommerce_proceed_to_checkout",
                    "woocommerce_after_cart_totals" =>
                        "woocommerce_after_cart_totals",
                    "woocommerce_after_cart" => "woocommerce_after_cart",
                ],
            ],
            "Mode" => [
                "type" => "select",
                "id" => "1qlick_mode",
                "title" => _x(
                    "Environment the site is running in",
                    "",
                    "1qlick"
                ),
                "desc_tip" => _x(
                    "Do not set this to anything but production",
                    "Sets 1qlick mode",
                    "1Qlick"
                ),
                "default" => "production",
                "options" => [
                    "testing" => "Testing",
                    "production" => "Production",
                ],
            ],
            "Basic Statistics" => [
                "type" => "checkbox",
                "id" => "basic_statistics",
                "title" => _x("Basic Statistics", "", "1qlick"),
                "desc_tip" => _x(
                    "Enables 1Qlick to gather basic statistics about your checkout",
                    "",
                    "1Qlick"
                ),
                "default" => "no",
                "label" => _x("Basic Statistics", "", "1qlick"),
            ],
        ];
    }

    public function save()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);
        WC_Admin_Settings::save_fields($settings);
    }

    public function get_sections()
    {
        $sections = [
            "" => __("General", "1Qlick"),
        ];

        return apply_filters(
            "woocommerce_get_sections_" . $this->id,
            $sections
        );
    }

    public function output()
    {
        $settings = $this->get_settings_for_section("");

        WC_Admin_Settings::output_fields($settings);
    }
}
