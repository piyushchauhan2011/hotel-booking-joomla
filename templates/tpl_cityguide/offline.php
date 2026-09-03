<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.tpl_cityguide
 *
 * @copyright   (C) 2026 Piyush Chauhan.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var Joomla\CMS\Document\HtmlDocument $this */

require JPATH_THEMES . '/cassiopeia/offline.php';

$wa = $this->getWebAssetManager();

$wa->useStyle('tpl_cityguide.custom')
    ->useScript('tpl_cityguide.custom');
