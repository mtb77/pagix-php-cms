<?
/*##################### Pagix Content Management System #######################
$Id: class.modules.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.modules.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
MODULES
Die Modulklasse ist die Mutterklasse aller Zusatzmodule im CMS und muss 
abgeleitet werden. Alle verwendenden Module sollten die oberen Methoden 
überschreiben um damit die Funktionalität zu gewährleisten.
*****WICHTIG*****
EIN MODUL SOLLTE NIEMALS AUF DIE DATENBANK DES CMS ZUGREIFEN MÜSSEN. ALLE 
BENÖTIGTEN INFORMATIONEN SIND GEKAPSELT UND VON "SICH SELBER" AUS AUFRUFBAR.
z.B. kann der PageName mit der Methode "setPageURL" geändert werden. Aufrufe 
des Publishings	sind erlaubt, aber nicht "erwünscht". Ein Modul sollte so 
ziemlich autonom arbeiten können. (z.B. Main Prozedur in den Rumpf der 
Klassendatei - damit die Module getestet werden können)
Auch hier WICHTIG: Alle Klassen werden am Anfang importiert. 
Selbstauführender Code ist nicht erlaubt.
#############################################################################*/
class modules {
	var $site;
	var $pid; 		// MODULABHÄNGIG ist das die eigene PAGEID beim MODULPERPAGE
	var $parentid;	// Für Page-basierte Module - INTERN genutzt.
	var $globals;	// GLOBALE VARIABLEN
	var $mid;
	var $db;

	function modules() {
		global $site, $db;
		$this->site = $site;
		$this->db = $db;
	}

	function globals() {
		// GLOBALS werden erst mit dem Anzeigenamen initialisiert.
		$this->globals[] = array("field"=>"dbhost",
										"name"=>"Datenbank Host",
										"value"=>"");
		$this->globals[] = array("field"=>"dbname",
										"name"=>"Datenbank Name",
										"value"=>"");
  		$this->globals[] = array("field"=>"dbuser",
										"name"=>"Datenbank User",
										"value"=>"");
    	$this->globals[] = array("field"=>"dbpasswd",
										"name"=>"Datenbank Passwd",
										"value"=>"");
     return $this->globals;
	}

	function parse_module() {
		// Parsing des kompletten Moduls
		// Wenn das Modul gepublished werden muss, soll hier der Code rein.
		// Im Regelfall sollte der entsprechende Code in einer getrennten Datei
		// gespeichert werden, der dann auf den Zielserver geschickt wird.
		// Was hier gut verwendbar ist, ist der normale Template Mechanismus, der
		// dabei dann auf eine PHP Seite beispielweise zurückgreift.
 		return "";
	}

	function parse_page() {
		// Es kann auch Module geben, die ein Parsing pro Page voraussetzen.
		// Dieses Parsing wird hiermit angestossen und dann innerhalb des normalen
		// Publishings anderer "Pages" als Return zurückgegeben.
		// Seiten, die ein Publishing mehrerer Elemente voraussetzen, kann man hiermit nicht
		// in dem Sinne abbilden. Als Beispiel gelte hier das Publishing des Elements
		// "element_mediapicture_edit" was man von der Logik übernehmen kann.
		// Bei einem leeren Return wird keine Seite erzeugt, daher ist die Demo-Methode ohne Return Wert.
		return "";
	}

	function getPageURL() {
		// Gibt die SeitenURL zurück. Der Defaultwert sollte hierbei dringend geändert werden.
		// Diese URL kann durchaus auch von einer Admin-Oberfläche dem User freigestellt sein.
		// Die URL wird von der Methode setPageURL festgelegt.
		$pg = new page($this->pid);
		return $pg->url();
	}

	function getPageName() {
		$pg = new page($this->pid);
		return $pg->pname();
	}

 	function admin_panel_page($id, $parentid) {
		echo "id:$id, parentid:$parentid<br>";
		echo "Message from: <b>Page-Administration: <i>admin_panel_page</b></i><br><br>";
  		echo "This Module is currently wrong configured or still in development.<br><br>";
		echo "For questions send a mail to: <a href='mailto:sascha@kulawik.de'>sascha@kulawik.de</a>";
	}

	function admin_panel_module() {
		//$sys = new Java("java.lang.System");
		//echo "JAVAVERSION:".$sys->getProperty("java.version");
		//$ex = xml_parser_create();
		//$ret = xml_parse_from_file($ex,$file);
		echo "Message from: <b>Module-Administration: <i>admin_panel_module</b></i><br><br>";
  		echo "This Module is currently wrong configured or still in development.<br><br>";
		echo "For questions send a mail to: <a href='mailto:sascha@kulawik.de'>sascha@kulawik.de</a>";
	}

	// ****************************************************************************************
	// GLOBALE FUNKTIONEN ZUM BENUTZEN BEI DER ABLEITUNG (ABER NICHT ABLEITEN, DENN DANN DA...)
	// ****************************************************************************************

