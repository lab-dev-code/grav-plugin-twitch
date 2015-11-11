<?php
/**
 * Twitch
 *
 * This plugin embeds Twitch streams from markdown
 *
 * Licensed under CC-BY, see LICENSE.
 */

namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

class TwitchPlugin extends Plugin
{
    const TWITCH_REGEX = '([a-zA-Z0-9][\w]{0,24})';

    /**
     * Return a list of subscribed events.
     *
     * @return array    The list of events of the plugin of the form
     *                      'name' => ['method_name', priority].
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Initialize configuration.
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0],
        ]);
    }

    /**
     * Add content after page content was read into the system.
     *
     * @param  Event  $event An event object, when `onPageContentRaw` is fired.
     */
    public function onPageContentRaw(Event $event)
    {
        /** @var Page $page */
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled')) {
            // Get raw content and substitute all formulas by a unique token
            $raw = $page->getRawContent();

            // build an anonymous function to pass to `parseLinks()`
            $function = function ($matches) use (&$config) {
                $search = $matches[0];

                // double check to make sure we found a valid Twitch channel name
                if (!isset($matches[1])) {
                    return $search;
                }
                $channel = $matches[1];

                // build the replacement embeded HTML string
                $player = '<iframe src="http://player.twitch.tv/?channel='
                    . $channel
                    . '" frameborder="'
                    . $config->get('player.properties.frameborder')
                    . '" scrolling="no" height="'
                    . $config->get('player.properties.height')
                    . '" width="'
                    . $config->get('player.properties.width')
                    . '" style="'
                    . $config->get('player.properties.style')
                    . '"></iframe>';

                $chat = '<iframe src="http://www.twitch.tv/'
                    . $channel
                    . '/chat" frameborder="'
                    . $config->get('chat.properties.frameborder')
                    . '" scrolling="no" height="'
                    . $config->get('chat.properties.height')
                    . '" width="'
                    . $config->get('chat.properties.width')
                    . '" style="'
                    . $config->get('chat.properties.style')
                    . '"></iframe>';

                $content = ($config->get('player.enabled') ? $player : '')
                    . ($config->get('chat.enabled') ? $chat : '');

                $replace = '<div style="overflow: auto; width: 100%" class="grav-twitch">' . $content . '</div>';

                // do the replacement
                return str_replace($search, $replace, $search);
            };

            // set the parsed content back into as raw content
            $page->setRawContent($this->parseLinks($raw, $function, $this::TWITCH_REGEX));
        }
    }
}
