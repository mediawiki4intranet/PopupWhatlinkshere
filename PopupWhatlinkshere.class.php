<?php

class PopupWhatlinkshere
{
	const MAX_LINKS_COUNT = 20;

	protected static function prepareVars($title)
	{
		global $haclgContLang;
		$r = array(
			// plConds
			array(
				'page_id=pl_from',
				'pl_namespace' => $title->getNamespace(),
				'pl_title' => $title->getDBkey(),
			),
			// tlConds
			array(
				'page_id=tl_from',
				'tl_namespace' => $title->getNamespace(),
				'tl_title' => $title->getDBkey(),
			),
		);
		if (!empty($haclgContLang))
		{
			$pd = Title::newFromText($haclgContLang->getPermissionDeniedPage())->getArticleId();
			$r[0][] = $r[1][] = 'page_id!='.$pd;
		}
		return $r;
	}

	protected static function linksCount($title)
	{
		list($plConds, $tlConds) = static::prepareVars($title);
		$dbr = wfGetDB(DB_SLAVE);
		$counts = array(
			'pl' => $dbr->selectField(array('pagelinks', 'page'), 'COUNT(*)', $plConds, __METHOD__),
			'tl' => $dbr->selectField(array('templatelinks', 'page'), 'COUNT(*)', $tlConds, __METHOD__),
		);
		return $counts;
	}

	/**
	 * This function outputs nested html list with all links of a specific page
	 */
	public static function AjaxWLHList($pagename)
	{
		global $wgUser;
		$title = Title::newFromText($pagename);
		if (!$title)
		{
			return '';
		}

		$linkscount = 0;
		$rows = array();

		$counts = static::linksCount($title);
		$limits = array(
			'pl' => static::MAX_LINKS_COUNT,
			'tl' => 0,
		);
		$limit = static::MAX_LINKS_COUNT;
		foreach ($counts as $key => $count)
		{
			$linkscount += $count;
			$limits[$key] = min($limit, $count);
			$limit -= $count;
			if ($limit < 0)
			{
				$limit = 0;
			}
		}

		if ($linkscount <= 0)
		{
			return '';
		}
		$dbr = wfGetDB(DB_SLAVE);

		$fields = array('page_id', 'page_namespace', 'page_title', 'page_is_redirect');
		list($plConds, $tlConds) = static::prepareVars($title);
		$options = array('ORDER BY' => 'page_title');

		$plRes = null;
		$tlRes = null;
		$ilRes = null;

		if ($limits['pl'] > 0)
		{
			$options['LIMIT'] = $limits['pl'];
			$plRes = $dbr->select(array('pagelinks', 'page'), $fields, $plConds, __METHOD__, $options);
		}

		if ($limits['tl'] > 0)
		{
			$options['LIMIT'] = $limits['tl'];
			$tlRes = $dbr->select(array('templatelinks', 'page'), $fields, $tlConds, __METHOD__, $options);
		}

		if ($plRes && $dbr->numRows($plRes))
		{
			foreach ($plRes as $row)
			{
				$row->is_template = 0;
				$rows[$row->page_id] = $row;
			}
		}
		if ($tlRes && $dbr->numRows($tlRes))
		{
			foreach ($tlRes as $row)
			{
				$row->is_template = 1;
				$rows[$row->page_id] = $row;
			}
		}

		$realLinkscount = 0;
		foreach($rows as $i => $row)
		{
			$ptitle = Title::newFromRow($row);
			if (!$ptitle->userCan('read'))
			{
				unset($rows[$i]);
			}
			else
			{
				$rows[$i]->title = $ptitle;
				$realLinkscount++;
			}
		}
		$html = '<div class="like-cl">';
		if ($realLinkscount > 0)
		{
			$realLinkscount = $linkscount;
			$html .= '<a href="javascript:void(0)">'.wfMsgNoTrans('pwhl-reopen-link-close', $realLinkscount).'</a>';
			$html .= '<div class="inner">';
			$html .= '<ul>';
			$what = NULL;
			foreach ($rows as $row)
			{
				if ($what === NULL || $row->is_template != $what)
				{
					$html .= '</ul><strong>' . wfMsgNoTrans(($row->is_template ? 'pwhl-block-templates' : 'pwhl-block-pages'), $realLinkscount) . '</strong><ul>';
					$what = $row->is_template;
				}
				$html .= '<li>' . $wgUser->getSkin()->link($row->title, $row->title->getSubpageText()) . '</li>';
			}
			$html .= '</ul>';
			if ($linkscount > static::MAX_LINKS_COUNT)
			{
				$spec = SpecialPage::getTitleFor('whatlinkshere', $title->getPrefixedText());
				$html .= '<p style="margin: 5px 0 0;">' . $wgUser->getSkin()->link($spec, wfMsgNoTrans('pwhl-more-links')) . '</p>';
			}
			$html .= '</div>';
		}
		else
		{
			$html .= wfMsgNoTrans('pwhl-reopen-link-empty');
		}
		$html .= '</div>';
		header("Content-Type: text/html; charset=utf-8");
		print $html;
		exit;
	}

	public static function ArticleViewHeader($article, &$outputDone, &$useParserCache)
	{
		global $wgOut;

		$title = $article->getTitle();
		if ($title->getNamespace() == NS_FILE)
		{
			return true;
		}

		$linkscount = 0;
		foreach (static::linksCount($title) as $k => $count)
		{
			$linkscount += $count;
		}

		if ($linkscount > 0)
		{
			global $wgOut;
			$wgOut->addModuleStyles('LikeCatlinks');
			$wgOut->addModules('PopupWhatlinkshere');
			$wgOut->addHTML(
				'<div id="popup_whatlinkshere_ajax" class="like-cl like-cl-outer">'.
					'<a href="javascript:void(0)" onclick="efPWHLShow(this);">'. wfMsgNoTrans('pwhl-reopen-link-view', $linkscount).'</a>'.
				'</div>'
			);
		}
		return true;
	}
}
