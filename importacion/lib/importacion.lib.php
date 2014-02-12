<?php

function importacion_prepare_head($object, $user)
{
	global $langs, $conf;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/importacion/facture/fiche.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Datos de invoice");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/facture/oc_abiertas.php?id=".$object->id;
	$head[$h][1] = $langs->trans("OC abiertas");
	$head[$h][2] = 'price';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/facture/document.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Documentos");
	$head[$h][2] = 'photos';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/facture/info.php?facid=".$object->id;
	$head[$h][1] = $langs->trans('Seguimiento');
	$head[$h][2] = 'category';
	$h++;
	
	
    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'importacion');

	// More tabs from canvas
	// TODO Is this still used ?
	if (isset($object->onglets) && is_array($object->onglets))
	{
		foreach ($object->onglets as $onglet)
		{
			$head[$h] = $onglet;
			$h++;
		}
	}

    complete_head_from_modules($conf,$langs,$object,$head,$h,'importacion', 'remove');

	return $head;
}

?>
