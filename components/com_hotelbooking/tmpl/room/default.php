<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Site\View\Room\HtmlView $this */

if (empty($this->item)) :
	?>
	<p><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></p>
	<?php
	return;
endif;

$itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
?>
<div class="hotelbooking-room">
	<h1><?php echo htmlspecialchars($this->item->name); ?></h1>

	<p class="hotelbooking-room-destination-link">
		<a href="<?php echo Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $this->item->destination_id); ?>">
			&larr; <?php echo htmlspecialchars($this->item->destination_name ?? ''); ?>
		</a>
	</p>

	<div class="hotelbooking-room-main">
		<?php if (!empty($this->item->image)) : ?>
			<img class="hb-card-image" src="<?php echo htmlspecialchars($this->item->image); ?>" alt="<?php echo htmlspecialchars($this->item->name); ?>">
		<?php else : ?>
			<div class="hb-card-image hb-card-image--placeholder"></div>
		<?php endif; ?>

		<?php if (!empty($this->item->description)) : ?>
			<div class="hotelbooking-description"><?php echo nl2br(htmlspecialchars($this->item->description)); ?></div>
		<?php endif; ?>
	</div>

	<aside class="hotelbooking-room-sidebar">
		<div class="hotelbooking-room-meta">
			<p class="hb-card-price"><svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.4 3H4a1 1 0 0 0-1 1v8.4a1 1 0 0 0 .3.7l9 9a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4l-9-9a1 1 0 0 0-.3-.2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="8" r="1.4" fill="currentColor"/></svg><?php echo number_format((float) $this->item->price, 2); ?> <?php echo Text::_('COM_HOTELBOOKING_PER_NIGHT'); ?></p>
			<p><svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php echo Text::_('COM_HOTELBOOKING_CAPACITY_LABEL'); ?>: <?php echo (int) $this->item->capacity; ?></p>
		</div>

		<h2><?php echo Text::_('COM_HOTELBOOKING_BOOKING_FORM_TITLE'); ?></h2>

		<form method="post" action="<?php echo Route::_('index.php?option=com_hotelbooking&task=booking.submit'); ?>">
			<div class="hb-field">
				<label for="checkin_date"><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKIN_LABEL'); ?></label>
				<input type="date" name="checkin_date" id="checkin_date" required>
			</div>
			<div class="hb-field">
				<label for="checkout_date"><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKOUT_LABEL'); ?></label>
				<input type="date" name="checkout_date" id="checkout_date" required>
			</div>
			<div class="hb-field">
				<label for="guests"><?php echo Text::_('COM_HOTELBOOKING_FIELD_GUESTS_LABEL'); ?></label>
				<input type="number" name="guests" id="guests" value="1" min="1" max="<?php echo (int) $this->item->capacity; ?>" required>
			</div>
			<div class="hb-field">
				<label for="guest_name"><?php echo Text::_('COM_HOTELBOOKING_FIELD_GUEST_NAME_LABEL'); ?></label>
				<input type="text" name="guest_name" id="guest_name" required>
			</div>
			<div class="hb-field hb-field--full">
				<label for="guest_email"><?php echo Text::_('COM_HOTELBOOKING_FIELD_GUEST_EMAIL_LABEL'); ?></label>
				<input type="email" name="guest_email" id="guest_email" required>
			</div>

			<input type="hidden" name="room_id" value="<?php echo (int) $this->item->id; ?>">
			<input type="hidden" name="Itemid" value="<?php echo $itemId; ?>">
			<?php echo HTMLHelper::_('form.token'); ?>

			<button type="submit" class="hb-btn hb-btn--primary"><?php echo Text::_('COM_HOTELBOOKING_SUBMIT_BOOKING'); ?></button>
		</form>
	</aside>
</div>
