<?php

namespace BitApps\Social\Utils;

/**
 * Selects the media and the link that a Share Now post sends.
 *
 * The Link and Media checkboxes only hide the other field in the UI, so the
 * old value stays in the saved template. A value is used only when its
 * checkbox is on. A template without a flag is an old record that was saved
 * before the flags existed, so its saved values are used.
 *
 * Keep in step with BitApps\SocialPro\Utils\DirectShareAttachments.
 */
final class DirectShareAttachments
{
    /**
     * @param object $template
     *
     * @return array{media: null|array, link: null|string}
     */
    public static function resolve($template)
    {
        $useMedia = !empty($template->media) && ($template->isAllImages ?? true);
        $useLink = !empty($template->link) && ($template->isLinkCard ?? true);

        return [
            'media' => $useMedia ? array_column($template->media, 'url') : null,
            'link'  => $useLink ? $template->link : null,
        ];
    }
}
