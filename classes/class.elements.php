<?
/*##################### Pagix Content Management System #######################
$Id: class.elements.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.elements.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:54  skulawik
Updated Versionsinfos

###############################################################################
Element-Klasse
Diese Klasse verwaltet die Tabellen template_elements_allowed sowie gilt
als Mutterklasse zum Ableiten für einzelne Elemente.
Der Constructur wird nur aus elements_list aufgerufen.
#############################################################################*/
class element
{
	var $id;				// ETYPID
	var $err;				// ERR OBJECT
	var $db;				// DB OBJECT
	var $site;				// SITE OBJECT
	var $el;				// ELEMENTS_LIST OBJECT
	var $tpl;				// TEMPLATE OBJECT
	var $eamaxcount;
	var $etemplatefilename;
	var $eusername;
	var $language;

	function element($id, $language="") {
		// CONSTRUCTOR
		global $site, $err, $db, $template, $el, $_PHPLIB;
		$this->site = $site;
		$this->err = $err;
		$this->db = $db;
		$this->el = $el;
		$this->tpl = $template;

		if ($id==0 or $id=="") {
			//echo "JETZE !";
			// Es muss eine neue Allow-Element erzeugt werden, wenn die ID = 0 ist
			$this->db->query("SELECT count(*) as menge FROM template_elements_allowed WHERE etypid = 0 AND elid = ".
							$this->el->id);
			$this->db->next_record();
			if ($this->db->f("menge") < 1) {
				$this->db->query(sprintf("INSERT INTO template_elements_allowed ".
										"(elid, etypid, eamaxcount, etemplatefilename) ".
										"VALUES (%s, 0, 0, '')", $this->el->id));
			}
		}
		$this->id = $id;
		$this->el_constructor($language);
	}

	function el_constructor($language="") {
		global $site;
		if ($language=="") {$this->language=$site->getLanguageDefault();}else{$this->language=$language;}
	//	echo "ActiveLanguage:$language $this->language";
	}

/*#####################################################################################################
	Funktionen für das Management via template_elements_allowed aus der Elementlisten-Konfiguration
#####################################################################################################*/

	function etemplatefilename($etemplatefilename = "") {
		// Reads or sets the current pagename for this Page
		if ($etemplatefilename!="") {
			$this->etemplatefilename = $etemplatefilename;
			$this->db->query(sprintf("UPDATE template_elements_allowed SET etemplatefilename = '%s' WHERE elid = %s and etypid = %s"
							,$etemplatefilename, $this->el->id, $this->id));
		}
		//$this->err->debug("elid:".$this->el->id." etypid:".$this->id);
		$this->db->query(sprintf("SELECT etemplatefilename FROM template_elements_allowed WHERE elid = %s and etypid = %s"
							, $this->el->id, $this->id));
		$this->db->next_record();
		$this->etemplatefilename = $this->db->f("etemplatefilename");
		return $this->etemplatefilename;
	}

	function eusername($eusername = "") {
		$dbq = new DB_CMS;
		// Reads or sets the current pagename for this Page
		if ($eusername!="") {
			$this->eusername = $eusername;
			$dbq->query(sprintf("UPDATE template_elements_allowed SET eusername = '%s' WHERE elid = %s and etypid = %s"
							,$eusername, $this->el->id, $this->id));
		}
		//$this->err->debug("elid:".$this->el->id." etypid:".$this->id);
		$dbq->query(sprintf("SELECT eusername FROM template_elements_allowed WHERE elid = %s and etypid = %s"
							, $this->el->id, $this->id));
		$dbq->next_record();
		$this->eusername = $dbq->f("eusername");
		return $this->eusername;
	}

	function eamaxcount($eamaxcount = "") {
		// Reads or sets the current pagename for this Page
		if ($eamaxcount!="") {
			$this->eamaxcount = $eamaxcount;
			$this->db->query(sprintf("UPDATE template_elements_allowed SET eamaxcount = %s WHERE elid = %s and etypid = %s"
							,$eamaxcount, $this->el->id, $this->id));
		}
		$this->db->query(sprintf("SELECT eamaxcount FROM template_elements_allowed WHERE elid = %s and etypid = %s"
							, $this->el->id, $this->id));
		$this->db->next_record();
		$this->eamaxcount = $this->db->f("eamaxcount");
		return $this->eamaxcount;
	}

	function add($etypid) {
		if ($etypid != "" AND $etypid != 0)	{$this->id = $etypid;}
		$this->db->query(sprintf("UPDATE template_elements_allowed SET etypid = %s WHERE elid = %s and etypid = 0"
								,$this->id, $this->el->id));
	}

	function delete($etypid = "") {
		if ($etypid != "" AND $etypid != 0)	{$this->id = $etypid;}
		$this->db->query(sprintf("DELETE FROM template_elements_allowed WHERE etypid = %s AND elid = %s"
								,$this->id, $this->el->id));
	}

/*#####################################################################################################
	Funktionen für das Management der einzelnen Elemente
#####################################################################################################*/

