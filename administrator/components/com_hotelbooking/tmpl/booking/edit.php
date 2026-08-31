<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Booking\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->whatsappLink)) : ?>
		<div class="control-group">
			<a href="<?php echo htmlspecialchars($this->whatsappLink); ?>" target="_blank" rel="noopener" class="btn btn-success">
				<?php echo Text::_('COM_HOTELBOOKING_WHATSAPP_HOTEL'); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php foreach ($this->form->getFieldsets() as $fieldsetName => $fieldset) : ?>
		<fieldset class="adminform">
			<?php if (!empty($fieldset->label)) : ?>
				<legend><?php echo Text::_($fieldset->label); ?></legend>
			<?php endif; ?>
			<?php foreach ($this->form->getFieldset($fieldsetName) as $field) : ?>
				<div class="control-group">
					<?php echo $field->renderField(); ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
	<?php endforeach; ?>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
