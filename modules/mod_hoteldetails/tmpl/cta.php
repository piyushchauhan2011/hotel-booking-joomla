<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$sfx = $params->get('moduleclass_sfx') ? ' ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8') : '';

if (empty($destination)) {
	return;
}
?>
<div class="mod-hoteldetails mod-hoteldetails--cta<?php echo $sfx; ?>">
	<h3><?php echo Text::sprintf('MOD_HOTELDETAILS_CTA_HEADING', htmlspecialchars($destination->name, ENT_QUOTES, 'UTF-8')); ?></h3>
	<p><?php echo Text::_('MOD_HOTELDETAILS_CTA_BODY'); ?></p>
	<a class="hb-btn hb-btn--primary" href="#hotel-rooms"><?php echo Text::_('MOD_HOTELDETAILS_CTA_BUTTON'); ?></a>
</div>
