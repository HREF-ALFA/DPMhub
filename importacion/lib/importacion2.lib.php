<?php

function importacion_prepare_head($object, $user)
{
	global $langs, $conf;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/importacion/trans_int/datosE.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Datos de enbarque");
	$head[$h][2] = 'dat';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/trans_int/inviceP.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Invoice pendientes");
	$head[$h][2] = 'ivi';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/trans_int/costosA.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Costos Asociados");
	$head[$h][2] = 'cos';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT."/importacion/trans_int/documentoS.php?facid=".$object->id;
	$head[$h][1] = $langs->trans("Documentos");
	$head[$h][2] = 'doc';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/importacion/trans_int/seguimientO.php?facid=".$object->id;
	$head[$h][1] = $langs->trans('Seguimiento');
	$head[$h][2] = 'seg';
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
