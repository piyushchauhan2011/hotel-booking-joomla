<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Site\View\Destinations\HtmlView $this */
?>
<div class="hotelbooking-destinations">
	<h1><?php echo Text::_('COM_HOTELBOOKING_ALL_DESTINATIONS'); ?></h1>

	<form method="get" action="<?php echo Route::_('index.php'); ?>" class="hb-search-form" role="search">
		<input type="hidden" name="option" value="com_hotelbooking">
		<input type="hidden" name="view" value="destinations">
		<div class="hb-search-field">
			<input type="text" name="search" value="<?php echo htmlspecialchars($this->state->get('filter.search', '')); ?>" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="hb-search-suggest-destinations" placeholder="<?php echo Text::_('COM_HOTELBOOKING_SEARCH_PLACEHOLDER'); ?>">
			<ul class="hb-search-suggestions" id="hb-search-suggest-destinations" role="listbox" hidden></ul>
		</div>
		<button type="submit"><?php echo Text::_('COM_HOTELBOOKING_SEARCH_BUTTON'); ?></button>
	</form>

	<?php if (empty($this->items)) : ?>
		<p><svg class="hb-empty-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="2"/><line x1="14.5" y1="14.5" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><?php echo Text::_('COM_HOTELBOOKING_NO_DESTINATIONS'); ?></p>
	<?php else : ?>
		<div class="hotelbooking-destination-grid hb-grid">
			<?php foreach ($this->items as $destination) : ?>
				<div class="hotelbooking-destination-card hb-card">
					<?php if (!empty($destination->image)) : ?>
						<img class="hb-card-image" src="<?php echo htmlspecialchars($destination->image); ?>" alt="<?php echo htmlspecialchars($destination->name); ?>">
					<?php else : ?>
						<div class="hb-card-image hb-card-image--placeholder"></div>
					<?php endif; ?>
					<div class="hb-card-body">
						<h2>
							<a href="<?php echo Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $destination->id); ?>">
								<?php echo htmlspecialchars($destination->name); ?>
							</a>
						</h2>
						<?php if (!empty($destination->description)) :
							$teaser = trim(strtok(strip_tags($destination->description), "\n"));

							if (\function_exists('mb_strlen') && mb_strlen($teaser) > 160) {
								$teaser = mb_substr($teaser, 0, 160);
								$teaser = mb_substr($teaser, 0, mb_strrpos($teaser, ' ')) . '…';
							}
							?>
							<p><?php echo htmlspecialchars($teaser); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
</div>
