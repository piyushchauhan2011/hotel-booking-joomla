<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $displayData */
$offer      = $displayData['offer'] ?? null;
$parentName = $displayData['parentName'] ?? '';
$link       = $displayData['link'] ?? '';

if (empty($offer) || empty($offer['title'])) {
    return;
}
?>
<div class="hb-snippet hb-snippet--offer hb-offer-card">
	<span class="hb-snippet-label"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_OFFER_LABEL'); ?></span>
	<?php if (!empty($offer['discount'])) : ?>
		<span class="hb-offer-badge"><?php echo htmlspecialchars($offer['discount']); ?></span>
	<?php endif; ?>
	<p class="hb-snippet-title"><?php echo htmlspecialchars($offer['title']); ?></p>
	<?php if (!empty($parentName)) : ?>
		<p class="hb-snippet-meta"><?php echo htmlspecialchars($parentName); ?></p>
	<?php endif; ?>
	<?php if (!empty($offer['description'])) : ?>
		<p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
	<?php endif; ?>
	<?php if (!empty($offer['code'])) : ?>
		<p class="hb-offer-code"><?php echo Text::_('COM_HOTELBOOKING_OFFER_CODE_LABEL'); ?> <code><?php echo htmlspecialchars($offer['code']); ?></code></p>
	<?php endif; ?>
	<?php if (!empty($offer['valid_until'])) : ?>
		<p class="hb-offer-valid"><?php echo Text::_('COM_HOTELBOOKING_OFFER_VALID_UNTIL_LABEL'); ?> <?php echo htmlspecialchars(HTMLHelper::_('date', $offer['valid_until'], 'd M Y')); ?></p>
	<?php endif; ?>
	<?php if (!empty($link)) : ?>
		<a class="hb-btn hb-btn--primary" href="<?php echo htmlspecialchars($link); ?>"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_CLAIM_OFFER'); ?></a>
	<?php endif; ?>
</div>
