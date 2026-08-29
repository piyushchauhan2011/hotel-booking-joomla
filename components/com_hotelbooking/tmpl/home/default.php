<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Site\View\Home\HtmlView $this */
?>
<div class="hotelbooking-home">
	<div class="hotelbooking-hero">
		<svg class="hotelbooking-hero-illustration" viewBox="0 0 1200 220" preserveAspectRatio="none" aria-hidden="true" focusable="false">
			<rect x="40" y="90" width="70" height="130" />
			<rect x="120" y="50" width="55" height="170" />
			<rect x="185" y="110" width="60" height="110" />
			<rect x="255" y="30" width="50" height="190" />
			<rect x="315" y="80" width="65" height="140" />
			<polygon points="60,90 75,60 90,90" />
			<circle cx="990" cy="55" r="34" />
			<rect x="850" y="100" width="60" height="120" />
			<rect x="920" y="60" width="55" height="160" />
			<rect x="985" y="120" width="70" height="100" />
			<rect x="1065" y="70" width="50" height="150" />
			<rect x="1125" y="105" width="55" height="115" />
		</svg>
		<div class="hotelbooking-hero-content">
			<h1><?php echo Text::_('COM_HOTELBOOKING_HOME_HERO_TITLE'); ?></h1>
			<p><?php echo Text::_('COM_HOTELBOOKING_HOME_HERO_SUBTITLE'); ?></p>

			<form method="get" action="<?php echo Route::_('index.php?option=com_hotelbooking&view=destinations'); ?>">
				<input type="text" name="search" placeholder="<?php echo Text::_('COM_HOTELBOOKING_SEARCH_PLACEHOLDER'); ?>">
				<button type="submit"><?php echo Text::_('COM_HOTELBOOKING_SEARCH_BUTTON'); ?></button>
			</form>
		</div>
	</div>

	<h2><?php echo Text::_('COM_HOTELBOOKING_FEATURED_DESTINATIONS'); ?></h2>

	<?php if (empty($this->destinations)) : ?>
		<p><svg class="hb-empty-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="2"/><line x1="14.5" y1="14.5" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><?php echo Text::_('COM_HOTELBOOKING_NO_DESTINATIONS'); ?></p>
	<?php else : ?>
		<div class="hotelbooking-destination-grid hb-grid">
			<?php foreach ($this->destinations as $destination) : ?>
				<div class="hotelbooking-destination-card hb-card">
					<?php if (!empty($destination->image)) : ?>
						<img class="hb-card-image" src="<?php echo htmlspecialchars($destination->image); ?>" alt="<?php echo htmlspecialchars($destination->name); ?>">
					<?php else : ?>
						<div class="hb-card-image hb-card-image--placeholder"></div>
					<?php endif; ?>
					<div class="hb-card-body">
						<h3>
							<a href="<?php echo Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $destination->id); ?>">
								<?php echo htmlspecialchars($destination->name); ?>
							</a>
						</h3>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
