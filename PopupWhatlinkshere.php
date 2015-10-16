<?php

/**
 * PopupWhatlinkshere extension
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>, 2009+
 * @license GNU General Public License 2.0 or later
 * @link http://wiki.4intra.net/PopupWhatlinkshere
 */

if (!defined('MEDIAWIKI'))
{
	echo "This file is an extension to the MediaWiki software and cannot be used standalone.\n";
	die();
}

$wgExtensionFunctions[] = 'efPopupWhatlinkshere';
$wgExtensionMessagesFiles['PopupWhatlinkshere'] = dirname(__FILE__).'/PopupWhatlinkshere.i18n.php';
$wgAutoloadClasses['PopupWhatlinkshere'] = dirname(__FILE__).'/PopupWhatlinkshere.class.php';
$wgExtensionCredits['parserhook'][] = array(
	'name'    => 'Popup What links here',
	'author'  => 'Vladimir Koptev',
	'url'     => 'http://wiki.4intra.net/PopupWhatlinkshere',
	'version' => '2013-06-26',
);
$wgResourceModules['LikeCatlinks'] = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Popupwhatlinkshere',
	'styles' => array(
		'likecatlinks.css' => array('media' => 'screen'),
		'likecatlinks.print.css' => array('media' => 'print'),
	),
	'position' => 'top',
);
$wgResourceModules['PopupWhatlinkshere'] = array(
	'scripts'       => array('PopupWhatlinkshere.js'),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'PopupWhatlinkshere',
	'position'      => 'top',
);
$wgAjaxExportList[] = 'efAjaxWLHList';

// Clear floats for ArticleViewHeader {
if (!function_exists('articleHeaderClearFloats'))
{
	global $wgHooks;
	$wgHooks['ParserFirstCallInit'][] = 'checkHeaderClearFloats';
	function checkHeaderClearFloats($parser)
	{
		global $wgHooks;
		if (!in_array('articleHeaderClearFloats', $wgHooks['ArticleViewHeader']))
			$wgHooks['ArticleViewHeader'][] = 'articleHeaderClearFloats';
		return true;
	}
	function articleHeaderClearFloats($article, &$outputDone, &$useParserCache)
	{
		global $wgOut;
		$wgOut->addHTML('<div style="clear:both;height:1px"></div>');
		return true;
	}
}
// }

function efPopupWhatlinkshere()
{
	global $wgHooks;
	$wgHooks['ArticleViewHeader'][] = 'PopupWhatlinkshere::ArticleViewHeader';
}

function efAjaxWLHList($pagename)
{
	return PopupWhatlinkshere::AjaxWLHList($pagename);
}
