<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var array $displayData */
$room = $displayData['room'] ?? null;

if (empty($room)) {
    return;
}

$link = Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $room->id);
?>
<div class="hb-snippet hb-snippet--room hb-card">
	<?php if (!empty($room->image)) : ?>
		<img class="hb-card-image" src="<?php echo htmlspecialchars($room->image); ?>" alt="<?php echo htmlspecialchars($room->name); ?>">
	<?php else : ?>
		<div class="hb-card-image hb-card-image--placeholder"></div>
	<?php endif; ?>
	<div class="hb-card-body">
		<span class="hb-snippet-label"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_ROOM_LABEL'); ?></span>
		<p class="hb-snippet-title"><a href="<?php echo $link; ?>"><?php echo htmlspecialchars($room->name); ?></a></p>
		<?php if (!empty($room->destination_name)) : ?>
			<p class="hb-snippet-meta"><?php echo htmlspecialchars($room->destination_name); ?></p>
		<?php endif; ?>
		<p class="hb-card-price"><?php echo number_format((float) $room->price, 2); ?> <?php echo Text::_('COM_HOTELBOOKING_PER_NIGHT'); ?></p>
		<a class="hb-btn hb-btn--primary" href="<?php echo $link; ?>"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_VIEW_ROOM'); ?></a>
	</div>
</div>
