<?
/*##################### Pagix Content Management System #######################
$Id: class.page.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.page.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Page-Klasse zur Erstellung und Verwaltung von Informationen über Pages
Hier werden zur Einfachheit auch die Module verwaltet.
Alle Module werden aus "Page" Instanziert, da die admin_panel-Verwaltung auch 
hier stattfindet.
#############################################################################*/
class page
{
	var $id = 0;
	var $url = "";
	var $pname = "";
	var $fname = "";
	var $site;
	var $err;
	var $db;
	var $tid;
	var $folder;
	var $language;

	function page($id = 0,$language="") {
		// CONSTRUCTOR
		global $site, $err, $db, $action2;
		$this->site = $site;
		$this->err = $err;
		$this->db = $db;
		if ($action2 == "folder_config" or $action2 == "folder") {
			$resp = $this->site->is_folder_in_site($id);
			$this->folder = true;
		}else{
			$resp = $this->site->is_page_in_site($id);
			$this->folder = false;
		}
		if ($id!=0) {
			if ($resp) {
				$this->id = $id;
			}else{
				$this->err->raise("page.is_page_in_site", "Keine Berechtigung !");
			}
		}

		if ($language != "") {
			$this->language = $language;
   	}elseif ($_REQUEST["language"]!=""){
			$this->language = $_REQUEST["language"];
   	}else{
			$this->language = $this->site->getLanguageDefault();
   	}

	}

	function parse() {
		// Erstellt die Seite für die Ausgabe respektive das Publishing
		global $_PHPLIB;
		// Erstellt eine Ansicht der Seite mit den entsprechenden Pageelementen und Templates

		if ($this->id==0) {
			die;
		}
		if ($this->get_page_type()!="HTML") {
			// MODULE WERDEN HIER AUFGERUFEN !!!!
			$mdl = new modules();
			$clsname = $mdl->getClassnameForType($this->get_page_type());
			eval('$mod = new module_'.$clsname.'();');
			$mod->parentid = $this->parentid();
			$mod->setPageProperties($this->id);

			return $mod->parse_page();
		}else{
			$tpl = new cmstemplate($this->tid());
			$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_templates"]);
			if ($tpl->filename()!="") {
				$t->set_file("page",$tpl->filename());
				$t->set_var("pname", $this->pname());
				$dbb = $tpl->get_all_element_lists();
				while ($dbb->next_record()) {
					$el = new elements_list($dbb->f("id"), $this->tid(), $this->language);
					$t->set_var($dbb->f("elplaceholder"), $el->paint($this->id));
				}
				$t->parse("out", "page");
				return $t->get("out");
   		}else{
			 	return "";
			}
   	}
	}

	function paint() {
		// Erstellt eine Previewansicht der aktuellen Page
		echo $this->parse();
	}

	function isEmpty() {
 		// Ermittelt, ob der Inhalt der Elementlisten schon gefüllt ist.
		$tpl = new cmstemplate($this->tid());
		$dbb = $tpl->get_all_element_lists();
		$inh = "";
		while ($dbb->next_record()) {
			$el = new elements_list($dbb->f("id"), $this->tid(), $this->language);
			if ($el->isEmpty($this->id)==false) {
				return false;
			}
		}
		return true;
	}


	function isMultiLanguage($MultiLanguage = "{case}") {
		global $auth;
		if ($this->get_page_type()!="HTML") {
			return false;
   	}
		if ($MultiLanguage!="{case}") {
			$this->db->query(sprintf("UPDATE page SET isMultiLanguage = '%s' WHERE id = %s", $MultiLanguage, $this->id));
		}
		$this->db->query(sprintf("SELECT isMultiLanguage FROM page WHERE id = %s", $this->id));
		$this->db->next_record();
		// echo "fff".$this->db->f("isMultiLanguage");
		if ($this->db->f("isMultiLanguage") == "1") {
			return true;
		}else{
			return false;
		}
	}

	function has_data() {
		// Ermittelt, ob schon Daten für diese Page eingegeben wurden
		$this->db->query("SELECT count(*) as menge FROM elements_data WHERE pid = ".$this->id);
		$this->db->next_record();
		if ($this->db->f("menge") >= 1) {
			return true;
		}else{
			return false;
		}
	}

