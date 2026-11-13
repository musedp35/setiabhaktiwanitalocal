<?php

namespace BitApps\SocialPro\Utils;

class ProSmartTagHandler
{
    public static function getProSmartTagValue($key, $postId)
    {
        preg_match('/custom_field_\[([^\]]+)\]/', $key, $matches);

        if (isset($matches[1])) {
            $customFieldKey = $matches[1];

            $customFieldValue = get_post_meta($postId, $customFieldKey, true);

            if (\is_array($customFieldValue) || \is_object($customFieldValue)) {
                $customFieldValue = implode(',', (array) $customFieldValue);
            }

            return $customFieldValue;
        }

        return (new WooCommerceProductInfo())->getWooCommerceInfo($key, $postId);
    }
}
