<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var array $displayData */
$destination = $displayData['destination'] ?? null;

if (empty($destination)) {
    return;
}

$link = Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $destination->id);

$teaser = '';

if (!empty($destination->description)) {
    $teaser = trim(strtok(strip_tags($destination->description), "\n"));

    if (\function_exists('mb_strlen') && mb_strlen($teaser) > 160) {
        $teaser = mb_substr($teaser, 0, 160);
        $teaser = mb_substr($teaser, 0, mb_strrpos($teaser, ' ')) . '…';
    }
}
?>
<div class="hb-snippet hb-snippet--destination hb-card">
	<?php if (!empty($destination->image)) : ?>
		<img class="hb-card-image" src="<?php echo htmlspecialchars($destination->image); ?>" alt="<?php echo htmlspecialchars($destination->name); ?>">
	<?php else : ?>
		<div class="hb-card-image hb-card-image--placeholder"></div>
	<?php endif; ?>
	<div class="hb-card-body">
		<span class="hb-snippet-label"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_DESTINATION_LABEL'); ?></span>
		<p class="hb-snippet-title"><a href="<?php echo $link; ?>"><?php echo htmlspecialchars($destination->name); ?></a></p>
		<?php if ($teaser !== '') : ?>
			<p><?php echo htmlspecialchars($teaser); ?></p>
		<?php endif; ?>
		<a class="hb-btn hb-btn--primary" href="<?php echo $link; ?>"><?php echo Text::_('PLG_CONTENT_HOTELBOOKING_EXPLORE_DESTINATION'); ?></a>
	</div>
</div>
