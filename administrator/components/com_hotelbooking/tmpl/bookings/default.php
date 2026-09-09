<?php

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Bookings\HtmlView $this */

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');

if ($this->workflowEnabled) {
    $this->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_workflow');
    $this->getDocument()->getWebAssetManager()->useScript('com_workflow.admin-items-workflow-buttons');
}
?>
<form action="<?php echo Route::_('index.php?option=com_hotelbooking&view=bookings'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row mb-3">
		<div class="col-md-6">
			<input type="text" class="form-control" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_HOTELBOOKING_FILTER_SEARCH_LABEL'); ?>" value="<?php echo htmlspecialchars($this->state->get('filter.search', '')); ?>">
			<button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<?php if ($this->workflowEnabled && $this->filterForm) : ?>
				<?php echo $this->filterForm->renderField('stage', 'filter'); ?>
			<?php endif; ?>
		</div>
		<div class="col-md-6 text-md-end">
			<button type="button" class="btn btn-secondary" onclick="document.adminForm.task.value='bookings.exportCsv';document.adminForm.submit();">
				<?php echo Text::_('COM_HOTELBOOKING_EXPORT_CSV'); ?>
			</button>
		</div>
	</div>

	<table class="table itemList">
		<thead>
			<tr>
				<th><?php echo HTMLHelper::_('grid.checkall'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_GUEST_NAME_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_ROOM_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKIN_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_CHECKOUT_LABEL'); ?></th>
				<?php if ($this->workflowEnabled) : ?>
					<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_STAGE_LABEL'); ?></th>
				<?php endif; ?>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_STATUS_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_PARTNER_STATUS_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_TOTAL_PRICE_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_COMMISSION_AMOUNT_LABEL'); ?></th>
				<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_COMMISSION_PAID_LABEL'); ?></th>
				<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOTELBOOKING_FIELD_CREATED_LABEL', 'a.created', $listDirn, $listOrder); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
			<?php
            $dataTransitionsAttribute = '';

            if ($this->workflowEnabled && !empty($item->stage_id)) {
                $rowTransitions = BookingWorkflowHelper::filterTransitions(
                    $this->transitions,
                    (int) $item->stage_id,
                    (int) ($item->workflow_id ?? 0),
                );
                $transitionIds = ArrayHelper::toInteger(ArrayHelper::getColumn($rowTransitions, 'value'));

                if ($transitionIds !== []) {
                    $dataTransitionsAttribute = ' data-transitions="' . implode(',', $transitionIds) . '"';
                }
            }
            ?>
			<tr<?php echo $dataTransitionsAttribute; ?>>
				<td><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td>
					<a href="<?php echo Route::_('index.php?option=com_hotelbooking&task=booking.edit&id=' . (int) $item->id); ?>">
						<?php echo htmlspecialchars($item->guest_name); ?>
					</a>
					<br><small><?php echo htmlspecialchars($item->guest_email); ?></small>
				</td>
				<td>
					<?php echo htmlspecialchars($item->destination_name ?? ''); ?>
					<?php if (!empty($item->room_name)) : ?>
						<br><small><?php echo htmlspecialchars($item->room_name); ?></small>
					<?php endif; ?>
				</td>
				<td><?php echo htmlspecialchars($item->checkin_date); ?></td>
				<td><?php echo htmlspecialchars($item->checkout_date); ?></td>
				<?php if ($this->workflowEnabled) : ?>
					<td><?php echo htmlspecialchars(Text::_((string) ($item->stage_title ?? ''))); ?></td>
				<?php endif; ?>
				<td><?php echo Text::_('COM_HOTELBOOKING_STATUS_' . strtoupper($item->status)); ?></td>
				<td><?php echo Text::_('COM_HOTELBOOKING_PARTNER_STATUS_' . strtoupper($item->partner_status)); ?></td>
				<td><?php echo htmlspecialchars(number_format((float) $item->total_price, 2)); ?></td>
				<td><?php echo htmlspecialchars(number_format((float) $item->commission_amount, 2)); ?></td>
				<td><?php echo $item->commission_paid ? Text::_('JYES') : Text::_('JNO'); ?></td>
				<td><?php echo htmlspecialchars($item->created); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if (empty($this->items)) : ?>
			<tr>
				<td colspan="<?php echo $this->workflowEnabled ? '12' : '11'; ?>"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php echo $this->pagination->getListFooter(); ?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<input type="hidden" name="transition_id" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
