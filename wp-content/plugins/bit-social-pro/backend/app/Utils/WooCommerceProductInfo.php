<?php

namespace BitApps\SocialPro\Utils;

class WooCommerceProductInfo
{
    public static function getWooCommerceInfo($key, $postId)
    {
        if (!\function_exists('wc_get_product')) {
            return '';
        }
        $product = wc_get_product($postId);

        if (empty($product)) {
            return '';
        }

        switch ($key) {
            case 'product_name':
                return $product->get_name();

                break;
            case 'product_description':
                return $product->get_description();

                break;
            case 'product_short_description':
                return $product->get_short_description();

                break;

            case 'product_price':
                return $product->get_price();

                break;
            case 'product_sale_price':
                return $product->get_sale_price();

                break;
            case 'product_regular_price':
                return $product->get_regular_price();

                break;
            case 'product_stock_quantity':
                return $product->get_stock_quantity();

                break;
            case 'product_weight':
                return $product->get_weight();

                break;
            case 'product_length':
                return $product->get_length();

                break;
            case 'product_width':
                return $product->get_width();

                break;
            case 'product_height':
                return $product->get_height();

                break;
            case 'product_dimensions':

                $dimensionsArray = $product->get_dimensions(false);
                $dimensions = wc_format_dimensions($dimensionsArray);

                return str_replace('&times;', 'X', $dimensions);

                break;
            case 'product_image_url':

                $imageId = $product->get_image_id();

                return wp_get_attachment_url($imageId);

                break;
        }
    }
}