	function get_current_dir($parentid, $path) {
		$dbd = new DB_CMS;
		if ($parentid != 0 AND $parentid != "") {
			$dbd->query("SELECT fname, parentid FROM folder WHERE id = $parentid");
			$dbd->next_record();
			if ($dbd->f("fname") != "") {
				$path = $dbd->f("fname")."/".$path;
			}
			$parentid = $dbd->f("parentid");
			$path = $this->get_current_dir($parentid, $path);
		}
		if (right($path, 1) != "/") {$path.="/";}
		if (left($path, 1) != "/") {$path="/".$path;}
		return $path;
	}

	function exists_pagename($purl, $parentid = "") {
		// Checks the existence of a specific Pagename
		if ($parentid=="") {
			$parentid = $this->parentid();
   	}
		$dbb = new DB_CMS;
		$dbb->query(sprintf("SELECT count(*) as menge FROM page WHERE sid = %s AND purl = '%s' AND parentid = '%s'",
							$this->site->id, $purl, $parentid));
		$dbb->next_record();
		if ($dbb->f("menge") > 0) {
			return true;
		}else{
			return false;
		}
	}

	function generate_filename() {
		// Generates the Directoryname for a Sitename
		// Pages are stored in the destination Server in specific directorys per Page,
		// so this pagename cannot exists twice
		$ee = strtolower(str_replace(" ", "", $this->pname)).".html";
		$i=0;
		while ($this->exists_pagename($ee)) {
			$i=$i+1;
			$ee = strtolower(str_replace(" ", "", $this->pname)).$i.".html";
   	}
		return $ee;
	}

