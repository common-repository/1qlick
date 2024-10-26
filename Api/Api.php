<?php

namespace OneQlick\Api;

use Automattic\WooCommerce\RestApi\Utilities\SingletonTrait;
use WC_Admin_Settings;
use WC_Checkout;
use WC_Order_Factory;
use WC_Payment_Gateway;
use WC_Shipping_Rate;

if (!defined( "ABSPATH")) {
    exit; // Exit if accessed directly
}

class Api
{
    use SingletonTrait;
    private $version;

    public function hook(string $version)
    {
        $this->version = $version;
        add_action("woocommerce_api_1qlick_session", [$this, "session"]);
        add_action("woocommerce_api_1qlick_order", [$this, "order"]);
        add_action("woocommerce_api_1qlick_status", [$this, "status"]);
    }

    private function security()
    {
        if (!isset($_SERVER["HTTP_SECURITY"])) {
            wp_die("Security check did not pass");
        }

        $cert = <<<EOF
-----BEGIN CERTIFICATE-----
MIICdjCCAd8CFAKUie0lV4kTsgztFsAI+O7TttBMMA0GCSqGSIb3DQEBCwUAMHox
CzAJBgNVBAYTAk5MMRMwEQYDVQQIDApTb21lLVN0YXRlMRAwDgYDVQQHDAdBbGtt
YWFyMQ8wDQYDVQQKDAYxUWxpY2sxEzARBgNVBAMMCjFxbGljay5jb20xHjAcBgkq
hkiG9w0BCQEWD3RlY2hAMXFsaWNrLmNvbTAeFw0yMjAyMTQxNTQzMjlaFw0zMjAy
MTIxNTQzMjlaMHoxCzAJBgNVBAYTAk5MMRMwEQYDVQQIDApTb21lLVN0YXRlMRAw
DgYDVQQHDAdBbGttYWFyMQ8wDQYDVQQKDAYxUWxpY2sxEzARBgNVBAMMCjFxbGlj
ay5jb20xHjAcBgkqhkiG9w0BCQEWD3RlY2hAMXFsaWNrLmNvbTCBnzANBgkqhkiG
9w0BAQEFAAOBjQAwgYkCgYEAuLHb1zMp2Lp1cUnyA+jees2pPuS0pk6JkTA9Ihg/
kuQCvaCzisFzE6MM65MQEnspJ9ZACNljVEd3VW1apFkTGoS/qvtb7g1F1zwKnMBG
GZaDx0Hs5F0QIuOFS3+S88dXiVziL0cBEjzL81F/lZoPPlYLuheOMmctrRBV8LrB
G7cCAwEAATANBgkqhkiG9w0BAQsFAAOBgQAJfaYw04zZAUSKxHaL/Ybs4FYUBZ0I
h99C8sIUGm5kG85V2THpeTIj76L6uIvBth0hz+3/Y2tNwqUxK4gJ91UgbZhOglKy
WmsvI0GiaiQYzEJx/ocDabghabco1BSYVi624IqTgU0p+eK6sjjW02sie9ehyfbE
eHKWSu56lJPPlw==
-----END CERTIFICATE-----
EOF;

        if (openssl_verify($_COOKIE["wp_woocommerce_session_" . COOKIEHASH], hex2bin($_SERVER["HTTP_SECURITY"]), $cert, OPENSSL_ALGO_SHA512) !== 1) {
            wp_die("Security check did not pass");
        }
    }

    private function headers()
    {
        global $wp_version;
        header("Content-Type: application/json; charset=" . get_option("blog_charset")); #Borrowed from wp_send_json, but required because we send headers ourselves
        header("X-1QLICK-PLUGIN-VERSION: " . $this->version);
        header("X-1QLICK-PHP-VERSION: " . phpversion());
        header("X-1QLICK-WORDPRESS-VERSION: " . $wp_version);
        header("X-1QLICK-WOOCOMMERCE-VERSION: " . WC_VERSION);
    }