	function setPageURL($purl) {
		$pg = new page($this->pid);
		return $pg->url($purl);
	}

	function setPageName($pname) {
		$pg = new page($this->pid);
		return $pg->pname($pname);
	}

	// ****************************************************************************************
	// GLOBALE FUNKTIONEN, NICHT FÜRS ABLEITEN RELEVANT
	// ****************************************************************************************

	function admin_panel_page_prepare($id, $parentid) {
		// Diese Funktion übernimmt generelle Aufgaben beim Erstellen und verwalten und ruft dann die
		// Benutzerdefinierte "admin_panel_page" auf.
		$this->parentid = $parentid;
		$this->setPageProperties($id);
		$this->admin_panel_page($this->pid, $parentid);
	}

	function setPageProperties($id) {
		// Diese Funktion setzt die Modulrelevanten Daten in die "Page" ein, falls die Seite neu ist.
		if ($id == "0") {
			$db = new DB_CMS;
			$mclass = get_class($this); // gibt erstmal den Klassennamen zurück
			$mclass = right($mclass, strlen($mclass) - 7);
			$ptype = $this->getPTypeForClassname($mclass);

			$db->query(sprintf("INSERT INTO page (id, pname, sid, purl, parentid, ptype) ".
										"VALUES (NULL, '%s', %s, '%s', %s, '%s')",
										"MODULE_".$mclass, $this->site->id, "KEINEURL" ,$this->parentid, $ptype
										));
			$this->pid = $db->insert_id();
		} else{
			$this->pid = $id;
		}
	}

	function getPTypeForClassname($mclass) {
		$dbb = new DB_CMS;
		$dbb->query(sprintf("SELECT mptype FROM modules WHERE mclass = '%s'", $mclass));
		$dbb->next_record();
		return $dbb->f("mptype");
	}

	function getClassnameForType($clsname) {
 		$dbb = new DB_CMS;
		$dbb->query(sprintf("SELECT mclass FROM modules WHERE mptype = '%s'", $clsname));
		$dbb->next_record();
		return $dbb->f("mclass");
	}

	function setMID() {  // SETZT DIE MODULE ID.
		$mclass = get_class($this); // gibt erstmal den Klassennamen zurück
		$mclass = right($mclass, strlen($mclass) - 7);
		//$ptype = $this->getPTypeForClassname($mclass);
		$dbb = new DB_CMS;
		$dbb->query(sprintf("SELECT id FROM modules WHERE mclass = '%s'", $mclass));
		$dbb->next_record();
		$this->mid = $dbb->f("id");
		return $this->mid;
	}

