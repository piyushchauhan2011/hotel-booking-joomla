<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Rooms\HtmlView $this */

Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('modal-content-select');

$function  = Factory::getApplication()->getInput()->getCmd('function', 'jSelectRoom');
$multilang = Multilanguage::isEnabled();
?>
<div class="container-popup">
	<form action="<?php echo Route::_('index.php?option=com_hotelbooking&view=rooms&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-md-6">
				<input type="text" class="form-control" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_HOTELBOOKING_FILTER_SEARCH_LABEL'); ?>" value="<?php echo htmlspecialchars($this->state->get('filter.search', '')); ?>">
				<button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			</div>
		</div>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-info">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-sm">
				<thead>
					<tr>
						<th><?php echo Text::_('JSTATUS'); ?></th>
						<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_NAME_LABEL'); ?></th>
						<th><?php echo Text::_('COM_HOTELBOOKING_FIELD_DESTINATION_LABEL'); ?></th>
						<?php if ($multilang) : ?>
							<th><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($this->items as $item) : ?>
					<tr>
						<td><?php echo $item->published ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED'); ?></td>
						<th scope="row">
							<a class="select-link" href="javascript:void(0)"
								data-content-select
								data-id="<?php echo (int) $item->id; ?>"
								data-title="<?php echo htmlspecialchars($item->name); ?>"
								>
								<?php echo htmlspecialchars($item->name); ?>
							</a>
						</th>
						<td><?php echo htmlspecialchars($item->destination_name ?? ''); ?></td>
						<?php if ($multilang) : ?>
							<td><?php echo LayoutHelper::render('joomla.content.language', $item); ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php echo $this->pagination->getListFooter(); ?>
		<?php endif; ?>

		<?php echo $this->filterForm->renderControlFields(); ?>
	</form>
</div>
