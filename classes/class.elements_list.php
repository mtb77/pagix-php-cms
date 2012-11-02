<?
/*##################### Pagix Content Management System #######################
$Id: class.elements_list.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.elements_list.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:54  skulawik
Updated Versionsinfos

###############################################################################
Elementliste
#############################################################################*/
class elements_list
{
	var $id;
	var $tid;
	var $site;
	var $err;
	var $db;
	var $elname;
	var $elmaxcount;
	var $language;

	function elements_list($id, $tid, $language="") {
		// CONSTRUCTOR
		global $site, $err, $db, $_PHPLIB;
		$this->site = $site;
		$this->err = $err;
		$this->db = $db;
		$this->tid = $tid;
		if ($language=="") {$this->language=$this->site->getLanguageDefault();}else{$this->language=$language;}
		if ($tid =="") {$this->err->raise_fatal("CLASS:elements_list", "Keine IDs der Elementliste übergeben !");}
		if ($id==0 or $id=="") {
			// Es muss eine neue Elementliste erzeugt werden, wenn die ID = 0 ist
			$this->db->query(sprintf("INSERT INTO template_elements_list (id, tid, elplaceholder, elmaxcount) ".
							"VALUES (NULL, %s, '', 0)", $this->tid));
			$id = $this->db->insert_id();
		}
		$this->id = $id;
	}

 //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 // FÜR PROPERTIES DER ENTSPRECHENDEN ELEMENTLISTE
 //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	// HELPERMETHODEN für das Lesen und Schreiben von Datenwerten zu einem PROPERTY
	function get_splitdata($varname) {
		// Holt für die Variable das Datenelement aus der Tabelle elements
		$arr = $this->get_splitdata_fullarray();
		return $arr[$varname];
	}

