<?php

/**
 * Extension is similar to DynamicPageList & company, but the code is simpler
 * and the functionality is more advanced.
 *
 * Features:
 * - <subpagelist> tag produces a simple or templated list of pages selected by dynamic conditions
 * - Special page with form interface to <subpagelist> (Special:PopupWhatlinkshere)
 * - {{#getsection|Title|section number}} parser function for extracting page sections
 * - Automatic AJAX display of subpages everywhere:
 *   $egSubpagelistAjaxNamespaces = array(NS_MAIN => true) setting enables this on namespaces specified.
 *   $egSubpagelistAjaxDisableRE is a regexp disables this on pages whose title match it.
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>, 2009+
 * @author based on SubPageList by Martin Schallnahs <myself@schaelle.de>, Rob Church <robchur@gmail.com>
 * @license GNU General Public Licence 2.0 or later
 * @link http://wiki.4intra.net/PopupWhatlinkshere
 *
 * @TODO Caching: templatelinks are now saved, but we still need to save references to
 * @TODO    category and subpage parents to the DB, and flush the cache when page is
 * @TODO    added to the category or when a new subpage of referenced parent is created.
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
$wgResourceModules['PopupWhatlinkshere'] = array(
	'scripts' => array('PopupWhatlinkshere.js'),
	'styles' => array('PopupWhatlinkshere.css'),
	'dependencies' => array( 'jquery' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'PopupWhatlinkshere',
	'position' => 'top',
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

