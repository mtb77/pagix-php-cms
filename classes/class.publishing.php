<?
/*##################### Pagix Content Management System #######################
$Id: class.publishing.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.publishing.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.3  2002/04/19 14:58:11  skulawik
*** empty log message ***

Revision 2.2  2002/04/19 14:55:49  skulawik
Language Fix

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Klasse publishing
#############################################################################*/
class publishing {

	function publishing() {

	}

	function publish_page($desthost, $guid, $pid, $no_pm = false, $language="") {
		global $err;
		$pg = new page($pid, $language);

		if ($pg->isEmpty()==true AND $pg->get_page_type()=="HTML") {   
			$this->pm($path.$pg->url().".".$language." <font color=\"red\">Die Seite ist leer und wurde nicht gespeichert.</font>", "err", $no_pm);
			return;
   	}

		$pageparse = $pg->parse();             
		$err->d($pageparse."<br>".$desthost."soapserver.php");
		$path = $pg->get_current_dir($pg->parentid(), "");
		if ($pageparse!="") {
			if ($pg->isMultiLanguage()==false) {
				$pgurl = $pg->url();
    		}else{
      		$pgurl = $pg->url().".".$language;
			}
			$this->pm($path.$pgurl." ($pid)", "e", $no_pm);
			$sd = new soapdiscussion($desthost."soapserver.php");
			if (!$sd->call("import_file", array("guid"=>$guid,
											"filecontent"=>$pageparse,
											"fileurl"=>$path,
											"filename"=>$pgurl), &$arr)) {
				$this->pm($sd->errmsg, "", $no_pm);
			} else {
				/*for ($i = 0; $i < count($arr) ;$i++) {
					echo $arr[$i]->name.": ".base64_decode($arr[$i]->value) . "<BR>";
				}*/
			}
		}else{
			$this->pm($path.$pg->url().".".$language." <font color=\"red\">Die Seite hat noch keine Vorlage und wurde nicht gespeichert.</font>", "err", $no_pm);
		}
	}

	function update_soapserver($desthost, $guid) {
		$sd = new soapdiscussion($desthost."soapserver.php");

		$fh = fopen("soapserver.php", "r");
		$soapserver = fread ($fh, filesize("soapserver.php"));
		fclose ($fh);

		$fh = fopen("update_executor.php", "r");
		$execut = fread ($fh, filesize("update_executor.php"));
		fclose ($fh);

		$sd->call("update_soapserver", array("process"=>"gotnew",
														"guid"=>$guid,
														"newsoapserver"=>base64_encode($soapserver),
														"newexecutor"=>base64_encode($execut)
										), &$arr);

		$fp = fopen($desthost."execute_update.php","r");
		if(!$fp) {$this->pm("FEHLER BEIM AKTUALISIEREN DER SERVERVERSION ! BITTE KONTAKTIEREN SIE DEN ADMINISTRATOR !","err");die;}
		while(!feof($fp)) {
			$data .= fgets($fp, 4096);
		}
		fclose($fp);
		echo $data;

		$sd->call("update_soapserver", array("process"=>"delete_executer",
														"guid"=>$guid
														), &$arr);
	}

