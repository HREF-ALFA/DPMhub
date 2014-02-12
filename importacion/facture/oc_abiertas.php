<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/importacion/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/importacion/lib/importacion.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
        
        
        
        


$langs->load('bills');
$langs->load('suppliers');
$langs->load('companies');

$mesg='';
$errors=array();
$id			= (GETPOST('facid','int') ? GETPOST('facid','int') : GETPOST('id','int'));
$action		= GETPOST("action");
$confirm	= GETPOST("confirm");
$ref		= GETPOST('ref','alpha');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicesuppliercard'));

$object=new FactureFournisseur($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}



/*
 * Actions
*/

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
    if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
    {
        $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
    }
    else
    {
        $result=$object->createFromClone($id);
        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?action=editref_supplier&id='.$result);
            exit;
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
            $action='';
        }
    }
}

elseif ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->fournisseur->facture->valider)
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);
    $object->fetch_thirdparty();

    $qualified_for_stock_change=0;
    if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
    {
    	$qualified_for_stock_change=$object->hasProductsOrServices(2);
    }
    else
    {
    	$qualified_for_stock_change=$object->hasProductsOrServices(1);
    }

    // Check parameters
    if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change)
    {
        $langs->load("stocks");
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
            $action='';
        }
    }

    if (! $error)
    {
        $result = $object->validate($user,'',$idwarehouse);
        if ($result < 0)
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->facture->supprimer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $result=$object->delete($id);
    if ($result > 0)
    {
        header('Location: index.php');
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->fournisseur->facture->creer)
{
	$object->fetch($id);
	$ret = $object->deleteline(GETPOST('lineid'));
	if ($ret > 0)
	{
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

elseif ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);
    $result=$object->set_paid($user);
}

// Set supplier ref
if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer)
{
    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
    if ($result < 0) dol_print_error($db, $object->error);
}


// Set label
elseif ($action == 'setlabel' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);
    $object->label=$_POST['label'];
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db);
}

elseif ($action == 'setdatef' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);
    $object->date=dol_mktime(12,0,0,$_POST['datefmonth'],$_POST['datefday'],$_POST['datefyear']);
    if ($object->date_echeance < $object->date) $object->date_echeance=$object->date;
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db,$object->error);
}
elseif ($action == 'setdate_lim_reglement' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);
    $object->date_echeance=dol_mktime(12,0,0,$_POST['date_lim_reglementmonth'],$_POST['date_lim_reglementday'],$_POST['date_lim_reglementyear']);
    if (! empty($object->date_echeance) && $object->date_echeance < $object->date)
    {
    	$object->date_echeance=$object->date;
    	setEventMessage($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"),'warnings');
    }
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db,$object->error);
}
elseif ($action == 'setnote_public' && $user->rights->fournisseur->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) dol_print_error($db,$object->error);
}
elseif ($action == 'setnote_private' && $user->rights->fournisseur->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) dol_print_error($db,$object->error);
}

// Delete payment
elseif ($action == 'deletepaiement')
{
    $object->fetch($id);
    if ($object->statut == 1 && $object->paye == 0 && $user->societe_id == 0)
    {
        $paiementfourn = new PaiementFourn($db);
        $paiementfourn->fetch(GETPOST('paiement_id'));
        $result=$paiementfourn->delete();
        if ($result < 0) $mesg='<div class="error">'.$paiementfourn->error.'</div>';
    }
}