	function get_splitdata_fullarray() {
		// Helperclass, für die spätere Validierung der entsprechenden PROPERTY
		$dbs = new DB_CMS;
		$dbs->query("SELECT properties FROM template_elements_list WHERE id = ".$this->id." and tid = ".$this->tid);
		$dbs->next_record();
		$data = $dbs->f("properties");
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

	function set_splitdata($varname, $varvalue) {
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
		$dbs->query(sprintf("UPDATE template_elements_list SET properties = '%s' WHERE id = %s and tid = %s"
								,addslashes($data), $this->id, $this->tid));
	}

	function property($id, $value="{empty}") {
		if ($value!="{empty}") {
			//Setzen eines Properties
			$this->set_splitdata($id, $value);
		}
		return $this->get_splitdata($id);
	}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

	function elmaxcount($elmaxcount = "") {
		// Reads or sets the current pagename for this Page
		if ($elmaxcount!="") {
			$this->elmaxcount = $elmaxcount;
			$this->db->query(sprintf("UPDATE template_elements_list SET elmaxcount = %s WHERE id = %s"
								,$this->elmaxcount, $this->id));
		}
		$this->db->query(sprintf("SELECT elmaxcount FROM template_elements_list WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->elmaxcount = $this->db->f("elmaxcount");
		return $this->elmaxcount;
	}

	function elname($elname = "") {
		// Reads or sets the current pagename for this Page
		if ($elname!="") {
			$this->elname = $elname;
			$this->db->query(sprintf("UPDATE template_elements_list SET elplaceholder = '%s' WHERE id = %s"
								,$this->elname, $this->id));
		}
		$this->db->query(sprintf("SELECT elplaceholder FROM template_elements_list WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->elname = $this->db->f("elplaceholder");
		return $this->elname;
	}

	function get_auth_elements() {
		// Liefert alle Authorisieren Elemente mit den entsprechenden Mengen für eine Seite
		$dbb = new DB_CMS;
		$dbb->query("SELECT ename, eusername, eclass, etypid, eamaxcount ".
					"FROM template_elements_allowed LEFT JOIN element ".
					"ON etypid = element.id WHERE elid = ".	$this->id);
		return $dbb;
	}

	function usage_allowed($etypid, $pid) {
		// Liefert TRUE zurück, wenn entsprechend noch ein Element eingefügt werden darf.
		// Hierbei wird Rücksicht genommen auf die Gesamtusage, sowie auf die einzelne Element-Usage
		//GESAMTUSAGE PER PAGE
		$dbb = new DB_CMS;
		$dbb->query("SELECT elmaxcount FROM template_elements_list WHERE id = ".$this->id);
 		$dbb->next_record();
		$maxc = $dbb->f("elmaxcount");
		$dbb->query("SELECT count(*) as menge FROM elements_data WHERE elid = ".$this->id." AND pid = ". $pid);
		$dbb->next_record();
		$curr = $dbb->f("menge");
		//echo $this->id." ".$curr." ".$maxc."<br>";
		if ($maxc=="0") {$maxc=9999;}
		if ($curr >= $maxc) {
			return false;
   	}else{
			// Jetzt auf das Element runtergebrochen
			// ELEMENTTYPUSAGE PER PAGE
			$dbb->query("SELECT eamaxcount FROM template_elements_allowed WHERE elid = ".$this->id." AND etypid = ".$etypid);
   		$dbb->next_record();
     		$maxc = $dbb->f("eamaxcount");
			$dbb->query("SELECT count(*) as menge FROM elements_data WHERE eltypid = ".$etypid." AND pid = ". $pid);
			$dbb->next_record();
			$curr = $dbb->f("menge");
   		if ($maxc=="0") {$maxc=9999;}
			//echo $this->id." ".$curr." ".$maxc."<br>";
			if ($curr >= $maxc) {
				return false;
    		}else{
				return true;
    		}
   	}
	}
	
	function isEmpty($pid) {
 		// Gibt zurück, ob ein Element dieser Elementliste gefüllt ist oder nicht.
		// Dafür wird jedes Objekt der Elementliste instanziert und geguckt, wasma die Prozedur da spricht
		global $site;
		$this->db->query("SELECT ed.id as edid, e.id as id, e.eclass as eclass FROM elements_data ed ".
						"LEFT JOIN element e ON ed.eltypid = e.id ".
						"WHERE ed.elid = ".$this->id." AND ed.pid = ".$pid." ORDER BY elorder");
		while ($this->db->next_record()) {
			eval('$emnt = new element_'.$this->db->f("eclass").'('.$this->db->f("edid").','.$this->language.');');
			$emnt->site = $site;
			$emnt->el = $this;
			$emnt->tid = $this->tid;
			$emnt->elid = $this->id;
			$emnt->id = $this->db->f("id");

			if ($emnt->isEmpty()==false) {
				// Ein Element ist false, also ist die ganze Elementliste false
				return false;
			}
		}
		return true;
	}
	
	function delete() {
		// Löscht aktive Elementliste permanent
		// VORSICHT ! HIER MÜSSEN ALLE VERWEISE FÜR DIESE ELEMENTLISTE AUCH GELÖSCHT WERDEN !!!!!!!
		$this->db->query("DELETE FROM template_elements_list WHERE id = ".$this->id);
		$this->db->query("DELETE FROM template_elements_allowed WHERE elid = ".$this->id);
	}

	function paint($pid, $emptypaint = false) {
		global $site;
		$this->db->query("SELECT ed.id as edid, e.id as id, e.eclass as eclass FROM elements_data ed ".
						"LEFT JOIN element e ON ed.eltypid = e.id ".
						"WHERE ed.elid = ".$this->id." AND ed.pid = ".$pid." ORDER BY elorder");
		while ($this->db->next_record()) {
			eval('$emnt = new element_'.$this->db->f("eclass").'('.$this->db->f("edid").','.$this->language.');');
			$emnt->site = $site;
			$emnt->el = $this;
			$emnt->tid = $this->tid;
			$emnt->elid = $this->id;
			$emnt->id = $this->db->f("id");
			$resp .= $emnt->paint($emptypaint);
		}
		return $resp;
	}

	

	function admin_panel() {
		global $PHP_SELF, $action2, $ename, $etypid, $pid, $edid;
		switch($action2)
		{
			case "insert":
				// Dies ist das Modul, was der Benutzer bekommt, wenn er selber eine Elementliste verwaltet.
				// (Einfügen von Elementen in die Elementliste anhand der Permissions)
				if ($_REQUEST["etypid"] != "" and $_REQUEST["etypid"] != 0) {
					// Neues Element gefunden
					$e = new element($etypid);
					$e->add_data_element($pid);
				}

				$page = new page($pid);
				$t = new Template("templates/");
				$t->set_file("page","admin_page_elements.html");
				$t->set_var(array("eplaceholder"=>$this->elname(),
								"pname"=>$page->pname(),
								"back_lnk"=>u($PHP_SELF."?action=page&id=".$page->id."&language=".$this->language),
								"lnk_preview"=>u("publish.php?action=preview&pid=".$page->id."&language=".$this->language),
								"post"=>$PHP_SELF,
								"tid"=>$this->tid,
  								"elid"=>$this->id,
  								"pid"=>$pid,
								"language"=>$this->site->getLanguageName($this->language),
								"languageshort"=>$this->language,
								"sessid"=>fu()
								));

				$t->set_block("page", "elemente", "elist");
				$dbb = $this->get_auth_elements();
				while ($dbb->next_record()) {
					if ($this->usage_allowed($dbb->f("etypid"), $pid)) {
						$ename = $dbb->f("ename");
						//$ename = $dbb->f("eusername");
						//if ($ename==""){$ename = $dbb->f("ename");}
						$t->set_var(array("etypid"=>$dbb->f("etypid"),
									"ename"=>$ename
									));
						$t->parse("elist", "elemente", true);
					}
				}

				$t->set_block("page", "liste", "eelist");
				$this->db->query("SELECT d.id as id, elorder, eltypid, ename, eclass FROM elements_data d ".
								"LEFT JOIN element e ON d.eltypid = e.id WHERE elid = ".$this->id.
								" AND d.pid = ".$pid." ORDER BY elorder");
				while ($this->db->next_record()) {
				//	echo '$emnt = new element_'.$this->db->f("eclass").'('.$this->db->f("id").');';
					eval('$emnt = new element_'.$this->db->f("eclass").'('.$this->db->f("id").','.$this->language.');');
					$t->set_var(array("eename"=>$this->db->f("ename"),//$emnt->eusername(),
									"element"=>$emnt->admin_panel(),
									"lnk_delete"=>u($PHP_SELF."?action=el&action2=eddelete".
													"&etypid=".$this->db->f("eltypid").
									  				"&elid=".$this->id."&tid=".$this->tid."&edid=".$this->db->f("id")."&pid=".$pid.
													"&language=".$this->language),
									"lnk_up"=>u($PHP_SELF."?action=el&action2=edup".
													"&etypid=".$this->db->f("eltypid").
									  				"&elid=".$this->id."&tid=".$this->tid."&edid=".$this->db->f("id")."&pid=".$pid.
													"&language=".$this->language),
									"lnk_down"=>u($PHP_SELF."?action=el&action2=eddown".
													"&etypid=".$this->db->f("eltypid").
									  				"&elid=".$this->id."&tid=".$this->tid."&edid=".$this->db->f("id")."&pid=".$pid.
													"&language=".$this->language)
									));
					$t->parse("eelist", "liste", true);
				}

				$t->parse("out", "page");
				$t->p("out");
				break;
			case "edup":		// Schiebt ein Datenelement eins nach oben
				//SORTERPATCH !!!
				$dbb = new DB_CMS;
				//$this->db->query("SELECT id FROM elements_data WHERE pid = $pid ORDER BY elorder");
				$this->db->query("SELECT id FROM elements_data WHERE pid = $pid AND elid = ".$this->id." ORDER BY elorder");
				$edd=0;
				while ($this->db->next_record()) {
					$edd = $edd + 1;
					$dbb->query("UPDATE elements_data SET elorder = $edd WHERE id = ".$this->db->f("id"));
				}
				$e = new element($etypid);
				$e->move_up($edid, $pid);
				Header("Location: ".u($PHP_SELF."?action=el&action2=insert&elid=".$this->id."&tid=".$this->tid."&pid=".$pid.
											"&language=".$this->language));
				break;
			case "eddown":		// Schiebt ein Datenelement eins nach unten
				//SORTERPATCH !!!
				$dbb = new DB_CMS;
				$this->db->query("SELECT id FROM elements_data WHERE pid = $pid AND elid = ".$this->id." ORDER BY elorder");
				$edd=0;
				while ($this->db->next_record()) {
					$edd = $edd + 1;
					$dbb->query("UPDATE elements_data SET elorder = $edd WHERE id = ".$this->db->f("id"));
				}
				$e = new element($etypid);
				$e->move_down($edid, $pid);
				Header("Location: ".u($PHP_SELF."?action=el&action2=insert&elid=".$this->id."&tid=".$this->tid."&pid=".$pid.
											"&language=".$this->language));
				break;
			case "eddelete":
				$this->err->confirm("Wollen Sie wirklich das Element löschen ?",
									u($PHP_SELF."?action=el&action2=eddelete_confirmed&elid=".
											$this->id."&tid=".$this->tid."&edid=".$edid."&pid=".$pid."&etypid=".$etypid.
											"&language=".$this->language),
									u($PHP_SELF."?action=el&action2=insert&elid=".$this->id."&tid=".$this->tid."&pid=".$pid.
											"&language=".$this->language));
				break;
			case "eddelete_confirmed":
				$e = new element($etypid);
				$e->delete_data_element($edid);
				Header("Location: ".u($PHP_SELF."?action=el&action2=insert&elid=".$this->id."&tid=".$this->tid."&pid=".$pid.
											"&language=".$this->language));
				break;
			case "delete":
				$this->err->confirm("Wollen Sie wirklich den Seitenbereich <font color='#E7651A'>".
											$this->elname()."</font> löschen ?",
									u($PHP_SELF."?action=el&action2=delete_confirmed&elid=".$this->id."&tid=".$this->tid),
									u($PHP_SELF."?action=template&tid=".$this->tid),true);
				break;
			case "delete_confirmed":
				$this->delete();
				Header("Location: ".u($PHP_SELF."?action=template&tid=".$this->tid));
				break;
			case "edelete":
				$this->err->confirm("Wollen Sie wirklich das Element ".$ename." löschen ?",
									u($PHP_SELF."?action=el&action2=edelete_confirmed&elid=".$this->id."&tid=".$this->tid."&etypid=".$etypid),
									u($PHP_SELF."?action=template&tid=".$this->tid),true);
				break;
			case "edelete_confirmed":
				$e = new element($etypid);
				$e->delete();
				Header("Location: ".u($PHP_SELF."?action=el&elid=".$this->id."&tid=".$this->tid));
				break;
			case "enew":
				if ($etypid > 0) {
					$e = new element(0);
					$e->add($etypid);
				}
				Header("Location: ".u($PHP_SELF."?action=el&elid=".$this->id."&tid=".$this->tid));
				break;
			case "eedit":
				$e = new element($etypid);
				$e->eamaxcount($_REQUEST["eamaxcount"]);
				$e->etemplatefilename($_REQUEST["etemplatefilelist"]);
				$e->eusername($_REQUEST["eusername"]);
				Header("Location: ".u($PHP_SELF."?action=el&elid=".$this->id."&tid=".$this->tid));
				break;
			default:
				// ADMIN OBERFLÄCHE FÜR DIE VERWALTUNG DER RECHTE DER ELEMENTLISTE (VERWALTUNG)
				$t = new Template("templates/");
				$t->set_file("page","admin_template_elements_list.html");
				$t->set_var(array("post"=>$PHP_SELF,
							"sessid"=>fu(),
							"action"=>"el",
							"elid"=>$this->id,
							"tid"=>$this->tid,
							"elname"=>$this->elname($_REQUEST["elname"]),
							"elmaxcount"=>$this->elmaxcount($_REQUEST["elmaxcount"]),
							"lnk_back"=>u($PHP_SELF."?action=template&tid=".$this->tid),
							"ea_nw_action2"=>"enew" //action2 für neues Element
							));

				// Listet alle Propoerties auf und übergibt die Werte an die DB
				$prop = $_REQUEST["prop"];
				$t->set_block("page", "properties", "prop");
    			$this->db->query("SELECT * FROM elements_list_properties");
				while($this->db->next_record()){
					// gelöschte müssen entfernt werden.... scheisse mit der scheisse ...
					// Also nur ändern, wenn der Übergebene Wert anders ist, als der aus der DB und nen SUBMIT da war
					if ($prop[$this->db->f("id")]!=$this->property($this->db->f("id")) AND $_REQUEST["Submit"] != "") {
						$propval = $this->property($this->db->f("id"), $prop[$this->db->f("id")]);
					}else{
						$propval = $this->property($this->db->f("id"));
     				}
					$t->set_var(array("prop_name"=>$this->db->f("prop_name"),
											"prop_id"=>"prop[".$this->db->f("id")."]",
											"prop_value"=>$propval
											));
					$t->parse("prop", "properties", true);
				}

				// Listet alle enames auf
				$t->set_block("page", "elist", "ealist");
				//$this->db->query("SELECT id, ename FROM element");
				$this->db->subquery(sprintf("SELECT ename, id ".
						"FROM element where id not in ".
						"(SELECT etypid from template_elements_allowed WHERE elid = %s)",$this->id));
				while($this->db->next_record()){
					$t->set_var(array("ename"=>$this->db->f("ename"),
									"id"=>$this->db->f("id")));
					$t->parse("ealist", "elist", true);
				}
    			$arr_efile[] ="";
				// Bilde element_templates Array, was bei jedem Element angezeigt wird
				global $_PHPLIB;
				$dhdl = opendir($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
				if ($dhdl) {
					while ($file = readdir($dhdl)) {
						if (($file != ".") and ($file != "..")){
							$arr_efile[] = $file;
						}
					}
				}

				// Erst einmal werden alle aus der Datenbank ausgegeben
				$t->set_block("page", "tllist", "tl");
				$t->set_block("page", "list", "tlist");
				$dba = new DB_CMS;
				$this->db->query(sprintf("SELECT ename, elid, eamaxcount, etypid, etemplatefilename FROM template_elements_allowed tea LEFT OUTER JOIN element e ".
										"ON tea.etypid = e.id WHERE elid = %s", $this->id));

				while ($this->db->next_record()) {
					foreach ($arr_efile as $key=>$val) {
						$t->set_var("etname", $val);
						if ($this->db->f("etemplatefilename")==$val) {
							$t->set_var("etchecked", "selected");
						}else{
							$t->set_var("etchecked", "");
						}
						if ($key==0){ // Liste löschen... Weiss der Herrgott, warum er die da anfügt....
							$t->parse("tl", "tllist");
						}else{
							$t->parse("tl", "tllist", true);
						}

					}
					$t->set_var(array("ename"=>$this->db->f("ename"),
									  "eamaxcount"=>$this->db->f("eamaxcount"),
									  "etypid"=>$this->db->f("etypid"),
									  "etemplatefilename"=>$this->db->f("etemplatefilename"),
									//  "eusername"=>$this->db->f("eusername"),
									  "ea_ch_action2"=>"eedit",
									//  "lnk_eedit"=>$PHP_SELF."?action=el&action2=eedit&etypid=".$this->db->f("etypid").
									 // 		"&elid=".$this->id."&tid=".$this->tid,
									  "lnk_edelete"=>u($PHP_SELF."?action=el&action2=edelete&ename=".$this->db->f("ename")."&etypid=".$this->db->f("etypid").
									  		"&elid=".$this->id."&tid=".$this->tid)
								));
					$t->parse("tlist", "list", true);
				}

				$t->parse("out", "page");
				$t->p("out");
		}
	}
}
?>