	function getAviableModules($sid) {
 		// Listet nur alle Module auf, die erstens erlaubt sind und zweitens auch ne page_config haben.
		$dbb = new DB_CMS;
		$dbb->query("SELECT modules.id,
						modules.mptype as ptype, modules.mname, modules.mclass
						FROM modules_allowed left join modules on mid=id
						WHERE has_page_config = 1 AND
						modules_allowed.sid = ".$sid);
		return $dbb;
	}

	function getAviableModulesConfig($sid) {
 		// Listet nur alle Module auf, die erstens erlaubt sind und zweitens auch ne module_config haben.
		$dbb = new DB_CMS;
		$dbb->query("SELECT modules.id,
						modules.mptype as ptype, modules.mname, modules.mclass
						FROM modules_allowed left join modules on mid=id
						WHERE has_module_config = 1 AND
						modules_allowed.sid = ".$sid);
		return $dbb;
	}

	// HELPERMETHODEN für das Lesen und Schreiben von Datenwerten zu einem Element
	function getPageVariable($varname) {
		// Holt für die Variable das Datenelement aus der Tabelle elements
		$arr = $this->getPageVariableArray();
		return $arr[$varname];
	}

	function getPageVariableArray() {
		// Helperclass, für die spätere Validierung der entsprechenden Elemente
		// zum Lesen und zum schreiben der entsprechenden Datenwerte
		$dbs = new DB_CMS;
		$dbs->query("SELECT data FROM page WHERE id = ".$this->pid);
		$dbs->next_record();
		$data = $dbs->f("data");
		$dat_arr = split("{/;}", $data);
		foreach ($dat_arr as $value) {
			if ($value != "") {
				$extdat = split("{==}", $value);
				//echo $extdat[0].$extdat[1];
				$tag = str_replace ("\"", "''", $extdat[1]);
		  			eval('$ext_arr["'.$extdat[0].'"] = "'.stripslashes($tag).'";');
			}
		}
		return $ext_arr;
	}

	function setPageVariable($varname, $varvalue) {
		// Trennzeichen intern (HOPEFULLY NOT USED ANYWHERE ELSE !!!) {/;} und {==}
		// Setzt den Datenwert in der Tabelle für eine Variable
		$dbs = new DB_CMS;
		$data = $varname."{==}".$varvalue;
		$arr = $this->getPageVariableArray();
		$arr[$varname] = $varvalue;
		$data = "";
		foreach ($arr as $key => $value) {
			if ($data != "") {$data.="{/;}";}
	    	$data.= $key."{==}".$value;
		}
		$dbs->query(sprintf("UPDATE page SET data = '%s' WHERE id = %s"
								,addslashes($data), $this->pid));
	}


	function getGlobalVariables($sid) {
		// Gibt die globalen Variablen zurück, sofern vorhanden...
		$dbb = new DB_CMS;
		$dbb->query("SELECT modules.id,
						modules.mptype as ptype, modules.mname, modules.mclass
						FROM modules_allowed left join modules on mid=id
						WHERE has_global_config = 1 AND
						modules_allowed.sid = ".$sid);
		$fnd = false;
      while($dbb->next_record()) {
			eval('$mod = new module_'.$dbb->f("mclass").'();');
			//$mod->admin_panel_module();
			$glb = $mod->globals();
			foreach($glb as $key=>$val) {
				//echo $val["field"];
				$fnd = true;
    			$allglb[$val["field"]] = $val; 		// Dadurch wird gewährleistet, daß es jede
																// Einstellung nur einmal gibt.
			}
		}
		// Jetzt müssen noch die Values aufgefüllt werden.... Tjaja !
		if ($fnd) {
			foreach($allglb as $key=>$val) {
				//echo $val["field"];
				$allglb[$val["field"]]["value"] = $this->getGlobalVariableValue($val["field"]);
			}
   	}
		return $allglb;
	}

	function getGlobalVariableValue($varname) {
		//return "test";
		$arr = $this->get_splitdata_fullarray();
		return $arr[$varname];
	}

	function setGlobalVariableValue($varname, $varvalue) {
		//$this->setMID();
		// Trennzeichen intern (HOPEFULLY NOT USED ANYWHERE ELSE !!!) {/;} und {==}
		// Setzt den Datenwert in der Tabelle für eine Variable
		$dbs = new DB_CMS;
		$data = $varname."{==}".$varvalue;
		$arr = $this->get_splitdata_fullarray();
		$arr[$varname] = $varvalue;
		$data = "";
		foreach ($arr as $key => $value) {
			if ($data != "") {$data.="{/;}";}
	    	$data.= $key."{==}".$value;
		}
		$dbs->query(sprintf("SELECT data FROM modules_globals WHERE sid = %s",
									 $this->site->id));
  		if ($dbs->num_rows() < 1) {
			$dbs->query(sprintf("INSERT INTO modules_globals (data, sid) VALUES ('%s', %s)",
								addslashes($data), $this->site->id));
		}else{
			$dbs->query(sprintf("UPDATE modules_globals SET data = '%s' WHERE sid = %s",
								addslashes($data), $this->site->id));
   	}
	}

 	// HELPERMETHODEN für das Lesen und Schreiben von Datenwerten zu einem Element
	function get_splitdata_fullarray() {
		//$this->setMID();
		// Helperclass, für die spätere Validierung der entsprechenden Elemente
		// zum Lesen und zum schreiben der entsprechenden Datenwerte
		$dbs = new DB_CMS;
			$dbs->query(sprintf("SELECT data FROM modules_globals WHERE sid = %s",
									 $this->site->id));
			$dbs->next_record();
			$data = $dbs->f("data");
			$dat_arr = split("{/;}", $data);
			foreach ($dat_arr as $value) {
				if ($value != "") {
					$extdat = split("{==}", $value);
					//echo $extdat[0].$extdat[1];
					$tag = str_replace ("\"", "''", $extdat[1]);
		   			eval('$ext_arr["'.$extdat[0].'"] = "'.stripslashes($tag).'";');
				}
			}
		return $ext_arr;
	}

	function admin_panel() {
		global $PHP_SELF;
	 	// Listet die Module auf, die existieren und stellt sie dar (für die Admin der SITE, also
		// werden auch nur die Module angezeigt, die für diese Site freigegeben worden
		switch($_GET["action2"]) {
			case "config":
				// Konfiguration "SITEABHÄNGIG" eines einzelnen Moduls unter "Module"
				$clsname = $_GET["mclass"];
				eval('$mod = new module_'.$clsname.'();');
				$mod->admin_panel_module();
				break;
			default:
				// Liste aller verfügbaren Module unter "Module" - SITEABHÄNGIG
				$t = new Template("templates/");
				$t->set_file("page","admin_modules_list.html");

				$t->set_var(array("post"=>$PHP_SELF,
										"action"=>"modules",
										"action2"=>"config",
										"sessid"=>fu()
										));
				$t->set_block("page", "list", "lst");
				$dbl = $this->getAviableModulesConfig($this->site->id);
				while($dbl->next_record()) {
						$t->set_var(array("modulname"=>$dbl->f("mname"),
												"lnk_modul"=>u($PHP_SELF."?action=modules&action2=config&mclass=".
																	$dbl->f("mclass"))
												));
						$t->parse("lst", "list",true);
				}
				$t->parse("out", "page");
				$t->p("out");
				break;
    	}
	}
}
?>