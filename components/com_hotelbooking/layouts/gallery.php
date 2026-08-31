<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var array $displayData */

$images        = $displayData['images'] ?? [];
$features      = $displayData['features'] ?? [];
$featuresTitle = $displayData['featuresTitle'] ?? '';

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
			data-hb-gallery-description="<?php echo htmlspecialchars($image['description'] ?? ''); ?>"
			>
			<img src="<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars($image['caption'] ?? ''); ?>" loading="lazy">
		</button>
	<?php endforeach; ?>
</div>

<dialog class="hb-lightbox" data-hb-lightbox>
	<button type="button" class="hb-lightbox-close" data-hb-lightbox-close aria-label="<?php echo Text::_('COM_HOTELBOOKING_LIGHTBOX_CLOSE'); ?>">&times;</button>
	<button type="button" class="hb-lightbox-nav hb-lightbox-nav--prev" data-hb-lightbox-prev aria-label="<?php echo Text::_('COM_HOTELBOOKING_GALLERY_PREV'); ?>">&lsaquo;</button>
	<button type="button" class="hb-lightbox-nav hb-lightbox-nav--next" data-hb-lightbox-next aria-label="<?php echo Text::_('COM_HOTELBOOKING_GALLERY_NEXT'); ?>">&rsaquo;</button>

	<div class="hb-lightbox-body">
		<figure class="hb-lightbox-figure">
			<img data-hb-lightbox-image src="" alt="">
			<span class="hb-lightbox-counter" data-hb-lightbox-counter></span>
		</figure>

		<div class="hb-lightbox-info">
			<h3 class="hb-lightbox-caption" data-hb-lightbox-caption></h3>
			<p class="hb-lightbox-description" data-hb-lightbox-description></p>

			<?php if (!empty($features)) : ?>
				<div class="hb-lightbox-features">
					<h4><?php echo htmlspecialchars($featuresTitle); ?></h4>
					<ul class="hb-amenities hb-amenities--compact">
						<?php foreach ($features as $feature) : ?>
							<li class="hb-amenity">
								<svg class="hb-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12.5l5 5L20 6.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<?php echo Text::_('COM_HOTELBOOKING_AMENITY_' . strtoupper($feature)); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</dialog>
