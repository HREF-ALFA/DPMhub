<?php
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
dol_include_once('/module/class/skeleton_class.class.php');

require_once DOL_DOCUMENT_ROOT.'/mimodulo/lib/importacion.lib.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$myparam	= GETPOST('myparam','alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if ($action == 'add')
{
	$object=new Skeleton_Class($db);
	$object->prop1=$_POST["field1"];
	$object->prop2=$_POST["field2"];
	$result=$object->create($user);
	if ($result > 0)
	{
		// Creation OK
	}
	{
		// Creation KO
		$mesg=$object->error;
	}
}
/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Importacion','');

$form=new Form($db);

// Example 3 : List of data
//if ($action == 'list')
//{
    /*$sql = "SELECT";
    $sql.= " t.rowid,";
    $sql.= " t.field1,";
    $sql.= " t.field2";
    $sql.= " FROM ".MAIN_DB_PREFIX."mytable as t";
    $sql.= " WHERE field3 = 'xxx'";
    $sql.= " ORDER BY field1 ASC";*/
    
	//esta parte hace las pestañas de la tabla
	$head=importacion_prepare_head($object, $user);
	$titre=$langs->trans("Importacion".$object->type);
	$picto=($object->type==1?'service':'product');
	dol_fiche_head($head, 'card', $titre, 0, $picto);
	
	if($_GET['id']==1){
			
		// En mode visu
		print '<table class="border" width="100%"><tr>';
	
		// Ref
		print '<td width="15%">'.$langs->trans("Ref.").'</td>';
		print '<td colspan="2">'.$basededatos.'</td>';
		print '</tr>';
	
		// Label
		print '<tr><td>'.$langs->trans("Numero de Invoice proveedor").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Ref. proveedor").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Proveedor").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Autor/Solicitante").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Fecha de Facturacion").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Fecha limite de pago").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Nota publica").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		print '<tr><td>'.$langs->trans("Nota privada").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
		
		 print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.'</td>';
	
		print '</tr>';
	
	
		dol_fiche_end();
        
	}
   

		
	/*	
		
    print '<table class="noborder">'."\n";
    print '<tr class="liste_titre">';
    print '<td>ejshjewrhewkrhewk';
    print '</td>';
        print '<td>ejshjewrhewkrhewk';
    print '</td>';
    print_liste_field_titre($langs->trans('field1'),$_SERVER['PHP_SELF'],'t.field1','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('field2'),$_SERVER['PHP_SELF'],'t.field2','',$param,'',$sortfield,$sortorder);
    print '</tr>';

    dol_syslog($script_file." sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                if ($obj)
                {
                    // You can use here results
                    print '<tr><td>';
                    print $obj->field1;
                    print $obj->field2;
                    print '</td></tr>';
                }
                $i++;
            }
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
    }

    print '</table>'."\n";
//}*/



// End of page
llxFooter();
$db->close();
?>
