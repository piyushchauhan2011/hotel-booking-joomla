<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */

$error = (string) ($displayData['error'] ?? '');
$item  = $displayData['item'] ?? null;

if ($error !== '') :
	?>
	<div class="hb-booking-error" role="alert">
		<p><?php echo htmlspecialchars($error); ?></p>
	</div>
	<?php
	return;
endif;

if (empty($item)) :
	?>
	<div class="hb-booking-error" role="alert">
		<p><?php echo Text::_('COM_HOTELBOOKING_BOOKING_NOT_FOUND'); ?></p>
	</div>
	<?php
	return;
endif;
?>
<div class="hotelbooking-confirmation hb-booking-result-success">
	<h2><?php echo Text::_('COM_HOTELBOOKING_BOOKING_CONFIRMED_TITLE'); ?></h2>
	<p>
		<?php
		echo htmlspecialchars(
			Text::sprintf(
				'COM_HOTELBOOKING_BOOKING_CONFIRMED_MESSAGE',
				$item->guest_name,
				$item->room_name ?? '',
				$item->checkin_date,
				$item->checkout_date,
				Text::_('COM_HOTELBOOKING_STATUS_' . strtoupper($item->status))
			)
		);
		?>
	</p>
</div>