	function url($url = "") {
		global $auth;
		// Reads or sets the current URL for this Page
		if ($url!="" and $url!=$this->url()) {

			if ($auth->r("page", "changeurl")) {
				$i=0;
				while ($this->exists_pagename($url)) {
					$i=$i+1;
					$url = $i.$url;
				}
				$this->url = $url;
				$this->db->query(sprintf("UPDATE page SET purl = '%s' WHERE id = %s", $url, $this->id));
			}
		}
		$this->db->query(sprintf("SELECT purl FROM page WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->url = $this->db->f("purl");
		return $this->url;
	}
	
	function tid($tid = "") {
		// Reads or sets the current Template ID for this Page
		$dbb = new DB_CMS;
		if ($tid!="") {
			$this->tid = $tid;
			$dbb->query(sprintf("UPDATE page SET tid = %s WHERE id = %s", $tid, $this->id));
		}
		$dbb->query(sprintf("SELECT tid FROM page WHERE id = %s", $this->id));
		$dbb->next_record();
		$this->tid = $dbb->f("tid");
		return $this->tid;
	}
	
	function parentid($ppid = "") {
		global $parentid;
		if ($this->folder) {$tbl = "folder";}else{$tbl = "page";}
		if ($ppid != "") {
  			$this->db->query("UPDATE $tbl SET parentid = $ppid WHERE id = ".$this->id);
		}
		if ($parentid!="") {
			return $parentid;
		} else {
			$this->db->query("SELECT parentid FROM $tbl WHERE id = ".$this->id);
			$this->db->next_record();
			return $this->db->f("parentid");
		}
	}

	function copy() {
		// COPY
		$np = new page(0);
		$np->pname($this->pname()."_copy");
  		$np->url($np->generate_filename());
		$np->tid($this->tid());
		$dpid = $np->id;									// DESTINATION PID

		$dbs = new DB_CMS;
		$dbd = new DB_CMS;
  		$dbs->query("SELECT * FROM elements_data WHERE pid = ".$this->id);
    	while ($dbs->next_record()) {
			$dbd->query(sprintf("INSERT INTO elements_data (id, elid, pid, elorder, eltypid, data) VALUES ".
							"(NULL, %s, $dpid, %s, %s, '%s')",	$dbs->f("elid"),
																		$dbs->f("elorder"),
																		$dbs->f("eltypid"),
																		addslashes(stripslashes($dbs->f("data")))
																		));
		}
	}

	function move($mvid) {
		// MOVE
		$i=0;
		$url=$this->url();
		while ($this->exists_pagename($url, $mvid)) {
			$i=$i+1;
			$url = $i.$url;
		};
		$this->url($url);
		$this->parentid($mvid);
	}
	
	function pname($pname = "") {
		global $auth;
		// Reads or sets the current pagename for this Page
		if ($pname!="") {
			$this->pname = $pname;
			if ($this->id == 0) {
				// NEUEINTRAG
				$auth->r("page", "addpage",  true);
				//if (!$this->exists_pagename($pname)){
					$this->db->query(sprintf("INSERT INTO page (id, pname, sid, purl, parentid) ".
					                        "VALUES (NULL, '%s', %s, '%s', %s)",
								$pname, $this->site->id, $this->generate_filename(),
								$this->parentid() ));
					$this->id = $this->db->insert_id();
				//}else{
				//	$this->err->raise("page.pname", "Seitenname existiert schon !");
				//}
			}else{
				$this->db->query(sprintf("UPDATE page SET pname = '%s' WHERE id = %s", $pname, $this->id));
			}
		}
		$this->db->query(sprintf("SELECT pname FROM page WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->pname = $this->db->f("pname");
		return $this->pname;
	}
	
	function fname($fname = "") {
		global $auth;
		// Reads or sets the current name for this folder (if its an folder)
		if ($fname!="") {
			$this->fname = $fname;
			if ($this->id == 0) {
				// NEUEINTRAG
				$auth->r("page", "addpage",  true);
				$this->db->query(sprintf("INSERT INTO folder (id, fname, sid, parentid) ".
							"VALUES (NULL, '%s', %s, %s)",
							$fname, $this->site->id, $this->parentid()
							));
				$this->id = $this->db->insert_id();
			}else{
				$this->db->query(sprintf("UPDATE folder SET fname = '%s' WHERE id = %s", $fname, $this->id));
			}
		}
		$this->db->query(sprintf("SELECT fname FROM folder WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->fname = $this->db->f("fname");
		return $this->fname;
	}

	function get_page_type() {
		// Wenn ne neue Seite angelegt werden soll, gibt diese Funktion ein leeren Pagetyp zurück
		if ($this->id  == 0) {
			return "";
   	}else{
			$dbb = new DB_CMS;
			$dbb->query("SELECT ptype FROM page WHERE id = " . $this->id);
  			$dbb->next_record();
			return $dbb->f("ptype");
   	}
	}
	
	function admin_panel() {
		// Paints the current admin panel for a page
		// If no ID is set, the class will create a new page
		global $PHP_SELF, $action2, $auth;
		
		switch($action2)
		{
			case "copy":
				$this->copy();
				//Das Submit ist für den Reload des Trees
    			Header("Location: ".u($PHP_SELF."?Submit=submit&action=page&action2=config&parentid=".
											$this->parentid()."&id=".$this->id));
				break;
    		case "move":
				$this->move($_GET["folder_sel"]);
				//Das Submit ist für den Reload des Trees
    			Header("Location: ".u($PHP_SELF."?Submit=submit&action=page&action2=config&parentid=".
											$_GET["folder_sel"]."&id=".$this->id));
				break;
			case "folder":
				$t = new Template("templates/");
				$t->set_file("page","admin_splash.html");
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "folder_config":
				$t = new Template("templates/");
				if ($this->id == 0) {
					$t->set_file("page","admin_folder_neuer.html");
     			}else{
				 	$t->set_file("page","admin_folder.html");
				}
				$t->set_var(array("post"=>$PHP_SELF,
							"fname"=>$this->fname($_GET["fname"]),
							"sessid"=>fu(),
							"id"=>$this->id,
							"action"=>"page",
						 	"action2"=>"folder_config",
						 	"parentid"=>$this->parentid()
						));
				$t->parse("out", "page");
				$t->p("out");
				break;
    		case "config":
    			$ptype = $this->get_page_type();
				$wptype = $_GET["ptype"];

				if ($ptype == "" AND $wptype=="") {
					// Eine neue Seite soll erstellt werden.
    				$t = new Template("templates/");
					$t->set_file("page","admin_pageformat_waehlen.html");

					$t->set_var(array("post"=>$PHP_SELF,
											"action"=>"page",
											"action2"=>"config",
											"pid"=>$this->id,
											"parentid"=>$this->parentid(),
											"sessid"=>fu()
     								));
             	$t->set_block("page", "list", "lst");
					$mdl = new modules();
					$dbl = $mdl->getAviableModules($this->site->id);
					while($dbl->next_record()) {
						$t->set_var(array("list_ptype"=>$dbl->f("ptype"),
												"list_pname"=>$dbl->f("mname")
										));
						$t->parse("lst", "list",true);
      			}
     				$t->parse("out", "page");
					$t->p("out");
     			}else{
					if ($ptype=="HTML"){$wptype="HTML";}
					if ($wptype!="HTML") {
						if ($wptype=="") {$wptype=$ptype;}
     					// MODULE WERDEN HIER AUFGERUFEN !!!!
						$mdl = new modules();
						$clsname = $mdl->getClassnameForType($wptype);
						eval('$mod = new module_'.$clsname.'();');
						$mod->admin_panel_page_prepare($this->id, $this->parentid());
					}else{
						// Ist der "Ändern Button"
						$t = new Template("templates/");
						$t->set_file("page","admin_page_aendern.html");

						if ($_REQUEST["Submit"]=="Submit") {
      					$this->isMultiLanguage($_REQUEST["ismultilanguage"]);
						}
						if ($this->isMultiLanguage()) {
							$t->set_var("ismultilanguage_checked", "CHECKED");
						}else{
							$t->set_var("ismultilanguage_checked", "");
						}
						$t->set_var(array(
										"pname"=> $this->pname($_GET["pname"]),
										"purl"=> $this->url($_GET["purl"]),
										"action"=>"page",
										"action2"=>"config",
										"back_lnk"=>u($PHP_SELF."?action=site"),
										"pid"=>$this->id,
										"lnk_tree"=>"admin.php?action2=struct",
										"parentid"=>$this->parentid(),
										"post"=>$PHP_SELF,
										"sessid"=>fu()
										));

						if ($this->id == 0) {
							$auth->r("page", "addpage",  true);
							// Wenn noch keine ID, dann wird der Pfad nicht angezeigt und danach erzeugt
							$t->set_block("page", "idnull", "idn");
							$t->set_var("idn", "");
							$t->set_block("page", "idnull2", "idn2");
							$t->set_var("idn2", "");
						}
						//COPY
						if ($auth->r("page", "copy")) {
							$t->set_var("lnk_copy", u($PHP_SELF."?action=page&action2=copy&parentid=".$this->parentid().
																"&id=".$this->id));
						}  else {
							$t->set_block("page", "copy", "cpy");
							$t->set_var("cpy", "");
						}
						// MOVE
						if ($auth->r("page", "move")) {
							$t->set_var("move_action2", "move");
							$t->set_block("page", "folder_list", "flist");

							if ($this->parentid()>0) {
								$t->set_var("fid", "0");
								$t->set_var("fname", "Homepage");
								$t->parse("flist", "folder_list", true);
							}
							$pid = $this->parentid();
							$this->db->query("SELECT id, fname FROM folder WHERE sid = ".$this->site->id);
							while ($this->db->next_record()) {
								if ($this->db->f("id")!=$pid) {
									$t->set_var("fid", $this->db->f("id"));
									$t->set_var("fname", $this->db->f("fname"));
									//$t->set_var("fselected", "move");
									$t->parse("flist", "folder_list", true);
								}
							}

						}  else {
							$t->set_block("page", "move", "mv");
							$t->set_var("mv", "");
						}

						if ($this->has_data()) {
							// Wenn schon Daten vorhanden sind, werden die Templates nicht mehr aufgelistet
							// Wenn schon Daten eingetragen wurden, kann das Template nicht mehr ausgewählt werden !
							$t->set_block("page", "selecttemplate", "selecttemplated");
							$t->set_var("selecttemplated", "");

							$tpl = new cmstemplate($this->tid());
							$t->set_var("tsname", $tpl->tname());
						}elseif ($this->id != 0) {
							// Templates auflisten
							$t->set_block("page", "list", "tlist");
							$this->db->query(sprintf("SELECT id, tname FROM template WHERE sid = %s", $this->site->id));
							while ($this->db->next_record()){
								$tid = $this->tid($_GET["tid"]);
								if ($tid == $this->db->f("id")) {
									$sel = "SELECTED";
								}else{
									$sel = "";
								}
								$t->set_var(array("tname"=>$this->db->f("tname"),
											"id"=>$this->db->f("id"),
											"selected"=>$sel));
								$t->parse("tlist", "list", true);
							}
						}
						$t->parse("out", "page");
						$t->p("out");
      			}
     			}
				break;
			default:
				if ($this->get_page_type()!="HTML") {
					// MODULE WERDEN HIER AUFGERUFEN !!!!
					$mdl = new modules();
					$clsname = $mdl->getClassnameForType($this->get_page_type());
					eval('$mod = new module_'.$clsname.'();');
					$mod->admin_panel_page_prepare($this->id, $this->parentid());
    				//echo "Dies ist ein Modul, bitte auf Ändern zum Konfigurieren klicken";
				}else{
					$t = new Template("templates/");
					$t->set_file("page","admin_page.html");

					// ###############################################################################################
					// ##################################### LANGUAGE HANDLING #######################################
					// ###############################################################################################
					$t->set_var(array("action"=>"page",
										"pid"=>$this->id,
										"post"=>$PHP_SELF,
										"sessid"=>fu()
									));

				/*	$t->set_block("page", "lange", "lalilu");
					if ($this->isMultiLanguage()) {
					$arr = $this->site->getLanguageAviable();
						foreach($arr as $key=>$val) {
							if ($this->language == $val) {
								$rsl = "SELECTED";
							}else{
								$rsl = "";
							}
							$t->set_var(array("lang_short"=>$val,
												"lang_selected"=>$rsl,
												"lang_name"=>$this->site->getLanguageName($val)
												));
							$t->parse("lalilu","lange", true);
						}
					}else{
						$t->set_block("page", "multilange", "blabla");
						$t->set_var("blabla","");
					}    */

					$t->set_block("page", "multilang", "lalilu");
					if ($this->isMultiLanguage()) {
						$arr = $this->site->getLanguageAviable();
						foreach($arr as $key=>$val) {
							$pg = new page($this->id, $val);
							if ($pg->isEmpty()) {
								$fill = "b";
        					}else{
								$fill = "a";
        					}
							$t->set_var(array("lang_short"=>$val,
												"lang_filled"=>$fill,
												"lang_link"=>u($PHP_SELF."?action=page&id=".$this->id."&language=".$val)
												));
							$t->parse("lalilu","multilang", true);
						}
						$t->set_var("lang_aktuell", $this->site->getLanguageName($this->language));
					}else{
						$t->set_block("page", "multilange", "blabla");
						$t->set_var("blabla","");
					}

					// ###############################################################################################
					// ###############################################################################################
					// ###############################################################################################

					// Elementliste für das ausgewählte Template zeigen
					$t->set_block("page", "ellist", "iellist");
					$this->err->debug($this->tid());
					$tpl = new cmstemplate($this->tid());
					$dbb = $tpl->get_all_element_lists();
					while ($dbb->next_record()) {
						$t->set_var(array("elplaceholder"=> $dbb->f("elplaceholder"),
										"id"=> $dbb->f("id"),
										"lnk_el"=>u($PHP_SELF."?action=el&action2=insert&tid=".$this->tid().
													"&elid=".$dbb->f("id")."&pid=".$this->id."&language=".$this->language)
										));
						$t->parse("iellist", "ellist", true);
					}

					if ($this->site->createadmin == 0) {
						$t->set_block("page", "masteradmin", "madm");
						$t->set_var(array("madm"=>"",
												"lnk_preview"=>u("publish.php?action=preview&pid=".$this->id."&language=".$this->language)
						));
					}

					$t->parse("out", "page");
					$t->p("out");
     			}
		}
	}
}
?>