// Create
elseif ($action == 'add' && $user->rights->fournisseur->facture->creer)
{
    $error=0;

    $datefacture=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
    $datedue=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);

    if (GETPOST('socid','int')<1)
    {
    	$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')).'</div>';
    	$action='create';
    	$error++;
    }

    if ($datefacture == '')
    {
        $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('DateInvoice')).'</div>';
        $action='create';
        $_GET['socid']=$_POST['socid'];
        $error++;
    }
    if (! GETPOST('ref_supplier'))
    {
        $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('RefSupplier')).'</div>';
        $action='create';
        $_GET['socid']=$_POST['socid'];
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        $tmpproject = GETPOST('projectid', 'int');

        // Creation facture
        $object->ref           = $_POST['ref'];
		$object->ref_supplier  = $_POST['ref_supplier'];
        $object->socid         = $_POST['socid'];
        $object->libelle       = $_POST['libelle'];
        $object->date          = $datefacture;
        $object->date_echeance = $datedue;
        $object->note_public   = GETPOST('note_public');
        $object->note_private  = GETPOST('note_private');
        $object->fk_project    = ($tmpproject > 0) ? $tmpproject : null;

        // If creation from another object of another module
        if ($_POST['origin'] && $_POST['originid'])
        {
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $_POST['origin'];
            /*if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
             {
            $element = $regs[1];
            $subelement = $regs[2];
            }*/

            // For compatibility
            if ($element == 'order')    {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal')   {
                $element = 'comm/propal'; $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }
            if ($element == 'order_supplier') {
                $element = 'fourn'; $subelement = 'fournisseur.commande';
            }
            if ($element == 'project')
            {
            	$element = 'projet';
            }
            $object->origin    = $_POST['origin'];
            $object->origin_id = $_POST['originid'];

            $id = $object->create($user);

            // Add lines
            if ($id > 0)
            {
                require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
                $classname = ucfirst($subelement);
                if ($classname == 'Fournisseur.commande') $classname='CommandeFournisseur';
                $srcobject = new $classname($db);

                $result=$srcobject->fetch($_POST['originid']);
                if ($result > 0)
                {
                    $lines = $srcobject->lines;
                    if (empty($lines) && method_exists($srcobject,'fetch_lines'))  $lines = $srcobject->fetch_lines();

                    $num=count($lines);
                    for ($i = 0; $i < $num; $i++)
                    {
                        $desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
                        $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

                        // Dates
                        // TODO mutualiser
                        $date_start=$lines[$i]->date_debut_prevue;
                        if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
                        if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
                        $date_end=$lines[$i]->date_fin_prevue;
                        if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
                        if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;

                        $result = $object->addline(
                            $desc,
                            $lines[$i]->subprice,
                            $lines[$i]->tva_tx,
                            $lines[$i]->localtax1_tx,
                            $lines[$i]->localtax2_tx,
                            $lines[$i]->qty,
                            $lines[$i]->fk_product,
                            $lines[$i]->remise_percent,
                            $date_start,
                            $date_end,
                            0,
                            $lines[$i]->info_bits,
                            'HT',
                            $product_type
                        );

                        if ($result < 0)
                        {
                            $error++;
                            break;
                        }
                    }
                }
                else
                {
                    $error++;
                }
            }
            else
            {
                $error++;
            }
        }
        // If some invoice's lines already known
        else
        {
            $id = $object->create($user);
            if ($id < 0)
            {
                $error++;
            }

            if (! $error)
            {
                for ($i = 1 ; $i < 9 ; $i++)
                {
                    $label = $_POST['label'.$i];
                    $amountht  = price2num($_POST['amount'.$i]);
                    $amountttc = price2num($_POST['amountttc'.$i]);
                    $tauxtva   = price2num($_POST['tauxtva'.$i]);
                    $qty = $_POST['qty'.$i];
                    $fk_product = $_POST['fk_product'.$i];
                    if ($label)
                    {
                        if ($amountht)
                        {
                            $price_base='HT'; $amount=$amountht;
                        }
                        else
                        {
                            $price_base='TTC'; $amount=$amountttc;
                        }
                        $atleastoneline=1;

                        $product=new Product($db);
                        $product->fetch($_POST['idprod'.$i]);

                        $ret=$object->addline($label, $amount, $tauxtva, $product->localtax1_tx, $product->localtax2_tx, $qty, $fk_product, $remise_percent, '', '', '', 0, $price_base);
                        if ($ret < 0) $error++;
                    }
                }
            }
        }

        if ($error)
        {
            $langs->load("errors");
            $db->rollback();
            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
            $action='create';
            $_GET['socid']=$_POST['socid'];
        }
        else
        {
            $db->commit();
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
            exit;
        }
    }
}

