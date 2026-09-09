<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Site\View\Destinations\HtmlView $this */

$search   = (string) $this->state->get('filter.search', '');
$itemsUrl = Route::_('index.php?option=com_hotelbooking&task=destinations.items');
?>
<div class="hotelbooking-destinations">
	<h1><?php echo Text::_('COM_HOTELBOOKING_ALL_DESTINATIONS'); ?></h1>

	<form method="get" action="<?php echo Route::_('index.php'); ?>" class="hb-search-form" role="search"
		hx-get="<?php echo $itemsUrl; ?>"
		hx-target="#hb-destination-results"
		hx-swap="innerHTML"
		hx-push-url="true"
		hx-sync="this:replace"
		hx-indicator="#hb-search-spinner"
		hx-trigger="submit, input delay:300ms from:input[name=search]">
		<input type="hidden" name="option" value="com_hotelbooking">
		<input type="hidden" name="view" value="destinations">
		<div class="hb-search-field">
			<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="hb-search-suggest-destinations" placeholder="<?php echo Text::_('COM_HOTELBOOKING_SEARCH_PLACEHOLDER'); ?>">
			<ul class="hb-search-suggestions" id="hb-search-suggest-destinations" role="listbox" hidden></ul>
		</div>
		<button type="submit"><?php echo Text::_('COM_HOTELBOOKING_SEARCH_BUTTON'); ?></button>
		<span id="hb-search-spinner" class="hb-spinner htmx-indicator" role="status">
			<span class="visually-hidden"><?php echo Text::_('COM_HOTELBOOKING_SEARCHING'); ?></span>
		</span>
	</form>

	<div id="hb-destination-results">
		<?php echo LayoutHelper::render('destination_items', [
			'items'      => $this->items,
			'search'     => $search,
			'limitstart' => (int) $this->state->get('list.start', 0),
			'limit'      => (int) $this->state->get('list.limit', 3),
			'total'      => (int) $this->pagination->total,
			'append'     => false,
		], JPATH_ROOT . '/components/com_hotelbooking/layouts'); ?>
	</div>

	<?php if (!empty($this->items)) : ?>
		<noscript>
			<?php echo $this->pagination->getListFooter(); ?>
		</noscript>
	<?php endif; ?>

	<?php if (!empty($this->faqs)) : ?>
		<h2><?php echo Text::_('COM_HOTELBOOKING_FAQS_HEADING'); ?></h2>
		<?php echo LayoutHelper::render('faqs', ['faqs' => $this->faqs], JPATH_ROOT . '/components/com_hotelbooking/layouts'); ?>
	<?php endif; ?>
</div>