    #/wc-api/1qlick_session
    public function session()
    {
        try {
            $this->security();

            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $this->headers();
                $this->sessionGet();
            } else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
                $this->headers();
                $this->sessionPut();
            } else {
                wp_die("Method not supported", "Method not supported", 405);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function sessionGet()
    {
        WC()->session->set("1qlick_status", "1qlick");

        wp_send_json([
            "result" => "succes",
            "cart" => $this->getCart(),
            "shippingMethods" => $this->getShippingMethods(),
            "paymentMethods" => $this->getPaymentMethods()
        ]);
    }

    private function sessionPut()
    {
        //Inspired by class-wc-ajax::update_order_review
        //Sets shipping and billing to the same location, we just want to update payment/shipment options
        $data = json_decode(file_get_contents("php://input"),true);
        WC()->customer->set_props(
            [
                "billing_country"   => sanitize_text_field($data["country"]),
                "billing_postcode"  => sanitize_text_field($data["postcode"]),
                "shipping_country"  => sanitize_text_field($data["country"]),
                "shipping_postcode" => sanitize_text_field($data["postcode"])
            ]
        );

        $this->sessionGet();
    }

    private function getShippingMethods(): array
    {
        $shipmentOptions = WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
        $shipments = [];
        /** @var WC_Shipping_Rate $shipmentOption */
        foreach ($shipmentOptions[0]["rates"] as $shipmentOption) {
            $shipments[] = [
                "id" => $shipmentOption->get_id(),
                "label" => $shipmentOption->get_label(),
                "cost" => $shipmentOption->get_cost()
            ];
        }
        
        return $shipments;
    }

    private function getPaymentMethods(): array
    {
        $result = [];
        /** @var WC_Payment_Gateway $method */
        foreach (WC()->payment_gateways()->get_available_payment_gateways() as $method) {
            $key = false;
            $values = [];
            $fields = [];
            if (class_exists("Mollie_WC_Plugin", false) && $method instanceof \Mollie_WC_Gateway_Abstract) {
                $key = \Mollie_WC_Plugin::PLUGIN_ID . '_issuer_' . $method->id;
                $options = \Mollie_WC_Plugin::getDataHelper()->getMethodIssuers(
                    \Mollie_WC_Plugin::getSettingsHelper()->isTestModeEnabled(),
                    $method->getMollieMethodId());
                if ($options) {
                    foreach ($options as $option) {
                        $values[] = [
                            "id" => $option->id,
                            "label" => $option->name,
                            "icon" => $option->image->svg
                        ];
                    }
                }
            }

            if ($key) {
                $fields = [
                    "key" => $key,
                    "options" => $values
                ];
            }

            $result[] = [
                "id" => $method->id,
                "label" => $method->get_title(),
                "icon" => preg_replace('/.*src=["\'](.*?)["\'].*/',"$1",$method->get_icon()),
                "cost" => (isset($method->settings) && isset($method->settings["payment_surcharge"])
                    && $method->settings["payment_surcharge"] !== "no_fee") ? $method->settings["payment_surcharge"] : 0,
                "fields" => $fields
            ];
        }
        return $result;
    }

    private function getCart(): array
    {
        $cart = [];
        foreach (WC()->cart->get_cart() as $cartItemKey => $cartItem) {
            $product = apply_filters("woocommerce_cart_item_product", $cartItem["data"], $cartItem, $cartItemKey);

            if ($product && $product->exists() && $cartItem["quantity"] > 0
                && apply_filters("woocommerce_checkout_cart_item_visible", true, $cartItem, $cartItemKey)) {

                $imageId = $product->get_image_id();
                if (empty($imageId)) {
                    $imageId = get_option( "woocommerce_placeholder_image", 0 );
                }

                $cart[] = [
                    "id" => $cartItem["product_id"],
                    "name" => $product->get_name(),
                    "quantity" => $cartItem["quantity"],
                    "short_description" => $product->get_short_description(),
                    "price" => ($cartItem["line_total"] + $cartItem["line_tax"]) / $cartItem["quantity"],
                    "images" => [
                        "src" => current(wp_get_attachment_image_src($imageId))
                    ]
                ];
            }
        }

        return $cart;
    }

    #/wc-api/order
    public function order()
    {
        try {
            $this->security();

            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $this->orderGet();
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $this->headers();
                $this->orderPost();
            } else {
                wp_die("Method not supported", "Method not supported", 405);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function orderGet()
    {
        $order = WC_Order_Factory::get_order(sanitize_text_field($_GET["order_id"]));
        if ($order === false) {
            wp_die("Order not found", "Order not found", 404);
        }

        $this->headers();
        wp_send_json([
            "orderId" => sanitize_text_field($_GET["order_id"]),
            "status" => $order->get_status()
        ]);
    }

    private function orderPost()
    {
        wc_clear_notices();
        //Fake a valid nonce
        $_REQUEST["woocommerce-process-checkout-nonce"] = wp_create_nonce("woocommerce-process_checkout");

        $data = json_decode(file_get_contents("php://input"),true);

        $splitAddress = false;
        if (class_exists("WPO_WC_Postcode_Checker")) {
            $splitAddress = true;
        }

        $_POST["billing_first_name"] = sanitize_text_field($data["billing"]["firstName"]);
        $_POST["billing_last_name"] = sanitize_text_field($data["billing"]["lastName"]);
        $_POST["billing_country"] = sanitize_text_field($data["billing"]["country"]);
        $_POST["billing_postcode"] = sanitize_text_field($data["billing"]["postcode"]);
        $_POST["billing_city"] = sanitize_text_field($data["billing"]["city"]);
        $_POST["billing_email"] = sanitize_email($data["billing"]["email"]);
        $_POST["billing_phone"] = sanitize_text_field($data["billing"]["phone"]);

        if ($splitAddress) {
            $_POST["billing_street_name"] = sanitize_text_field($data["billing"]["streetName"]);
            $_POST["billing_house_number"] = sanitize_text_field($data["billing"]["houseNumber"]);
        } else {
            $_POST["billing_address_1"] = sanitize_text_field($data["billing"]["streetName"] . " " . $data["billing"]["houseNumber"]);
        }

        if (! empty($data["shipping"])) {
            $_POST["ship_to_different_address"] = true;
            $_POST["shipping_first_name"] = sanitize_text_field($data["shipping"]["firstName"]);
            $_POST["shipping_last_name"] = sanitize_text_field($data["shipping"]["lastName"]);
            $_POST["shipping_country"] = sanitize_text_field($data["shipping"]["country"]);
            $_POST["shipping_postcode"] = sanitize_text_field($data["shipping"]["postcode"]);
            $_POST["shipping_city"] = sanitize_text_field($data["shipping"]["city"]);
            $_POST["shipping_email"] = sanitize_text_field($data["shipping"]["email"]);
            $_POST["shipping_phone"] = sanitize_text_field($data["shipping"]["phone"]);

            if ($splitAddress) {
                $_POST["shipping_street_name"] = sanitize_text_field($data["shipping"]["streetName"]);
                $_POST["shipping_house_number"] = sanitize_text_field($data["shipping"]["houseNumber"]);
            } else {
                $_POST["shipping_address_1"] = sanitize_text_field($data["shipping"]["streetName"] . " " . $data["shipping"]["houseNumber"]);
            }
        }

        $_POST["payment_method"] = sanitize_text_field($data["payment"]);
        $_POST["shipping_method"] = sanitize_text_field($data["shipment"]);
        WC()->session->set("chosen_shipping_methods", [sanitize_text_field($data["shipment"])]);

        foreach ($data["additional"] as $additional) {
            $_POST[sanitize_text_field($additional["key"])] = sanitize_text_field($additional["value"]);
        }


        add_action("woocommerce_checkout_order_processed", function ($order_id, $posted_data, $order) {
            WC()->session->set("1qlick_status", "payment");
            WC()->session->set("1qlick_order", $order_id);
        }, 10, 3);

        define("DOING_AJAX", true); // Fake ajax to get a result we can actually use instead of being redirected
        WC_Checkout::instance()->process_checkout();
    }

    private function statusGet()
    {
        $orderId = WC()->session->get("1qlick_order");
        if ($orderId) { //Order went to payment, check its status
            $order = wc_get_order($orderId);
            if ($order->get_status() == "processing") {
                WC()->session->set("1qlick_order", null);
                WC()->session->set("1qlick_status", null);
                wp_send_json([
                    "status" => "paid",
                    "redirect" => $order->get_checkout_order_received_url()
                ]);
            }
        }

        wp_send_json([
            "status" => WC()->session->get("1qlick_status", "unset")
        ]);
    }

    #/wc-api/status
    public function status()
    {
        try {
            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $this->statusGet();
            } else {
                wp_die("Method not supported", "Method not supported", 405);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Exception $exception)
    {
        $this->headers();
        wp_send_json([
            "result" => "exception",
            "message" => $exception->getMessage(),
            "stack" => $exception->getTrace()
        ]);
    }
}
