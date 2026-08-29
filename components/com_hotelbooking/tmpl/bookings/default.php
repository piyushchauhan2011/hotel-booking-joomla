<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Learn\Component\Hotelbooking\Site\View\Bookings\HtmlView $this */

if (empty($this->item)) :
	?>
	<p><?php echo Text::_('COM_HOTELBOOKING_BOOKING_NOT_FOUND'); ?></p>
	<?php
	return;
endif;
?>
<div class="hotelbooking-confirmation">
	<h1><?php echo Text::_('COM_HOTELBOOKING_BOOKING_CONFIRMED_TITLE'); ?></h1>
	<p>
		<?php
		echo htmlspecialchars(
			Text::sprintf(
				'COM_HOTELBOOKING_BOOKING_CONFIRMED_MESSAGE',
				$this->item->guest_name,
				$this->item->room_name ?? '',
				$this->item->checkin_date,
				$this->item->checkout_date,
				Text::_('COM_HOTELBOOKING_STATUS_' . strtoupper($this->item->status))
			)
		);
		?>
	</p>
</div>