// Modification d'une ligne
elseif ($action == 'update_line')
{
    if (GETPOST('etat') == '1' && ! GETPOST('cancel')) // si on valide la modification
    {
        $object->fetch($id);
        $object->fetch_thirdparty();

        if ($_POST['puht'])
        {
            $pu=$_POST['puht'];
            $price_base_type='HT';
        }
        if ($_POST['puttc'])
        {
            $pu=$_POST['puttc'];
            $price_base_type='TTC';
        }

        if (GETPOST('idprod'))
        {
            $prod = new Product($db);
            $prod->fetch($_POST['idprod']);
            $label = $prod->description;
            if (trim($_POST['desc']) != trim($label)) $label=$_POST['desc'];

            $type = $prod->type;
        }
        else
        {

            $label = $_POST['desc'];
            $type = $_POST["type"]?$_POST["type"]:0;

        }

        $localtax1tx= get_localtax($_POST['tauxtva'], 1, $mysoc,$object->thirdparty);
        $localtax2tx= get_localtax($_POST['tauxtva'], 2, $mysoc,$object->thirdparty);
        $remise_percent=GETPOST('remise_percent');

        $result=$object->updateline(GETPOST('lineid'), $label, $pu, GETPOST('tauxtva'), $localtax1tx, $localtax2tx, GETPOST('qty'), GETPOST('idprod'), $price_base_type, 0, $type, $remise_percent);
        if ($result >= 0)
        {
            unset($_POST['label']);
        }
    }
}

elseif ($action == 'addline')
{
    $ret=$object->fetch($id);
    if ($ret < 0)
    {
        dol_print_error($db,$object->error);
        exit;
    }
    $ret=$object->fetch_thirdparty();

    if (GETPOST('search_idprodfournprice') || GETPOST('idprodfournprice'))	// With combolist idprodfournprice is > 0 or -1, with autocomplete, idprodfournprice is > 0 or ''
    {
    	$idprod=0;
    	$product=new Product($db);

    	if (GETPOST('idprodfournprice') == '')
		{
			$idprod=-1;
		}
    	if (GETPOST('idprodfournprice') > 0)
        {
    	    $idprod=$product->get_buyprice(GETPOST('idprodfournprice'), $_POST['qty']);    // Just to see if a price exists for the quantity. Not used to found vat
        }

        if ($idprod > 0)
        {
            $result=$product->fetch($idprod);

            // cas special pour lequel on a les meme reference que le fournisseur
            // $label = '['.$product->ref.'] - '. $product->libelle;
            $label = $product->description;
            $label.= $product->description && $_POST['np_desc'] ? "\n" : "";
            $label.= $_POST['np_desc'];

            $tvatx=get_default_tva($object->thirdparty, $mysoc, $product->id, $_POST['idprodfournprice']);
            $npr = get_default_npr($object->thirdparty, $mysoc, $product->id, $_POST['idprodfournprice']);

            $localtax1tx= get_localtax($tvatx, 1, $mysoc,$object->thirdparty);
            $localtax2tx= get_localtax($tvatx, 2, $mysoc,$object->thirdparty);
            $remise_percent=GETPOST('remise_percent');
            $type = $product->type;

            $result=$object->addline($label, $product->fourn_pu, $tvatx, $localtax1tx, $localtax2tx, $_POST['qty'], $idprod, $remise_percent, '', '', 0, $npr);

        }
        if ($idprod == -1)
        {
            // Quantity too low
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans("ErrorQtyTooLowForThisSupplier").'</div>';
        }
    }
    else
    {
        $npr = preg_match('/\*/', $_POST['tauxtva']) ? 1 : 0 ;
        $tauxtva = str_replace('*','',$_POST["tauxtva"]);
        $tauxtva = price2num($tauxtva);
        $localtax1tx= get_localtax($tauxtva, 1, $mysoc,$object->thirdparty);
        $localtax2tx= get_localtax($tauxtva, 2, $mysoc,$object->thirdparty);
        $remise_percent=GETPOST('remise_percent');

        if (! $_POST['dp_desc'])
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Description")).'</div>';
        }
        else
        {
            $type = $_POST["type"];
            if (! empty($_POST['amount']))
            {
                $ht = price2num($_POST['amount']);
                $price_base_type = 'HT';

                //$desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0)
                $result=$object->addline($_POST['dp_desc'], $ht, $tauxtva, $localtax1tx, $localtax2tx, $_POST['qty'], 0, $remise_percent, $datestart, $dateend, 0, $npr, $price_base_type, $type);
            }
            else
            {
                $ttc = price2num($_POST['amountttc']);
                $ht = $ttc / (1 + ($tauxtva / 100));
                $price_base_type = 'HT';
                $result=$object->addline($_POST['dp_desc'], $ht, $tauxtva,$localtax1tx, $localtax2tx, $_POST['qty'], 0, $remise_percent, $datestart, $dateend, 0, $npr, $price_base_type, $type);
            }
        }
    }

    //print "xx".$tva_tx; exit;
    if ($result > 0)
    {
    	// Define output language
    	$outputlangs = $langs;
        $newlang=GETPOST('lang_id','alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    	if (! empty($newlang))
    	{
    		$outputlangs = new Translate("",$conf);
    		$outputlangs->setDefaultLang($newlang);
    	}
        //if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) supplier_invoice_pdf_create($db, $object->id, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

        unset($_POST['qty']);
        unset($_POST['type']);
        unset($_POST['idprodfournprice']);
        unset($_POST['remise_percent']);
        unset($_POST['dp_desc']);
        unset($_POST['np_desc']);
        unset($_POST['pu']);
        unset($_POST['tva_tx']);
        unset($_POST['label']);
        unset($localtax1_tx);
        unset($localtax2_tx);
    }
    else if (empty($mesg))
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }

    $action = '';
}