	function paint($emptypaint = false) {
		// Paintet ein Element-Object
		// OVERRIDEABLE !
		global $_PHPLIB;
		//echo $this->etemplatefilename()."dd".$this->el->id."dd". $this->id;
		if ($emptypaint == false) {
			$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
			$t->set_file("page",$this->etemplatefilename());
			$t->set_var("data", nl2br($this->data()));
			$t->parse("out", "page");
			return $t->get("out");
   	}else{
			return nl2br($this->data());
		}
	}

	function isEmpty() {
		// Gibt zurück, ob das Element gefüllt ist. Sollte im Regelfall überschrieben werden
		// auch hier wieder: Keine OO-Programmiersprache :)
		// Und Gott schuf die ABSTRACTS.......
		return false;
	}

	// HELPERMETHODEN für das Lesen und Schreiben von Datenwerten zu einem Element
	function get_splitdata($varname) {
		// Holt für die Variable das Datenelement aus der Tabelle elements
		$arr = $this->get_splitdata_fullarray();
		return $arr[$varname.$this->language];
	}

	function get_splitdata_fullarray() {
		// Helperclass, für die spätere Validierung der entsprechenden Elemente
		// zum Lesen und zum schreiben der entsprechenden Datenwerte
		$dbs = new DB_CMS;
		if ($this->edid!="") {
			$dbs->query("SELECT data FROM elements_data WHERE id = ".$this->edid);
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
		}
		return $ext_arr;
	}

	function set_splitdata($varname, $varvalue) {
		// Trennzeichen intern (HOPEFULLY NOT USED ANYWHERE ELSE !!!) {/;} und {==}
		// Setzt den Datenwert in der Tabelle für eine Variable
		$dbs = new DB_CMS;
		$data = $varname.$this->language."{==}".$varvalue;
		$arr = $this->get_splitdata_fullarray();
		$arr[$varname.$this->language] = $varvalue;
		$data = "";
		foreach ($arr as $key => $value) {
			if ($data != "") {$data.="{/;}";}
	    	$data.= $key."{==}".$value;
		}
		$dbs->query(sprintf("UPDATE elements_data SET data = '%s' WHERE id = %s"
								,addslashes($data), $this->edid));
	}

	//Methoden zum Anlegen von Datenobjekten
	function elorder($id) {
		$this->db->query("SELECT elorder FROM elements_data WHERE id = ".$id);
		$this->db->next_record();
		return $this->db->f("elorder");
	}

	function get_max_orderid($pid) {
		$this->db->query("SELECT max(elorder) as elorder FROM elements_data WHERE elid = ".$this->el->id.
							" AND pid = ".$pid);
		$this->db->next_record();
		//$ret = $this->db->f("elorder") + 1;
		//return $ret;
		return $this->db->f("elorder");
	}

	function add_data_element($pid) {
		//if ($etypid != "" AND $etypid != 0)	{$this->id = $etypid;}
		$this->db->query(sprintf("INSERT INTO elements_data (id, elid, pid, elorder, eltypid, data) ".
								"VALUES (NULL, %s, %s, %s, %s, '')"
								,$this->el->id, $pid, $this->get_max_orderid($pid) + 1, $this->id
								));
		return $this->db->insert_id();
	}

	function delete_data_element($id) {
		$eo = $this->elorder($id);
		$this->db->query("DELETE FROM elements_data WHERE id = ".$id);
		$this->db->query(sprintf("UPDATE elements_data SET elorder = elorder - 1 ".
						"WHERE elorder > %s AND elid = %s", $eo, $this->el->id));
	}

	function move_up($id, $pid) {
		// Werte kleiner machen
		$eo = $this->elorder($id);
		if ($eo > 1) {
			$id2 = $eo-1;
			$this->db->query(sprintf("SELECT id FROM elements_data ".
									"WHERE elid = %s AND pid = %s AND elorder = %s",$this->el->id,$pid,$id2));
			$this->db->next_record();
			$id2 = $this->db->f("id");
			$this->db->query(sprintf("UPDATE elements_data SET elorder = %s ".
							"WHERE id = %s", $eo, $id2));
			$this->db->query(sprintf("UPDATE elements_data SET elorder = %s ".
							"WHERE id = %s", $eo - 1, $id));
		}
	}

	function move_down($id, $pid) {
		// Werte grösser machen
		$eo = $this->elorder($id);
		if ($eo < $this->get_max_orderid($pid)) {
			$id2 = $eo+1;
			$this->db->query(sprintf("SELECT id FROM elements_data ".
									"WHERE elid = %s AND pid = %s AND elorder = %s",$this->el->id,$pid,$id2));
			$this->db->next_record();
			$id2 = $this->db->f("id");
			$this->db->query(sprintf("UPDATE elements_data SET elorder = %s ".
							"WHERE id = %s", $eo, $id2));
			$this->db->query(sprintf("UPDATE elements_data SET elorder = %s ".
							"WHERE id = %s", $eo + 1, $id));
		}
	}

	function publish($id) {
		//OVERIDEABLE
		//Leere Funktion, wird für "normale" Elemente nicht benötigt,
		//allerdings beim Publishing immer aufgerufen.
		//Falls eine Element-Klasse zusätzliche Parameter zum Publishing
		//benötigt, werden diese hier festgelegt. (per Überladung)
		//(z.B. BLOBs such as Bilder, die in der Datenbank gespeichert sind)
	}
}
?>