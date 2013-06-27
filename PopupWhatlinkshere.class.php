<?php

class PopupWhatlinkshere
{
	const MAX_LINKS_COUNT = 20;
	/**
	 * @var DatabaseBase
	 */
	protected static $_dbr = null;
	/**
	 * @return DatabaseBase
	 */
	protected static function dbr()
	{
		if (static::$_dbr == null)
		{
			static::$_dbr = wfGetDB( DB_SLAVE );
		}
		return static::$_dbr;
	}
	
	protected static function prepareVars($title)
	{
		return array(
			
//			'plConds'
			array(
				'page_id=pl_from',
				'pl_namespace' => $title->getNamespace(),
				'pl_title' => $title->getDBkey(),
			),

//			'tlConds'
			array(
				'page_id=tl_from',
				'tl_namespace' => $title->getNamespace(),
				'tl_title' => $title->getDBkey(),
			),

//			'ilConds'
			array(
				'page_id=il_from',
				'il_to' => $title->getDBkey(),
			),
			
//			'options'
			array(),
		);
	}


	protected static function linksCount($title)
	{
		$fields = 'COUNT(*) c';
		
		list($plConds, $tlConds, $ilConds, $options) = static::prepareVars($title);

		$counts = array(
			'pl' => static::dbr()->selectRow( array( 'pagelinks', 'page' ), $fields, $plConds, __METHOD__, $options ),
			'tl' => static::dbr()->selectRow( array( 'templatelinks', 'page' ), $fields, $tlConds, __METHOD__, $options ),
			'il' => static::dbr()->selectRow( array( 'imagelinks', 'page' ), $fields, $ilConds, __METHOD__, $options ),
		);
		foreach ($counts as $i => $res)
		{
			$counts[$i] = $res->c - 0;
		}
		
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
			return '';
		
		$linkscount = 0;
		$rows = array();
		
		$counts = static::linksCount($title);
		$limits = array(
			'pl' => static::MAX_LINKS_COUNT,
			'tl' => 0,
			'il' => 0,
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
		
		$fields = array( 'page_id', 'page_namespace', 'page_title', 'page_is_redirect' );
		list($plConds, $tlConds, $ilConds, $options) = static::prepareVars($title);
		$options['ORDER BY'] = 'page_title';
		
		//
		$plRes = null;
		$tlRes = null;
		$ilRes = null;
		
		//
		$options['LIMIT'] = $limits['pl'];
		$plRes = static::dbr()->select( array( 'pagelinks', 'page' ), $fields, $plConds, __METHOD__, $options );

		if ($limits['tl'] > 0)
		{
			$options['LIMIT'] = $limits['tl'];
			$tlRes = static::dbr()->select( array( 'templatelinks', 'page' ), $fields, $tlConds, __METHOD__, $options );
		}
		
		if ($limits['il'] > 0)
		{
			$options['LIMIT'] = $limits['il'];
			$ilRes = static::dbr()->select( array( 'imagelinks', 'page' ), $fields, $ilConds, __METHOD__, $options );
		}
		
		if ($plRes && static::dbr()->numRows($plRes))
		{
			foreach($plRes as $row)
			{
				$row->is_template = 0;
				$row->is_image = 0;
				$rows[$row->page_id] = $row;
			}
		}
		if ($tlRes && static::dbr()->numRows($tlRes))
		{
			foreach($tlRes as $row)
			{
				$row->is_template = 1;
				$row->is_image = 0;
				$rows[$row->page_id] = $row;
			}
		}
		if ($ilRes && static::dbr()->numRows($ilRes))
		{
			foreach($ilRes as $row)
			{
				$row->is_template = 0;
				$row->is_image = 1;
				$rows[$row->page_id] = $row;
			}
		}
		
		$realLinkscount = 0;
		foreach($rows as $i => $row)
		{
			$ptitle = Title::newFromRow($row);
			if (!$ptitle->userCanRead())
			{
				unset($rows[$i]);
			}
			else
			{
				$rows[$i]->title = $ptitle;
				$realLinkscount++;
			}
		}
		
		
		if ($realLinkscount > 0)
		{
			$realLinkscount = $linkscount;
			
			$html = '<div class="catlinks">';
			$html .= '<a href="javascript:void(0)">'.wfMsgNoTrans('pwhl-reopen-link-close', $realLinkscount).'</a>';
			
			$html .= '<div class="inner">';
			$html .= '<ul>';
			foreach($rows as $row)
			{
				$html .= '<li>' . $wgUser->getSkin()->link($row->title, $row->title->getSubpageText()) . '</li>';
			}
			$html .= '</ul>';

			if ($linkscount > static::MAX_LINKS_COUNT)
			{
				$spec = SpecialPage::getTitleFor('whatlinkshere', $title->getDBkey());
				$html .= '<p style="margin: 5px 0 0;">' . $wgUser->getSkin()->link($spec, wfMsgNoTrans('pwhl-more-links')) . '</p>';
			}
			
			$html .= '</div>';
			$html .= '</div>';
		}
		else
		{
			$html = wfMsgNoTrans('pwhl-reopen-link-empty');
		}
		
		return $html;
	}
	
	public static function ArticleViewHeader($article, &$outputDone, &$useParserCache)
	{
		global $wgOut;

		$title = $article->getTitle();
		$linkscount = 0;
		foreach (static::linksCount($title) as $k => $count)
		{
			$linkscount += $count;
		}

		if ($linkscount > 0)
		{
			global $wgOut;
			$wgOut->addModules( 'PopupWhatlinkshere' );
			$wgOut->addHTML(
				'<div id="popup_whatlinkshere_ajax" class="catlinks">'.
					'<a href="javascript:void(0)" onclick="efPWHLShow(this);">'. wfMsgNoTrans('pwhl-reopen-link-view', $linkscount).'</a>'.
				'</div>'
					.'<div class="catlinks" style="line-height: 1.35em; margin: 0 0 0 2px; padding: 0.3em; clear: none; float: left">sadfasdfasdfa</div>'
			);
		}	
		return true;
	}
}
