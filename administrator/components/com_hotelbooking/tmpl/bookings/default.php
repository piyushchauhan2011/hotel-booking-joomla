<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Bookings\HtmlView $this */

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&view=bookings'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-6">
			<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_HOTELBOOKING_FILTER_SEARCH_LABEL'); ?>" value="<?php echo htmlspecialchars($this->state->get('filter.search', '')); ?>">
			<button type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		</div>
	</div>

	<table class="table">
		<thead>
			<tr>
				<th><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_GUEST_NAME_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_ROOM_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKIN_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKOUT_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_STATUS_LABEL'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOTELBOOKING_FIELD_CREATED_LABEL', 'a.created', $listDirn, $listOrder); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
			<tr>
				<td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td>
					<a href="<?php echo Route::_('index.php?option=com_hotelbooking&task=booking.edit&id=' . (int) $item->id); ?>">
						<?php echo htmlspecialchars($item->guest_name); ?>
					</a>
					<br><small><?php echo htmlspecialchars($item->guest_email); ?></small>
				</td>
				<td><?php echo htmlspecialchars($item->room_name ?? ''); ?></td>
				<td><?php echo htmlspecialchars($item->checkin_date); ?></td>
				<td><?php echo htmlspecialchars($item->checkout_date); ?></td>
				<td><?php echo Text::_('COM_HOTELBOOKING_STATUS_' . strtoupper($item->status)); ?></td>
				<td><?php echo htmlspecialchars($item->created); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (empty($this->items)) : ?>
			<tr>
				<td colspan="7"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php echo $this->pagination->getListFooter(); ?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
