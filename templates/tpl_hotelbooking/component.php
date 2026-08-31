<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.tpl_hotelbooking
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var Joomla\CMS\Document\HtmlDocument $this */

require JPATH_THEMES . '/cassiopeia/component.php';

$wa = $this->getWebAssetManager();

// This template's own custom CSS/JS, compiled from scss/tpl_hotelbooking (see joomla.asset.json)
$wa->useStyle('tpl_hotelbooking.custom')
    ->useScript('tpl_hotelbooking.custom');
