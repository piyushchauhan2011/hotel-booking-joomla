<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Rooms\HtmlView $this */

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&view=rooms'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-6">
			<input type="text" class="form-control" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_HOTELBOOKING_FILTER_SEARCH_LABEL'); ?>" value="<?php echo htmlspecialchars($this->state->get('filter.search', '')); ?>">
			<button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		</div>
	</div>

	<table class="table">
		<thead>
			<tr>
				<th><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOTELBOOKING_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_DESTINATION_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_PRICE_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_CAPACITY_LABEL'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
			<tr>
				<td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td><?php echo $item->published ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
				<td>
					<a href="<?php echo Route::_('index.php?option=com_hotelbooking&task=room.edit&id=' . (int) $item->id); ?>">
						<?php echo htmlspecialchars($item->name); ?>
					</a>
				</td>
				<td><?php echo htmlspecialchars($item->destination_name ?? ''); ?></td>
				<td><?php echo number_format((float) $item->price, 2); ?></td>
				<td><?php echo (int) $item->capacity; ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (empty($this->items)) : ?>
			<tr>
				<td colspan="6"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php echo $this->pagination->getListFooter(); ?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
