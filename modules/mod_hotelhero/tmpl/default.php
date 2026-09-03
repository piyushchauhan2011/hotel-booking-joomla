<?php

\defined('_JEXEC') or die;

$modId = 'mod-hotelhero-' . (int) $module->id;
$style = $heroImage !== '' ? ' style="background-image: url(\'' . htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') . '\');"' : '';
?>
<div id="<?php echo $modId; ?>" class="mod-hotelhero<?php echo $params->get('moduleclass_sfx') ? ' ' . htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8') : ''; ?>"<?php echo $style; ?>>
	<div class="mod-hotelhero-overlay" aria-hidden="true"></div>
	<div class="mod-hotelhero-content">
		<?php if ($heroTitle !== '') : ?>
			<h2><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
		<?php endif; ?>
		<?php if ($heroSubtitle !== '') : ?>
			<p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php endif; ?>
		<?php if ($ctaLabel !== '' && $ctaUrl !== '') : ?>
			<a class="hb-btn hb-btn--primary" href="<?php echo htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8'); ?>">
				<?php echo htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8'); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
