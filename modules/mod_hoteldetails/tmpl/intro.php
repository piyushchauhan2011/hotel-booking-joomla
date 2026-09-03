<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$sfx = $params->get('moduleclass_sfx') ? ' ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8') : '';

if (empty($destination)) {
	return;
}
?>
<div class="mod-hoteldetails mod-hoteldetails--intro<?php echo $sfx; ?>">
	<?php if (!empty($destination->description)) : ?>
		<div class="hotelbooking-description"><?php echo $destination->description; ?></div>
	<?php endif; ?>

	<?php if (!empty($destination->amenities)) : ?>
		<h3><?php echo Text::_('COM_HOTELBOOKING_HOTEL_AMENITIES_TITLE'); ?></h3>
		<ul class="hb-amenities">
			<?php foreach ($destination->amenities as $amenity) : ?>
				<li class="hb-amenity">
					<svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12.5l5 5L20 6.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php echo Text::_('COM_HOTELBOOKING_AMENITY_' . strtoupper($amenity)); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
