<?php
/**
 * @version 1.0
 *
 * @package LastTapEvents/inc/controller
 */

defined('ABSPATH') || exit;


class LastTap_WidgetController extends LastTap_BaseController
{
    public function lt_register()
    {
        if (!$this->lt_activated('media_widget')) return;

        $media_widget = new LastTap_MediaWidget();
        $media_widget->lt_register();
    }
}