elseif ($action == 'classin')
{
    $object->fetch($id);
    $result=$object->setProject($_POST['projectid']);
}


// Set invoice to draft status
elseif ($action == 'edit' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);

    $totalpaye = $object->getSommePaiement();
    $resteapayer = $object->total_ttc - $totalpaye;

    // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
    //$ventilExportCompta = $object->getVentilExportCompta();

    // On verifie si aucun paiement n'a ete effectue
    if ($resteapayer == $object->total_ttc	&& $object->paye == 0 && $ventilExportCompta == 0)
    {
        $object->set_draft($user);

        $outputlangs = $langs;
        if (! empty($_REQUEST['lang_id']))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        //if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) supplier_invoice_pdf_create($db, $object->id, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

        $action='';
    }
}

// Set invoice to validated/unpaid status
elseif ($action == 'reopen' && $user->rights->fournisseur->facture->creer)
{
    $result = $object->fetch($id);
    if ($object->statut == 2
    || ($object->statut == 3 && $object->close_code != 'replaced'))
    {
        $result = $object->set_unpaid($user);
        if ($result > 0)
        {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Add file in email form
if (GETPOST('addfile'))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    // Set tmp user directory TODO Use a dedicated directory for temp mails files
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    dol_add_file_process($upload_dir_tmp,0,0);
    $action='presend';
}

// Remove file in email form
if (! empty($_POST['removedfile']))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
    dol_remove_file_process($_POST['removedfile'],0);
    $action='presend';
}

// Send mail
if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
    $langs->load('mails');

    $object->fetch($id);
    $result=$object->fetch_thirdparty();
    if ($result > 0)
    {
//        $ref = dol_sanitizeFileName($object->ref);
//        $file = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref.'/'.$ref.'.pdf';

//        if (is_readable($file))
//        {
            if ($_POST['sendto'])
            {
                // Le destinataire a ete fourni via le champ libre
                $sendto = $_POST['sendto'];
                $sendtoid = 0;
            }
            elseif ($_POST['receiver'] != '-1')
            {
                // Recipient was provided from combo list
                if ($_POST['receiver'] == 'thirdparty') // Id of third party
                {
                    $sendto = $object->client->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $object->client->contact_get_property($_POST['receiver'],'email');
                    $sendtoid = $_POST['receiver'];
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message = $_POST['message'];
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($action == 'send')
                {
                    if (dol_strlen($_POST['subject'])) $subject=$_POST['subject'];
                    else $subject = $langs->transnoentities('CustomerOrder').' '.$object->ref;
                    $actiontypecode='AC_SUP_ORD';
                    $actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
                    if ($message)
                    {
                        $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                        $actionmsg.=$message;
                    }
                    $actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
                }

                // Create form object
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
                if ($mailfile->error)
                {
                    setEventMessage($mailfile->error,'errors');
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "
                        setEventMessage($mesg);

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('BILL_SUPPLIER_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) {
                            $error++; $this->errors=$interface->errors;
                        }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
                            exit;
                        }
                    }
                    else
                    {
                        $langs->load("other");
                        $mesg='<div class="error">';
                        if ($mailfile->error)
                        {
                            $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
                            $mesg.='<br>'.$mailfile->error;
                        }
                        else
                        {
                            $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                        }
                        $mesg.='</div>';
                    }
                }
            }

            else
            {
                $langs->load("other");
                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
                dol_syslog('Recipient email is empty');
            }
/*        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }*/
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
        dol_syslog('Unable to read data from the invoice. The invoice file has perhaps not been generated.');
    }

    //$action = 'presend';
}

