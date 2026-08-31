<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/** @var \Learn\Component\Hotelbooking\Administrator\View\Snippets\HtmlView $this */

$app = Factory::getApplication();

Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

$editor = $app->getInput()->getCmd('editor', '');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('com_hotelbooking.admin-snippets-modal');
$this->getDocument()->addScriptOptions('xtd-hotelbooking', ['editor' => $editor]);
?>
<div class="container-popup">
	<?php if (empty($this->destinations) && empty($this->rooms) && empty($this->offers)) : ?>
		<div class="alert alert-info">
			<span class="icon-info-circle" aria-hidden="true"></span>
			<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_EMPTY'); ?>
		</div>
	<?php else : ?>
		<?php if (!empty($this->destinations)) : ?>
			<h3><?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_DESTINATIONS'); ?></h3>
			<table class="table table-sm">
				<tbody>
					<?php foreach ($this->destinations as $destination) : ?>
						<tr>
							<td><?php echo htmlspecialchars($destination->name); ?></td>
							<td class="text-end">
								<a class="btn btn-sm btn-primary hb-select-link" href="javascript:void(0)"
									data-type="destination" data-id="<?php echo (int) $destination->id; ?>">
									<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if (!empty($this->rooms)) : ?>
			<h3><?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_ROOMS'); ?></h3>
			<table class="table table-sm">
				<tbody>
					<?php foreach ($this->rooms as $room) : ?>
						<tr>
							<td>
								<?php echo htmlspecialchars($room->name); ?>
								<?php if (!empty($room->destination_name)) : ?>
									<div class="small text-muted"><?php echo htmlspecialchars($room->destination_name); ?></div>
								<?php endif; ?>
							</td>
							<td class="text-end">
								<a class="btn btn-sm btn-primary hb-select-link" href="javascript:void(0)"
									data-type="room" data-id="<?php echo (int) $room->id; ?>">
									<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if (!empty($this->offers)) : ?>
			<h3><?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_SECTION_OFFERS'); ?></h3>
			<table class="table table-sm">
				<tbody>
					<?php foreach ($this->offers as $offer) : ?>
						<tr>
							<td>
								<?php echo htmlspecialchars($offer->title); ?>
								<?php if (!empty($offer->discount)) : ?>
									<span class="badge bg-success"><?php echo htmlspecialchars($offer->discount); ?></span>
								<?php endif; ?>
								<div class="small text-muted"><?php echo htmlspecialchars($offer->parent_name); ?></div>
							</td>
							<td class="text-end">
								<a class="btn btn-sm btn-primary hb-select-link" href="javascript:void(0)"
									data-type="offer" data-id="<?php echo (int) $offer->id; ?>"
									data-entity="<?php echo htmlspecialchars($offer->entity); ?>"
									data-index="<?php echo (int) $offer->index; ?>">
									<?php echo Text::_('COM_HOTELBOOKING_SNIPPETS_INSERT'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>
</div>
