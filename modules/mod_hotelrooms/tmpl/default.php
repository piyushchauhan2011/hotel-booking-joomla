<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$sfx = $params->get('moduleclass_sfx') ? ' ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8') : '';
?>
<div class="mod-hotelrooms<?php echo $sfx; ?>">
	<?php if ($module->showtitle) : ?>
		<h2><?php echo htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8'); ?></h2>
	<?php endif; ?>

	<?php if (empty($rooms)) : ?>
		<p><?php echo $emptyMessage; ?></p>
	<?php else : ?>
		<div class="hotelbooking-room-grid hb-grid">
			<?php foreach ($rooms as $room) : ?>
				<div class="hotelbooking-room-card hb-card">
					<?php if (!empty($room->image)) : ?>
						<img class="hb-card-image" src="<?php echo htmlspecialchars($room->image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($room->name, ENT_QUOTES, 'UTF-8'); ?>">
					<?php else : ?>
						<div class="hb-card-image hb-card-image--placeholder"></div>
					<?php endif; ?>
					<div class="hb-card-body">
						<h3>
							<a href="<?php echo Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $room->id); ?>">
								<?php echo htmlspecialchars($room->name, ENT_QUOTES, 'UTF-8'); ?>
							</a>
						</h3>
						<p class="hb-card-price"><?php echo number_format((float) $room->price, 2); ?> <?php echo Text::_('COM_HOTELBOOKING_PER_NIGHT'); ?></p>
						<p><?php echo Text::_('COM_HOTELBOOKING_CAPACITY_LABEL'); ?>: <?php echo (int) $room->capacity; ?></p>
						<a class="hb-btn hb-btn--primary" href="<?php echo Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $room->id); ?>"><?php echo Text::_('COM_HOTELBOOKING_BOOK_NOW'); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
