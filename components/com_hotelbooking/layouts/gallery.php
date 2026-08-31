<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */

$images = $displayData['images'] ?? [];

if (empty($images)) {
    return;
}
?>
<div class="hb-gallery">
	<?php foreach ($images as $image) : ?>
		<?php if (empty($image['image'])) : continue; endif; ?>
		<button
			type="button"
			class="hb-gallery-thumb"
			data-hb-gallery-full="<?php echo htmlspecialchars($image['image']); ?>"
			data-hb-gallery-caption="<?php echo htmlspecialchars($image['caption'] ?? ''); ?>"
			>
			<img src="<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars($image['caption'] ?? ''); ?>" loading="lazy">
		</button>
	<?php endforeach; ?>
</div>

<div class="hb-lightbox" data-hb-lightbox hidden>
	<button type="button" class="hb-lightbox-close" data-hb-lightbox-close aria-label="<?php echo Text::_('COM_HOTELBOOKING_LIGHTBOX_CLOSE'); ?>">&times;</button>
	<figure class="hb-lightbox-figure">
		<img data-hb-lightbox-image src="" alt="">
		<figcaption data-hb-lightbox-caption></figcaption>
	</figure>
</div>
