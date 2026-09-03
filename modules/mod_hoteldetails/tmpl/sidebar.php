<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Learn\Component\Hotelbooking\Site\Helper\DestinationContextHelper;

$sfx = $params->get('moduleclass_sfx') ? ' ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8') : '';

if (empty($destination)) {
	return;
}

$offers     = $destination->offers ?? [];
$amenities  = $destination->amenities ?? [];
$hasOffers  = !empty($offers);
?>
<div class="mod-hoteldetails mod-hoteldetails--sidebar<?php echo $sfx; ?>">
	<?php if ($hasOffers) : ?>
		<h3><?php echo Text::_('COM_HOTELBOOKING_OFFERS_TITLE'); ?></h3>
		<?php echo LayoutHelper::render('offers', ['offers' => $offers], JPATH_ROOT . '/components/com_hotelbooking/layouts'); ?>
	<?php elseif (!empty($amenities)) : ?>
		<h3><?php echo Text::_('MOD_HOTELDETAILS_GOOD_TO_KNOW'); ?></h3>
		<ul class="hb-amenities">
			<?php foreach ($amenities as $amenity) : ?>
				<li class="hb-amenity">
					<svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12.5l5 5L20 6.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php echo Text::_('COM_HOTELBOOKING_AMENITY_' . strtoupper($amenity)); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif (!empty($destination->description)) : ?>
		<h3><?php echo Text::_('MOD_HOTELDETAILS_GOOD_TO_KNOW'); ?></h3>
		<p><?php echo htmlspecialchars(DestinationContextHelper::plainExcerpt($destination->description, 220), ENT_QUOTES, 'UTF-8'); ?></p>
	<?php endif; ?>
</div>
