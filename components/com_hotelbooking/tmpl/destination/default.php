<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Site\View\Destination\HtmlView $this */

if (empty($this->item)) :
	?>
	<p><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></p>
	<?php
	return;
endif;
?>
<div class="hotelbooking-destination">
	<h1><?php echo htmlspecialchars($this->item->name); ?></h1>

	<?php if (!empty($this->item->image)) : ?>
		<img class="hb-card-image" src="<?php echo htmlspecialchars($this->item->image); ?>" alt="<?php echo htmlspecialchars($this->item->name); ?>">
	<?php else : ?>
		<div class="hb-card-image hb-card-image--placeholder"></div>
	<?php endif; ?>

	<?php if (!empty($this->item->description)) : ?>
		<div class="hotelbooking-description"><?php echo nl2br(htmlspecialchars($this->item->description)); ?></div>
	<?php endif; ?>

	<h2><?php echo Text::_('COM_HOTELBOOKING_VIEW_ROOMS_HERE'); ?></h2>

	<?php if (empty($this->rooms)) : ?>
		<p><?php echo Text::_('COM_HOTELBOOKING_NO_ROOMS'); ?></p>
	<?php else : ?>
		<div class="hotelbooking-room-grid hb-grid">
			<?php foreach ($this->rooms as $room) : ?>
				<div class="hotelbooking-room-card hb-card">
					<?php if (!empty($room->image)) : ?>
						<img class="hb-card-image" src="<?php echo htmlspecialchars($room->image); ?>" alt="<?php echo htmlspecialchars($room->name); ?>">
					<?php else : ?>
						<div class="hb-card-image hb-card-image--placeholder"></div>
					<?php endif; ?>
					<div class="hb-card-body">
						<h3>
							<a href="<?php echo Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $room->id); ?>">
								<?php echo htmlspecialchars($room->name); ?>
							</a>
						</h3>
						<p class="hb-card-price"><svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.4 3H4a1 1 0 0 0-1 1v8.4a1 1 0 0 0 .3.7l9 9a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4l-9-9a1 1 0 0 0-.3-.2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="8" r="1.4" fill="currentColor"/></svg><?php echo number_format((float) $room->price, 2); ?> <?php echo Text::_('COM_HOTELBOOKING_PER_NIGHT'); ?></p>
						<p><svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><?php echo Text::_('COM_HOTELBOOKING_CAPACITY_LABEL'); ?>: <?php echo (int) $room->capacity; ?></p>
						<a class="hb-btn hb-btn--primary" href="<?php echo Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $room->id); ?>"><?php echo Text::_('COM_HOTELBOOKING_BOOK_NOW'); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
