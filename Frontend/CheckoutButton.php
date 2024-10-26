<?php

namespace OneQlick\Frontend;

use WC_Admin_Settings;

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class CheckoutButton
{
    const URLS = [
        "production" => "https://www.1qlick.com",
        "testing" => "https://testing.1qlick.com",
    ];

    /** @var bool */
    private $assetsAdded;

    public function __construct()
    {
        $this->assetsAdded = false;
    }

    public static function hook()
    {
        $button = new self();

        foreach (
            WC_Admin_Settings::get_option("1qlick_button_location", [])
            as $location
        ) {
            add_action($location, [$button, "getOneQlickButton"]);
        }
    }

    public function getOneQlickButton()
    {
        $qr = WC_Admin_Settings::get_option("1qlick_qrcode", "no") === "yes" ? "qr" : "";
        $basic_stats = WC_Admin_Settings::get_option("basic_statistics", "no") === "yes" ? true : false;

        if (!$this->assetsAdded) {
            $this->assetsAdded = true;

            wp_register_script(
                "what_is_1qlick_js",
                plugins_url("whatis.js", __FILE__),
                [],
                true,
                true
            );
            wp_enqueue_script("what_is_1qlick_js");
            wp_register_script(
                "1qlick_qr_js_dependency",
                "https://www.easyproject.cn/easyqrcodejs/easy.qrcode.min.js?v=4",
                [],
                true,
                true
            );
            wp_enqueue_script("1qlick_qr_js_dependency");
            wp_register_script(
                "1qlick_qr_js",
                plugins_url("qrcode.js", __FILE__),
                [],
                true,
                true
            );
            wp_enqueue_script("1qlick_qr_js");
            wp_enqueue_style(
                "what_is_1qlick_css",
                plugins_url("whatis.css", __FILE__),
                [],
                true
            );

            WC()->session->set("1qlick_order", null);
            WC()->session->set("1qlick_status", null);
        }

        echo "" .
            # DEVONLY START QR Code and Status
            "<div id=\"oneqlick-qrcode-container\" class=\"$qr\">" .
            "<img id=\"oneqlick-logo\" src=\"" .
            plugins_url("1qlick.png", __FILE__) .
            "\" style=\"display: none\" />" .
            "<div id=\"oneqlick-qrcode-explanation\" class=\"oneqlick-status\">" .
            esc_html__("Scan QR code for ", "1qlick") .
            "<br />" .
            esc_html__("1Qlick Mobile Checkout", "1qlick") .
            "</div>" .
            "<div id=\"oneqlick-status-1qlick\"  style=\"display:none;\" class=\"oneqlick-status\">" .
            "<div class=\"oneqlick-status-spinner\"></div>" .
            esc_html__("1Qlick in progress", "1qlick") .
            "</div>" .
            "<div id=\"oneqlick-status-payment\" style=\"display:none;\" class=\"oneqlick-status\">" .
            "<div class=\"oneqlick-status-spinner\"></div>" .
            esc_html__("Payment in progress", "1qlick") .
            " </div>" .
            "<div id=\"oneqlick-status-qrcode\" class=\"oneqlick-status\"></div>" .
            "</div>" .
            # DEVONLY END QR Code and Status

            # DEVONLY START Checkout Button
            "<a href=\"" .
            esc_url(
                self::URLS[
                    WC_Admin_Settings::get_option("1qlick_mode", "production")
                ] .
                    "/shopper/checkout/" .
                    base64_encode(get_site_url()) .
                    "/" .
                    base64_encode(
                        $_COOKIE["wp_woocommerce_session_" . COOKIEHASH]
                    )
            ) .
            "\"" .
            " target=\"_self\" class=\"oneqlick-button $qr\" id=\"oneqlick-checkout-link\">" .
            str_replace(
                "%icon",
                "<img class=\"oneqlick-logo\" src=\"" .
                    plugins_url("1qlick.svg", __FILE__) .
                    "\" />",
                esc_html__("Checkout with %icon", "1qlick")
            ) .
            "</a>" .
            # DEVONLY END Checkout Button

            # DEVONLY START Explanation
            "<div id=\"what-is-oneqlick-button\">" .
            "<a href=\"#\">" .
            esc_html__("What is 1Qlick?", "1qlick") .
            "</a>" .
            "</div>" .
            "<div id=\"what-is-oneqlick-text\" style=\"display:none;\">" .
            "<div id=\"oneqlick-text-content\">" .
            "<span class=\"oneqlick-header\">" .
            esc_html__("What is 1Qlick?", "1qlick") .
            "</span>" .
            "<span class=\"oneqlick-subheader\">" .
            esc_html__("Enter details automatically,", "1qlick") .
            "<br />" .
            esc_html__("wherever you shop", "1qlick") .
            "</span>" .
            "<ul class=\"oneqlick-points\">" .
            "<li>" .
            esc_html__("Enter details once", "1qlick") .
            "</li>" .
            "<li>" .
            esc_html__("No more form filling from then on", "1qlick") .
            "</li>" .
            "<li>" .
            esc_html__("Save your payment and shipping preferences", "1qlick") .
            "</li>" .
            "<li>" .
            esc_html__("All your order history in one place", "1qlick") .
            "</li>" .
            "</ul>" .
            "</div>" .
            "</div>" .
            # DEVONLY END Explanation

            "";

        if ($basic_stats) {
            # TODO: include javascript
            wp_register_script(
                "basic_statistics_js",
                plugins_url("basicStatistics.js", __FILE__),
                [],
                true,
                true
            );
            wp_enqueue_script("basic_statistics_js");
        }
    }
}
