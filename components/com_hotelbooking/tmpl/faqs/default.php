<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Learn\Component\Hotelbooking\Site\View\Faqs\HtmlView $this */
?>
<div class="hotelbooking-faqs">
	<h1><?php echo Text::_('COM_HOTELBOOKING_FAQS_PAGE_TITLE'); ?></h1>

	<?php if (empty($this->items)) : ?>
		<p><?php echo Text::_('COM_HOTELBOOKING_NO_FAQS'); ?></p>
	<?php else : ?>
		<?php echo LayoutHelper::render('faqs', ['faqs' => $this->items], JPATH_ROOT . '/components/com_hotelbooking/layouts'); ?>
	<?php endif; ?>
</div>
