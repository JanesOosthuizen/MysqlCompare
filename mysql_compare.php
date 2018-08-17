<?php
/**
 * Copyright (c) Janes Oosthuizen (hello@janes.co.za)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     (c) Janes Oosthuizen (hello@janes.co.za)
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

class DatabaseCompare{
	var $mysqlobject = "";
        var $linkRemote = "";
	var $DatabaseOne  = "";
	var $DatabaseTwo = "";
        var $displayMatches = false;
	
	function SelectDatabaseOne($db){
		$this->DatabaseOne=$db;
	}
	
	function SelectDatabaseTwo($dbb){
		$this->DatabaseTwo=$dbb;
	}
	
	function ConnectDatabaseOne($database="localhost",$userdb="root",$passworddb=""){
		$this->mysqlobject = mysql_connect($database, $userdb, $passworddb) or die(mysql_error());
		mysql_select_db($this->DatabaseOne, $this->mysqlobject) or die(mysql_error());
	}

	function ConnectDatabaseTwo($database="localhost",$userdb="root",$passworddb=""){
		$this->linkRemote = mysql_connect($database, $userdb, $passworddb) or die(mysql_error());
		mysql_select_db($this->DatabaseTwo, $this->linkRemote) or die(mysql_error());
	}
        
	function switchDatabaseOne($obj){
		mysql_select_db($this->DatabaseOne, $obj) or die(mysql_error());
	}

	function switchDatabaseTwo($obj){
		mysql_select_db($this->DatabaseTwo, $obj) or die(mysql_error());
	}
	
	function DoComparison(){
		$tableList = array();
		$tablesLocal = array();
		$tablesRemote = array();
		$dboneSQL = "SHOW TABLES FROM {$this->DatabaseOne}";
		$resultTablesMySQLOne = mysql_query($dboneSQL, $this->mysqlobject);
		if (!$resultTablesMySQLOne) {
		   echo mysql_error($this->mysqlobject);
		   exit;
		}
		$nMySql=0;
		while ($rowTablesMySQLOne = mysql_fetch_row($resultTablesMySQLOne)) {
			$tablesLocal[$rowTablesMySQLOne[0]] = $rowTablesMySQLOne[0];
			$nMySql++;
		}

		$dbTwoSQL = "SHOW TABLES FROM {$this->DatabaseTwo}";
		$resultTablesMySQLTwo = mysql_query($dbTwoSQL, $this->linkRemote);
		if (!$resultTablesMySQLTwo) {
		   echo mysql_error($this->linkRemote);
		   exit;
		}
		$nMySqlRemote=0;
		while ($rowTablesMySQLTwo = mysql_fetch_row($resultTablesMySQLTwo)) {
			$tablesRemote[$rowTablesMySQLTwo[0]] = $rowTablesMySQLTwo[0];
			$nMySqlRemote++;
		}
		
		echo "<table border='1' style='border-collapse:collapse;width:100%'>";
		echo "<tr><td colspan='2' style='background:#000;color:#FFF; text-align:center'><strong>List of Tables</strong></td></tr>";
		echo "<tr style='background:#000; color:#FFF; text-align:center'><td style='width:50%'><strong>Local [".$this->DatabaseOne."]</strong></td>";
		echo "<td style='width:50%'><strong>Remote [".$this->DatabaseTwo."]</strong></td></tr>";
		foreach( $tablesLocal as $tablename){
			if (isset($tablesRemote[$tablename])){
			   	$tableList[$tablename] = $tablename;
				if ($this->displayMatches){
					echo "<tr align='center' style='background:#DFFFDF'><td>" . $tablename . "</td>";
					echo "<td>" . $tablesRemote[$tablename] . "</td></tr>";
				}
			}else{
				echo "<tr align='center' style='background:#FFDFDF'><td>" . $tablename . "</td>";
				echo "<td>&nbsp;</td></tr>";
			}
			unset($tablesLocal[$tablename]);
			unset($tablesRemote[$tablename]);
		}
		foreach( $tablesRemote as $tablename){
			echo "<tr align='center' style='background:#FFDFDF'><td>&nbsp;</td>";
			echo "<td>" . $tablesRemote[$tablename] . "</td></tr>";
			unset($tablesLocal[$tablename]);
			unset($tablesRemote[$tablename]);
		}
		if ($nMySql!=$nMySqlRemote){
			echo "<tr><td colspan='2' bgcolor='#FFDFDF' align='center'><strong>Table number mismatch (Local: $nMySql tables <=> Remote: $nMySqlRemote tables)</strong></td></tr>";
		}
		echo "</table><br>&nbsp;";
		echo "<table border='1' align='center' cellpadding='2' width='100%'>";

		foreach( $tableList as $tablename ){
			$fieldsLocal =  array();
			$fieldsRemote = array();
                        $this->switchDatabaseOne($this->mysqlobject);
                        $resultOne = mysql_query("SHOW COLUMNS FROM " . $tablename, $this->mysqlobject);
			if (!$resultOne) { echo mysql_error($this->mysqlobject); exit; }
			
			if (mysql_num_rows($resultOne) > 0) {
				while($rowTablesMySQLOne = mysql_fetch_assoc($resultOne)) { 
                                    $fieldsLocal[] = $rowTablesMySQLOne;
                                }
			}

			mysql_free_result($resultOne);
                        $this->switchDatabaseTwo($this->linkRemote);
			$resultTwo = mysql_query("SHOW COLUMNS FROM " . $tablename, $this->linkRemote);
                        
			if (!$resultTwo) { echo mysql_error($this->linkRemote); exit; }
			if (mysql_num_rows($resultTwo) > 0) {
				while($rowTablesMySQLTwo = mysql_fetch_assoc($resultTwo)) { 
                                    $fieldsRemote[] = $rowTablesMySQLTwo;
                                }
			}

                        mysql_free_result($resultTwo);
                        
                        $localcount = count($fieldsLocal);
                        $remotecount = count($fieldsRemote);

                        $localFields = array();
                        foreach($fieldsLocal as $fl) { 
                            $localFields[] = $fl['Field'];
                        }
                        $remoteFields = array();
                        foreach($fieldsRemote as $fr) { 
                            $remoteFields[] = $fr['Field'];
                        }
                        
                        if ($localcount == $remotecount){
                            if ($this->displayMatches){
                                echo '<tr style="background:#DFFFDF;"><td>'.$tablename.'</td><td>Number of Columns on Local: '. $localcount.'</td><td>Number of Columns on Remote: '.$remotecount.'</td><td></tr>';
                                echo '<tr style="background:#DFFFDF;"><td>'.$tablename.'</td><td>'.print_r($localFields,true).'</td><td><pre>'.print_r($remoteFields,true).'</td><td></tr>';
                            }
                        } else {
                            $localFields = $this->compareColumns($localFields, $remoteFields);
                            $remoteFields = $this->compareColumns($remoteFields, $localFields);
                            echo '<tr style="background:#FFDFDF;"><td rowspan="2">'.$tablename.'</td><td>Number of Columns on Local: '. $localcount.'</td><td>Number of Columns on Remote: '.$remotecount.'</td><td></tr>';
                            echo '<tr style="background:#FFDFDF;"><td>';
                            foreach($localFields as $lfkey => $lf) { 
                                echo $lf.'<br/>';
                            }
                            echo '</td><td>';
                            foreach($remoteFields as $rfkey => $rf) { 
                                echo $rf.'<br/>';
                            }
                            echo '</td><td></tr>';
                        }
		}
		echo "</table><br>&nbsp;";
	}
        
        function compareColumns($tableOne, $tableTwo){
            $result = array_diff($tableOne, $tableTwo);
            foreach($result as $key => $r){
                $tableOne[$key] = '<b>'.$r.'</b>';
            }
            return $tableOne;
        }
        
}
?>