	function publish_site($desthost, $guid, $basedir, $sitesdir, $filtxt = "") { // Published das Site Directory
		$sd = new soapdiscussion($desthost."soapserver.php");
		$dhdl = opendir($sitesdir);
		if ($dhdl) {
			while ($file = readdir($dhdl)) {
				if (($file != ".") && ($file != "..")) {
					if (is_dir($sitesdir.$file)) {
						$this->publish_site($desthost, $guid, $basedir.$file."/", $sitesdir.$file."/", $filtxt);
					} else {
						$l_file_size = filesize($sitesdir.$file);
						$this->pm("($filtxt-File) Überprüfe Existenz der Datei: /".$basedir.$file, "s");
						// Check exist-file
						if (!$sd->call("check_file", array("guid"=>$guid,
														"fileurl"=>"/".$basedir,
														"filename"=>$file), &$arri)) {
							$this->pm($sd->errmsg,"err");
						}
						for ($i = 0; $i < count($arri) ;$i++) {
							$varname = $arri[$i]->name;
							$val = $arri[$i]->value;
							eval('$a_'.$varname." = '".base64_decode($val)."';");
						}
						if ($a_file_exists == "1") {
						//	echo "Filesize_REMOTE: ".$a_file_size. " Filesize_LOCAL: ".$l_file_size."<br>";
							if ($l_file_size!=$a_file_size) {
								$this->pm( "Ersetze File...<br>","");
								// Transfer File
								$this->push_file($sd, $guid, $sitesdir, $file, $l_file_size, $filtxt, $basedir);
							}
						}else{
							// File existiert nicht
							$this->push_file($sd, $guid, $sitesdir, $file, $l_file_size, $filtxt, $basedir);
						}
						//continue;
					}
				}
			}
		}
	}

	function publish_singlemedia($desthost, $file, $nopm = false) { // Published das Site Directory
		global $site,$_PHPLIB,$auth;
		$guid = $site->guid();
		$basedir = right($_PHPLIB["dir_elmedia"], strlen($_PHPLIB["dir_elmedia"])-1);
		$localdir = $_PHPLIB["sites_dir"].$auth->auth["sid"]."/".$basedir;
		//echo "ffffffffffffff";die;
		$sd = new soapdiscussion($desthost."soapserver.php");
		if (($file != ".") && ($file != "..")) {
			$l_file_size = filesize($localdir.$file);
			$this->pm("(Element-Bild) Überprüfe Existenz der Datei: /".$basedir.$file, "s", $nopm);
			// Check exist-file
			if (!$sd->call("check_file", array("guid"=>$guid,
											"fileurl"=>"/".$basedir,
											"filename"=>$file), &$arri)) {
				$this->pm($sd->errmsg,"err",$nopm);
			}
			for ($i = 0; $i < count($arri) ;$i++) {
				$varname = $arri[$i]->name;
				$val = $arri[$i]->value;
				eval('$a_'.$varname." = '".base64_decode($val)."';");
			}
			if ($a_file_exists == "1") {
			//	echo "Filesize_REMOTE: ".$a_file_size. " Filesize_LOCAL: ".$l_file_size."<br>";
				if ($l_file_size!=$a_file_size) {
					$this->pm( "Ersetze File...<br>","",$nopm);
					// Transfer File
					$this->push_file($sd, $guid, $localdir, $file, $l_file_size, "Element-Bild", $basedir,$nopm);
				}
			}else{
				// File existiert nicht
				$this->push_file($sd, $guid, $localdir, $file, $l_file_size, "Element-Bild", $basedir,$nopm);
			}
		}
	}

	function push_file($sd, $guid, $sitesdir, $file, $l_file_size, $filtxt, $basedir,$nopm=false) {
		$fh = fopen($sitesdir.$file, "r");
		$filecontent = fread ($fh, $l_file_size);
		fclose ($fh);
		$this->pm("($filtxt-File) /".$basedir.$file, "e",$nopm);
		if (!$sd->call("import_file", array("guid"=>$guid,
										"filecontent"=>$filecontent,
										"fileurl"=>"/".$basedir,
										"filename"=>$file), &$arr)) {
				$this->pm($sd->errmsg,"");
		}
	}

	function pm($message, $state="s", $no_pm = false) {
		// Publishing Message
		switch($state)
		{
			case "err":
				$resp = "Fehler: ";
				break;
			case "s":
				$resp = "Status: ";
				break;
			case "e":
				$resp = "Sende Datei: ";
				break;
			default:
				$resp = "";
				break;
		}
		if (!$no_pm AND $resp != "") {echo "<b>$resp</b><i>$message</i><br>";}
	}

}
?>