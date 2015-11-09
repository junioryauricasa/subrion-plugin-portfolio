<?php
//##copyright##

$iaDb->setTable('portfolio_entries');

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->iaSmarty->compile_check = true;

	unset($iaCore->iaView->blocks['left']);
	unset($iaCore->iaView->blocks['right']);

	if (isset($iaCore->requestPath[0]))
	{
		$id = (int)$iaCore->requestPath[0];

		if (!$id)
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		$portfolioEntry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, 'id = :id AND `status` = :status', array('id' => $id, 'status' => iaCore::STATUS_ACTIVE));

		if (empty($portfolioEntry))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		iaBreadcrumb::toEnd($portfolioEntry['title'], IA_SELF);
		$openGraph = array(
			'title' => $portfolioEntry['title'],
			'url' => IA_SELF,
			'description' => $portfolioEntry['body']
		);
		if (isset($portfolioEntry['image']))
		{
			$openGraph['image'] = IA_CLEAR_URL . 'uploads/' . $portfolioEntry['image'];
		}
		$iaView->set('og', $openGraph);
		$iaView->assign('portfolio_entry', $portfolioEntry);

		$iaView->title(iaSanitize::tags($portfolioEntry['title']));
	}
	else
	{
		$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
		$page = ($page < 1) ? 1 : $page;

		$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('portfolio');

		$pagination = array(
			'start' => ($page - 1) * $iaCore->get('portfolio_entries_per_page'),
			'limit' => (int)$iaCore->get('portfolio_entries_per_page'),
			'template' => $pageUrl . '?page={page}'
		);

		$order = ('date' == $iaCore->get('portfolio_entries_order')) ? 'ORDER BY `date_added` DESC' : 'ORDER BY `title` ASC';

		$stmt = '`status` = :status AND `lang` = :language';
		$iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'language' => $iaView->language));
		$rows = $iaDb->all('SQL_CALC_FOUND_ROWS `id`, `title`, `date_added`, `body`, `alias`, `image`', $stmt . ' ' . $order, $pagination['start'], $pagination['limit']);
		$pagination['total'] = $iaDb->foundRows();

		$iaView->assign('portfolio_entries', $rows);
		$iaView->assign('pagination', $pagination);
	}

	$iaView->display('index');
}

$iaDb->resetTable();