<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Room\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm">
	<?php foreach ($this->form->getFieldset('basic') as $field) : ?>
		<div class="control-group">
			<?php echo $field->renderField(); ?>
		</div>
	<?php endforeach; ?>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
