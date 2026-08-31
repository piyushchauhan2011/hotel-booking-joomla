<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $displayData */

$offers = $displayData['offers'] ?? [];

if (empty($offers)) {
    return;
}
?>
<div class="hb-offers">
	<?php foreach ($offers as $offer) : ?>
		<?php if (empty($offer['title'])) : continue; endif; ?>
		<div class="hb-offer-card">
			<?php if (!empty($offer['discount'])) : ?>
				<span class="hb-offer-badge"><?php echo htmlspecialchars($offer['discount']); ?></span>
			<?php endif; ?>
			<h3><?php echo htmlspecialchars($offer['title']); ?></h3>
			<?php if (!empty($offer['description'])) : ?>
				<p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
			<?php endif; ?>
			<?php if (!empty($offer['code'])) : ?>
				<p class="hb-offer-code"><?php echo Text::_('COM_HOTELBOOKING_OFFER_CODE_LABEL'); ?> <code><?php echo htmlspecialchars($offer['code']); ?></code></p>
			<?php endif; ?>
			<?php if (!empty($offer['valid_until'])) : ?>
				<p class="hb-offer-valid"><?php echo Text::_('COM_HOTELBOOKING_OFFER_VALID_UNTIL_LABEL'); ?> <?php echo htmlspecialchars(HTMLHelper::_('date', $offer['valid_until'], 'd M Y')); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
