<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Snippets\HtmlView $this */

Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

$editor = $this->editor;
$type   = (string) $this->state->get('filter.type', 'destination');
$search = (string) $this->state->get('filter.search', '');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('com_hotelbooking.admin-snippets-modal')
	->useScript('com_hotelbooking.admin-snippets-modal');
$this->getDocument()->addScriptOptions('xtd-hotelbooking', ['editor' => $editor]);

$formAction = Route::_(
	'index.php?option=com_hotelbooking&view=snippets&layout=modal&tmpl=component&editor='
	. urlencode($editor) . '&' . Session::getFormToken() . '=1'
);

$tabs = [
	'destination' => Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_DESTINATIONS'),
	'room'        => Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_ROOMS'),
	'offer'       => Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_OFFERS'),
];

$destinationField = $this->filterForm ? $this->filterForm->getField('destination_id', 'filter') : false;
$entityField      = $this->filterForm ? $this->filterForm->getField('entity', 'filter') : false;
$limitField       = $this->filterForm ? $this->filterForm->getField('limit', 'list') : false;
?>
<div class="container-popup hb-snippets-modal">
	<form action="<?php echo $formAction; ?>" method="post" name="adminForm" id="adminForm">
		<ul class="nav nav-tabs" role="tablist">
			<?php foreach ($tabs as $tabType => $tabLabel) : ?>
				<li class="nav-item" role="presentation">
					<a class="nav-link<?php echo $type === $tabType ? ' active' : ''; ?>"
						href="<?php echo $this->getTabUrl($tabType); ?>"
						role="tab"
						aria-selected="<?php echo $type === $tabType ? 'true' : 'false'; ?>">
						<?php echo $tabLabel; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<div class="hb-snippets-toolbar">
			<div class="hb-snippets-search">
				<div class="input-group">
					<label class="visually-hidden" for="filter_search"><?php echo Text::_('COM_HOTELBOOKING_FILTER_SEARCH_LABEL'); ?></label>
					<input type="text" class="form-control" name="filter_search" id="filter_search"
						placeholder="<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_SEARCH_HINT'); ?>"
						value="<?php echo htmlspecialchars($search); ?>">
					<button type="submit" class="btn btn-primary"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
					<button type="submit" class="btn btn-secondary" data-hb-snippets-clear><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></button>
				</div>
			</div>
			<?php if ($type === 'room' && $destinationField) : ?>
				<div class="hb-snippets-filter">
					<label class="visually-hidden" for="<?php echo $destinationField->id; ?>"><?php echo $destinationField->title; ?></label>
					<?php echo $destinationField->input; ?>
				</div>
			<?php endif; ?>
			<?php if ($type === 'offer' && $entityField) : ?>
				<div class="hb-snippets-filter">
					<label class="visually-hidden" for="<?php echo $entityField->id; ?>"><?php echo $entityField->title; ?></label>
					<?php echo $entityField->input; ?>
				</div>
			<?php endif; ?>
			<?php if ($limitField) : ?>
				<div class="hb-snippets-limit">
					<label class="visually-hidden" for="<?php echo $limitField->id; ?>"><?php echo $limitField->title; ?></label>
					<?php echo $limitField->input; ?>
				</div>
			<?php endif; ?>
		</div>

		<input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($type); ?>">

		<div class="hb-snippets-results">
			<?php if (empty($this->items)) : ?>
				<div class="alert alert-info">
					<span class="icon-info-circle" aria-hidden="true"></span>
					<?php echo $this->getEmptyMessage(); ?>
				</div>
			<?php else : ?>
				<table class="table table-sm">
					<tbody>
						<?php if ($type === 'destination') : ?>
							<?php foreach ($this->items as $destination) : ?>
								<tr>
									<td><?php echo htmlspecialchars($destination->name); ?></td>
									<td class="text-end">
										<a class="btn btn-sm btn-primary hb-select-link" href="#"
											data-type="destination" data-id="<?php echo (int) $destination->id; ?>">
											<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php elseif ($type === 'room') : ?>
							<?php foreach ($this->items as $room) : ?>
								<tr>
									<td>
										<?php echo htmlspecialchars($room->name); ?>
										<?php if (!empty($room->destination_name)) : ?>
											<div class="small text-muted"><?php echo htmlspecialchars($room->destination_name); ?></div>
										<?php endif; ?>
									</td>
									<td class="text-end">
										<a class="btn btn-sm btn-primary hb-select-link" href="#"
											data-type="room" data-id="<?php echo (int) $room->id; ?>">
											<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<?php foreach ($this->items as $offer) : ?>
								<tr>
									<td>
										<?php echo htmlspecialchars($offer->title); ?>
										<?php if (!empty($offer->discount)) : ?>
											<span class="badge bg-success"><?php echo htmlspecialchars($offer->discount); ?></span>
										<?php endif; ?>
										<div class="small text-muted"><?php echo htmlspecialchars($offer->parent_name); ?></div>
									</td>
									<td class="text-end">
										<a class="btn btn-sm btn-primary hb-select-link" href="#"
											data-type="offer" data-id="<?php echo (int) $offer->id; ?>"
											data-entity="<?php echo htmlspecialchars($offer->entity); ?>"
											data-index="<?php echo (int) $offer->index; ?>">
											<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<?php echo $this->pagination->getListFooter(); ?>
			<?php endif; ?>
		</div>

		<?php if ($this->filterForm) : ?>
			<?php echo $this->filterForm->renderControlFields(); ?>
		<?php endif; ?>
	</form>
</div>
