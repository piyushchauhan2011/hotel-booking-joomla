<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Learn\Component\Hotelbooking\Site\View\Hotel\HtmlView $this */

if ((int) $this->destinationId > 0) {
	return;
}
?>
<div class="hotelbooking-hotel-canvas">
	<p><?php echo Text::_('COM_HOTELBOOKING_HOTEL_CANVAS_NOTE'); ?></p>
</div>