// Build document
elseif ($action	== 'builddoc')
{
    // Save modele used
    $object->fetch($id);
    $object->fetch_thirdparty();
    if ($_REQUEST['model'])
    {
        $object->setDocModel($user, $_REQUEST['model']);
    }

    $outputlangs = $langs;
    if (! empty($_REQUEST['lang_id']))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    $result=supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
    if ($result	<= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        exit;
    }
}

// Delete file in doc form
elseif ($action == 'remove_file')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($object->fetch($id))
    {
    	$object->fetch_thirdparty();
        $upload_dir =	$conf->fournisseur->facture->dir_output . "/";
        $file =	$upload_dir	. '/' .	GETPOST('file');
        $ret=dol_delete_file($file,0,0,0,$object);
        if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
        else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
    }
}

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->fournisseur->facture->creer)
{
	if ($action == 'addcontact')
	{
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0)
		{
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
		}

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesg = '<div class="error">'.$object->error.'</div>';
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut')
	{
		if ($object->fetch($id))
		{
			$result=$object->swapContactStatus(GETPOST('ligne'));
		}
		else
		{
			dol_print_error($db);
		}
	}

	// Efface un contact
	else if ($action == 'deletecontact')
	{
		$object->fetch($id);
		$result = $object->delete_contact($_GET["lineid"]);

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else {
			dol_print_error($db);
		}
	}
}


/*
 *	View
 */


$form = new Form($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);

llxHeader('','','');

