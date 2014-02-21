<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/fourn/facture/info.php
 *      \ingroup    facture, fournisseur
 *		\brief      Page des informations d'une facture fournisseur
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/importacion/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/importacion/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/importacion/lib/importacion2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load('bills');
$langs->load('suppliers');
$langs->load('companies');


/*
 * View
 */
$form = new Form($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);

llxHeader('','','');


//esta parte hace las pestañas de la tabla
    $head=importacion_prepare_head($fac, $user);
    //$titre=$langs->trans("Datos de invoice".$fac->type);
    $picto=($fac->type==1?'service':'product');
    dol_fiche_head($head, 'seg', $titre, 0, $picto);





            if (GETPOST('origin') && GETPOST('originid'))
            {
                
                    $soc = $objectsrc->client;
                    $cond_reglement_id  = (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
                    $mode_reglement_id  = (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
                    $remise_percent     = (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
                    $remise_absolue     = (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
                    $dateinvoice        = empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

                    $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
                    $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datetmp);
                    $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
                    $datedue=($datetmp==''?-1:$datetmp);
                    $datetmp=dol_mktime(12,0,0,$_POST['fmonth'],$_POST['fday'],$_POST['fyear']);
                    $datef=($datetmp==''?-1:$datetmp);
                
            }
            else
            {
                $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
                $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datetmp);
                $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
                $datedue=($datetmp==''?-1:$datetmp);
                $datetmp=dol_mktime(12,0,0,$_POST['fmonth'],$_POST['fday'],$_POST['fyear']);
                $datef=($datetmp==''?-1:$datetmp);
            }



print '<form name="add" action="insertarSe.php" method="post">';
    print '<table class="border" width="100%">';

     // REF DE TRANSITO INTERNAIONAL
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref. de tr&aacutensito Internacional').'</td><td><input name="r_trancito" value="'.(isset($_POST['r_trancito'])?$_POST['r_trancito']:$fac_ori->ref).'" type="text"></td>';

    // REF TRANSPORTISTA
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref. de transportista').'</td><td><input name="r_transportista" value="'.(isset($_POST['r_transportista'])?$_POST['r_transportista']:$fac_ori->ref).'" type="text"></td>';
       
    // TRANSPORTISTA
    print '<tr><td class="fieldrequired">'.$langs->trans('Transportista').'</td><td><input name="transportista" value="'.(isset($_POST['transportista'])?$_POST['transportista']:$fac_ori->ref).'" type="text"></td>';

    // Autor/Solicitante
    print '<tr><td class="fieldrequired">'.$langs->trans('Autor/Solicitante').'</td><td><input name="auto_s" value="'.(isset($_POST['auto_s'])?$_POST['auto_s']:$fac_ori->ref).'" type="text"></td>';

    // Fecha de Embarque
    print '<tr><td class="fieldrequired">'.$langs->trans('Fecha de embarque').'</td><td>';
    $form->select_date($dateinvoice,'','','','',"add",1,1);
    print '</td></tr>';

    // ETA
    print '<tr><td>'.$langs->trans('ETA').'</td><td>';
    $form->select_date($datedue,'ech','','','',"add",1,1);
    print '</td></tr>';

    // Numero de guia
    print '<tr><td>'.$langs->trans('N&uacutemero de guia').'</td><td><input size="30" name="num_guia" value="'.(isset($_POST['num_guia'])?$_POST['num_guia']:$fac_ori->libelle).'" type="text"></td></tr>';


    //Pais de origen
    print '<tr '.$bc[$var].'><td class="fieldrequired">'.$langs->trans("Pais de Origen").'</td><td>';
        //if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
        print $form->select_country($mysoc->country_id,'country_id');
        if ($user->admin) 
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        print '</td></tr>'."\n";


    //Combo de Frontera de destino
    print '<tr '.$bc[$var].'><td class="fieldrequired">'.$langs->trans("Frontera de destino").'</td><td>';
    //if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
        print $form->select_country($mysoc->country_id,'country_id');
        if ($user->admin) 
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        print '</td></tr>'."\n";
    

    //Nota publica
    print '<tr><td>'.$langs->trans('NotePublic').'</td>';
        print '<td>';
        $doleditor = new DolEditor('note_public', GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
        print $doleditor->Create(1);
        print '</td>';
       // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';


    //Nota privada
    print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';


    //Status
    print '<tr><td class="nowrap" width="20%">'.$langs->trans("Status").'</td>';
    print '<form action="insertar.php" method="post">';
     print '<td><input type="checkbox" name="numero" value="programado"/> Programado <br/>';
     print '<input type="checkbox" name="numero" value="transito"/> En Tr&aacutensito <br/>';
     print '<input type="checkbox" name="numero" value="recibido"/> Recibido en Frontera <br/>';
    print '</td></tr></form>';
        
     //Medio de embarque
    print '<tr '.$bc[$var].'><td class="fieldrequired">'.$langs->trans("Medio de embarque").'</td><td>';
    print '<select name=trans>';
    print '<option value="aereo">'."Aereo".'</option>'; 
    print '<option value="terres">'."Terrestre".'</option>'; 
    print '<option value="marit">'."Mar&iacutetimo".'</option>'; 
    print '</select></td></tr>';

   
   // Bouton "Create Draft"
            print "</table>\n";
            print '<br><center><input type="submit" class="button" name="button" value="'.$langs->trans('Agregar').'"></center>';
            print "</form>\n";




        ///Tabla

            print"Costos asociados al embarque";

            print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
            print '<table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print'<td> Fecha </td>';
            print'<td> Estado </td>';
            print'<td> Autor </td>';
            print'<td> Comentario </td>';
            print "</tr>\n";

            print '<tr';
            print '<td class="liste_titre"></td>';
            print'<td>'.$langs->trans('');
            $form->select_date($datef,'f','','','',"add",1,1);   
            print '</td>'; 
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"></td></tr>';
            print "</form>\n";

           
        

// End of page
llxFooter();
$db->close();
?>
