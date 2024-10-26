<?php

namespace OneQlick\Admin;

class Tab
{
    public static function hook()
    {
        add_filter("woocommerce_settings_tabs_array", function () {
            return ["1qlick_settings" => __("1Qlick", "1Qlick")];
        });
    }
}
