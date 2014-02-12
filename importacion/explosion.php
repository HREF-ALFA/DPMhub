<?php


    $host = localhost;
    $dbname = dolibarr;
    $user = dolibarrmysql;
    $pass = changeme;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    }catch( PDOException $e) {
        echo "Error Connection: " . $e->getMessage();
    }


    //explosion
    	$consulta="SELECT * from llx_commande_fournisseurdet group by (fk_commande)";
    	$result=$conn->prepare($consulta) or $myVar=true;
	$result->execute();
	$num_record=$result->rowCount();

	$i=0;
        $array;
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
              
             $array[$i]=$row['fk_commande'];
              $i++;

        }
	$e=0;
	$array_bu;
        for($i=1; $i<=$num_record; $i++){
           
	    $consulta2="SELECT * from llx_commande_fournisseurdet where fk_commande = {0}";
	    $questionsSQL= str_replace("{0}", $array[$i-1], $consulta2);
	    $myRsRes=$conn->prepare($questionsSQL);
	    $myRsRes->execute();
	    $num_record2=$myRsRes->rowCount();
	    	    
	       
	    while($rows = $myRsRes->fetch(PDO::FETCH_ASSOC)) {
		  
		$consulta3="SELECT * from llx_commande_fournisseur where rowid = {0}";
		$questionsSQL2= str_replace("{0}", $rows['fk_commande'], $consulta3);
		$myRs=$conn->prepare($questionsSQL2);
		$myRs->execute();
		$rowss = $myRs->fetch(PDO::FETCH_ASSOC);
		
	        $pedido=$rows['fk_commande'];
		$producto=$rows['fk_product'];
		$tercero=$rowss['fk_soc'];
		for($s=0; $s<$rows['qty']; $s++){
		    
		    $consulta4="INSERT INTO `dolibarr`.`llx_input_stock(individuos)` (`id`, `fk_trans_nacional`, `fk_transporte`, `fk_costos`, `fk_importacion`, `fk_transit_internacional`, `fk_factura`, `fk_societe`, `fk_productos`, `fk_ordendecompra`, `fk_frontera`) VALUES ('{00}', '{01}', '{02}', '{03}', '{04}', '{05}', '{06}', '{07}', '{08}', '{09}', '{10}');";
		    $lSrch= array("{00}","{01}", "{02}", "{03}", "{04}", "{05}", "{06}", "{07}", "{08}", "{09}", "{10}");
		    $lVals= array('NULL','NULL','NULL','NULL','NULL','NULL','NULL',$tercero,$producto,$pedido,'NULL');
		    $tSQL= str_replace($lSrch, $lVals, $consulta4);
		    $result1=$conn->prepare($tSQL) or die("Invalid Query: " . $tSQL . " - " . mysql_error());
		    $result1->execute();
		}
	    }
	    
        }
?>