// Mode creation
if ($action == 'create')
{
    print_fiche_titre($langs->trans('NewBill'));

    dol_htmloutput_mesg($mesg);

    $societe='';
    if ($_GET['socid'])
    {
        $societe=new Societe($db);
        $societe->fetch($_GET['socid']);
    }

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
            $element = 'projet';
        }
        else if (in_array($element,array('order_supplier')))
        {
            // For compatibility
            if ($element == 'order')    {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal')   {
                dol_htmloutput_errors('',$errors);
                $element = 'comm/propal'; $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }
            if ($element == 'order_supplier') {
                $element = 'fourn'; $subelement = 'fournisseur.commande';
            }

            require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
            $classname = ucfirst($subelement);
            if ($classname == 'Fournisseur.commande') $classname='CommandeFournisseur';
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            $objectsrc->fetch_thirdparty();

            $projectid			= (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');
            //$ref_client			= (!empty($objectsrc->ref_client)?$object->ref_client:'');

            $soc = $objectsrc->client;
            $cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
            $mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
            $remise_percent 	= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
            $remise_absolue 	= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
            $dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

            $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
            $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datetmp);
            $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
            $datedue=($datetmp==''?-1:$datetmp);
        }
    }
    else
    {
        $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
        $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datetmp);
        $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
        $datedue=($datetmp==''?-1:$datetmp);
    }

    print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
    print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';
    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

    // Third party
    print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
    print '<td>';

    if ($_REQUEST['socid'] > 0)
    {
        print $societe->getNomUrl(1);
        print '<input type="hidden" name="socid" value="'.$_GET['socid'].'">';
    }
    else
    {
        print $form->select_company((empty($_GET['socid'])?'':$_GET['socid']),'socid','s.fournisseur = 1',1);
    }
    print '</td>';

    // Ref supplier
    print '<tr><td class="fieldrequired">'.$langs->trans('RefSupplier').'</td><td><input name="ref_supplier" value="'.(isset($_POST['ref_supplier'])?$_POST['ref_supplier']:$fac_ori->ref).'" type="text"></td>';
    print '</tr>';

    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
    print '<table class="nobordernopadding">'."\n";

    // Standard invoice
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
    print $desc;
    print '</td></tr>'."\n";


    // Label
    print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" value="'.(isset($_POST['libelle'])?$_POST['libelle']:$fac_ori->libelle).'" type="text"></td></tr>';

    // Date invoice
    print '<tr><td class="fieldrequired">'.$langs->trans('DateInvoice').'</td><td>';
    $form->select_date($dateinvoice,'','','','',"add",1,1);
    print '</td></tr>';

    // Due date
    print '<tr><td>'.$langs->trans('DateMaxPayment').'</td><td>';
    $form->select_date($datedue,'ech','','','',"add",1,1);
    print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		select_projects(-1, $projectid, 'projectid');
		print '</td></tr>';
	}

    print '<tr><td>'.$langs->trans('NotePublic').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_public', GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
   // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

    // Private note
    print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

    if (is_object($objectsrc))
    {
        print "\n<!-- ".$classname." info -->";
        print "\n";
        print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
        print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
        print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        $txt=$langs->trans($classname);
        if ($classname=='CommandeFournisseur') $txt=$langs->trans("SupplierOrder");
        print '<tr><td>'.$txt.'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
        if ($mysoc->country_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
            }

            if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
            {
                print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
            }
        }
        print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";
    }
    else
    {
    	// TODO more bugs
        if (1==2 && ! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
        {
            print '<tr class="liste_titre">';
            print '<td>&nbsp;</td>';
            print '<td>'.$langs->trans('Label').'</td>';
            print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
            print '<td align="right">'.$langs->trans('VAT').'</td>';
            print '<td align="right">'.$langs->trans('Qty').'</td>';
            print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
            print '</tr>';

            for ($i = 1 ; $i < 9 ; $i++)
            {
                $value_qty = '1';
                $value_tauxtva = '';
                print '<tr><td>'.$i.'</td>';
                print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
                print '<td align="right"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
                print '<td align="right">';
                print $form->load_tva('tauxtva'.$i,$value_tauxtva,$societe,$mysoc);
                print '</td>';
                print '<td align="right"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td>';
                print '<td align="right"><input type="text" size="8" name="amountttc'.$i.'" value=""></td></tr>';
            }
        }
    }

    // Other options
    $parameters=array('colspan' => ' colspan="6"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

    // Bouton "Create Draft"
    print "</table>\n";

    print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

    print "</form>\n";


    // Show origin lines
    if (is_object($objectsrc))
    {
        print '<br>';

        $title=$langs->trans('ProductsAndServices');
        print_titre($title);

        print '<table class="noborder" width="100%">';

        $objectsrc->printOriginLinesList();

        print '</table>';
    }
}
else
{
    if ($id > 0 || ! empty($ref))
    {
        /* *************************************************************************** */
        /*                                                                             */
        /* Fiche en mode visu ou edition                                               */
        /*                                                                             */
        /* *************************************************************************** */

        $now=dol_now();

        $productstatic = new Product($db);

        $object->fetch($id,$ref);
        $result=$object->fetch_thirdparty();
        if ($result < 0) dol_print_error($db);

        $societe = new Fournisseur($db);
        $result=$societe->fetch($object->socid);
        if ($result < 0) dol_print_error($db);

        /*
         *	View card
         */
       $head=importacion_prepare_head($object, $user);
	$titre=$langs->trans("Importacion".$object->type);
	$picto=($object->type==1?'service':'product');
	dol_fiche_head($head, 'price', $titre, 0, $picto);

        dol_htmloutput_mesg($mesg);
        dol_htmloutput_errors('',$errors);

        // Confirmation de la suppression d'une ligne produit
        if ($action == 'confirm_delete_line')
        {
            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 1, 1);
            if ($ret == 'html') print '<br>';
        }

        // Clone confirmation
        if ($action == 'clone')
        {
            // Create an array for form
            $formquestion=array(
            //'text' => $langs->trans("ConfirmClone"),
            //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
            );
            // Paiement incomplet. On demande si motif = escompte ou autre
            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$object->ref),'confirm_clone',$formquestion,'yes', 1);
            if ($ret == 'html') print '<br>';
        }

        // Confirmation de la validation
        if ($action == 'valid')
        {
			 // on verifie si l'objet est en numerotation provisoire
            $objectref = substr($object->ref, 1, 4);
            if ($objectref == 'PROV')
            {
                $savdate=$object->date;
                if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))
                {
                    $object->date=dol_now();
                    //TODO: Possibly will have to control payment information into suppliers
                    //$object->date_lim_reglement=$object->calculate_date_lim_reglement();
                }
                $numref = $object->getNextNumRef($soc);
            }
            else
            {
                $numref = $object->ref;
            }

            $text=$langs->trans('ConfirmValidateBill',$numref);
            /*if (! empty($conf->notification->enabled))
            {
            	require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
            	$notify=new Notify($db);
            	$text.='<br>';
            	$text.=$notify->confirmMessage('BILL_SUPPLIER_VALIDATE',$object->socid);
            }*/
            $formquestion=array();

            if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $object->hasProductsOrServices(1))
            {
                $langs->load("stocks");
                require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
                $formproduct=new FormProduct($db);
                $formquestion=array(
                //'text' => $langs->trans("ConfirmClone"),
                //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
            }

            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, 1, 1, 240);
            if ($ret == 'html') print '<br>';
        }

        // Confirmation set paid
        if ($action == 'paid')
        {
            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        // Confirmation de la suppression de la facture fournisseur
        if ($action == 'delete')
        {
            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete', '', 0, 1);
            if ($ret == 'html') print '<br>';
        }


        /**
         * 	Invoice
         */
        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/importacion/facture/index.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td class="nowrap" width="20%">'.$langs->trans("Ref.").'</td><td colspan="4">';
        $ref= $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
        print $ref;
        print '</td>';
        print "</tr>\n";
	
	
        //numero de invoice
        print '<tr><td>'.$form->editfieldkey("Numero Invoice proveedor",'label',$object->label,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td>';
        print '<td colspan="3">'.$form->editfieldval("Label",'label',$object->label,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td>';


        // Ref supplier
        print '<tr><td>'.$form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td><td colspan="4">';
        print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer));
        print '</td></tr>';


        // Third party
        print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="4">'.$societe->getNomUrl(1);
        print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/fourn/facture/index.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)</td>';
        print '</tr>';

	//autor solicitante
        //print '<tr><td class="nowrap" width="20%">'.$langs->trans("Autor/Solicitante").'</td><td colspan="4">';



        // Date
        print '<tr><td>'.$form->editfieldkey("Fecha de Facturacion",'datef',$object->datep,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("Date",'datef',$object->datep,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker');
        print '</td>';

        // Due date
        print '<tr><td>'.$form->editfieldkey("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker');
        if ($action != 'editdate_lim_reglement' && $object->statut < 2 && $object->date_echeance && $object->date_echeance < ($now - $conf->facture->fournisseur->warning_delay)) print img_warning($langs->trans('Late'));
        print '</td>';
	?>

		<tr><td><?php echo $form->editfieldkey("NotePublic", $note_public, $object->note_public, $object, $permission, $typeofdata, $moreparam); ?></td>
		<td><?php echo $form->editfieldval("NotePublic", $note_public, $object->note_public, $object, $permission, $typeofdata, '', null, null, $moreparam); ?></td></tr>
	

	
		<tr><td><?php echo $form->editfieldkey("NotePrivate", $note_private, $object->note_private, $object, $permission, $typeofdata, $moreparam); ?></td>
		<td><?php echo $form->editfieldval("NotePrivate", $note_private, $object->note_private, $object, $permission, $typeofdata, '', null, null, $moreparam); ?></td></tr>
	
	
	<?php
	
	
	
	
	
	 // Status
        $alreadypaid=$object->getSommePaiement();
        print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4,$alreadypaid).'</td></tr>';
      
        // Other options
        $parameters=array('colspan' => ' colspan="4"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

        print '</table><br><br><br>';
                
                
        print $langs->trans('OC abiertas').'<br>';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans('Item').'</td>';
        print '<td>'.$langs->trans('#OC').'</td>';
        print '<td align="center">'.$langs->trans('No.Parte').'</td>';
        print '<td align="right">'.$langs->trans('Cantidad OC').'</td>';
        print '<td align="right">'.$langs->trans('Precio U').'</td>';
        print '<td align="right">'.$langs->trans('Cantidad INV').'</td>';
        print '<td align="center">'.$langs->trans('Precio U INV').'</td>';
        print '</tr>';

        $consulta2="SELECT * from llx_societe where nom = '{0}'";
        $questionsSQL= str_replace("{0}", $societe->nom, $consulta2);
        $resqql = $db->query($questionsSQL);
        $objjp = $db->fetch_object($resqql);
        
        $sq = 'SELECT  * FROM  `llx_commande_fournisseur` WHERE fk_soc= '.$objjp->rowid;
        $resql = $db->query($sq);
        
        $nm= $db->num_rows($resql);
                    
                    
        while($objp = $db->fetch_object($resql)){
                        
            $sqls = 'SELECT  * FROM  `llx_commande_fournisseurdet` WHERE fk_commande= '.$objp->rowid;
            $resqls = $db->query($sqls);
            $nms= $db->num_rows($resqls);
            
            //if ($resql)
            //{
            $num = $db->num_rows($resqll);
            //  if ($num > 0)
            // {
            $i = 0;

            $var=True;
            $total=0;
            $total_ttc=0;
            $totalrecu=0;
                    
                while($objps = $db->fetch_object($resqls)){               
                    
                    $sql = 'SELECT p.ref, p.label';
                    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
                    $sql.= " WHERE p.rowid = ".$objps->fk_product;
                    $resqll = $db->query($sql);
                  
                    $objpp = $db->fetch_object($resqll);
                    
                    $var=!$var;
                  
                           
                    print '<tr '.$bc[$var].'>';
                    print '<td>'.$objpp->ref;
                    print '</td>';
                    print '<td>'.$objp->ref.'</td>';
                    //if ($objp->df > 0 )
                    //{
                    print '<td align="center">'.$objpp->label;
     
                    print dol_print_date($db->jdate($objp->df)).'</td>';
                    //   }
                    //     else
                    // {
                        //print '<td align="center"><b>!!!</b></td>';
                   // }
                    print '<td align="right">'.$objps->qty.'</td>';
                    print '<td align="right">'.$objps->subprice.'</td>';
    
                    print '<form name="addpaiement" action="oc_abiertas.php" method="post">';
                    print '<td align="right">';                                        
                    print '<input type="text" size="8" name="cantidad">';
                    print "</td>\n";
                    
                    print '<td align="center">';
                    print '<input type="text" size="8" name="precio">';
                    print "</td></tr>\n";
                    
                    $total+=$objp->total_ht;
                    $total_ttc+=$objp->total_ttc;
                    $totalrecu+=$objp->am;
                    $i++;
                }
        }
        print '<tr><td></td><td></td><td></td><td></td><td align="right">';
        print "</td></tr>\n";
        //   }
        // if ($i > 1)
        // {
             /* Print total
             print '<tr class="liste_total">';
             print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').':</td>';
             print '<td align="right"><b>'.price($total_ttc).'</b></td>';
             print '<td align="right"><b>'.price($totalrecu).'</b></td>';
             print '<td align="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
             print '<td align="center">&nbsp;</td>';
             print "</tr>\n";
     //    }
*/                                    print "</table>\n";
// }     
                        print '<center><br>';
                        print '<br><input type="submit" class="button" value="'.$langs->trans('Rechazar Factura').'">';
                        print '<input type="submit" class="button" value="'.$langs->trans('Aceptar Factura').'">';
                        //print '<input type="submit" class="button" value="'.$langs->trans('Modificar').'"></center>';
                        //			print '</td></tr>';
            
            print '</form>';
    }
}


print '<div class="fichecenter"><div class="fichehalfleft"><br><br>';
            	//print '<table width="100%"><tr><td width="50%" valign="top">';
                //print '<a name="builddoc"></a>'; // ancre
                /*
                 * Documents generes
                */
                $ref=dol_sanitizeFileName($object->ref);
                $subdir = get_exdir($object->id,2).$ref;
                $filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref;
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$object->id;
                $genallowed=$user->rights->fournisseur->facture->creer;
                $delallowed=$user->rights->fournisseur->facture->supprimer;
                $modelpdf=(! empty($object->modelpdf)?$object->modelpdf:(empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)?'':$conf->global->INVOICE_SUPPLIER_ADDON_PDF));
                print $formfile->showdocuments('facture_fournisseur',$subdir,$filedir,$urlsource,$genallowed,$delallowed,$modelpdf,1,0,0,0,0,'','','',$societe->default_lang);
                $somethingshown=$formfile->numoffiles;
                /*
                 * Linked object block
                */
                //$somethingshown=$object->showLinkedObjectBlock();

	    print '</div>';
// End of page
llxFooter();
$db->close();
?>