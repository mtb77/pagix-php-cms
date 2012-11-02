<?
/*##################### Pagix Content Management System #######################
$Id: element_interna_link.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_interna_link.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.6  2002/04/29 09:19:49  skulawik
no message

Revision 2.5  2002/04/29 09:18:41  skulawik
no message

Revision 2.4  2002/04/29 09:17:43  skulawik
no message

Revision 2.3  2002/04/19 09:10:01  skulawik
*** empty log message ***

Revision 2.2  2002/04/19 09:07:56  skulawik
Fehler wenn der User keine Unterverzeichnisse angelegt hatte im Editieren Modus

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Element: interner Link
#############################################################################*/
class element_interna_link extends element
{
	var $edid;
	var $linkadress;
	var $linkname;
	var $db;
	var $el;
	var $pid;
	// Beispielklasse für das erste Element
	// HTML Vorlage für ADMIN ist admin_element_text.html
	function element_interna_link($edid,$language="") {
		global $err, $el, $pid, $site;
		$this->err = $err;
		$this->db = new DB_CMS;
		$this->el = $el;
		$this->pid = $pid;
		$this->site = $site;
		
		if ($edid=="" OR $edid < 0) {
			$this->err->raise_fatal("CLASS element_interna_link::element_link", "No such ID given !");
		}
		$this->edid = $edid;
		$this->el_constructor($language);
	}

	function paint() {
		// eigene Paint Prozedur
		global $_PHPLIB, $site;
		$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
		$t->set_file("page",$this->etemplatefilename());
		$t->set_var("linkname", $this->linkname());
		// Herausfinden der URL
		$intpid = $this->linkadress();
		if ($intpid!="") {
			$pg2 = new page($intpid);
			$dir = $pg2->get_current_dir($pg2->parentid(), "");
			if (left($dir, 1) == "/") {$dir = right($dir, strlen($dir) - 1);}
			if ($site->demopublishing) {
				$siturl = $site->url_demo();
			}else{
				$siturl = $site->url();
			}
			// ###############################MULTILANGUAGE##########################################
			if ($pg2->isMultiLanguage()) {
				if ($this->linklanguage()!="") {
					$purl = $pg2->url().".".$this->linklanguage();
     			}else{
					$purl = $pg2->url();
				}
    		}else{
				$purl = $pg2->url();
    		}
			// ######################################################################################
			$t->set_var("linkadress", $siturl.$dir.$purl);
			if ($this->popupwin() == "CHECKED") {$t->set_var("popupwin", 'Target="_blank"');}
			$t->parse("out", "page");
			return $t->get("out");
		}else{
			return "";
		}
	}

	function isEmpty() {
		// Gibt zurück, ob das Element gefüllt ist. Sollte im Regelfall überschrieben werden
		if ($this->linkname()=="") {
			return true;
		}else{
			return false;
		}
	}

	function linkadressfolder() {
		$dbb = new DB_CMS;
		$dbb->query("SELECT parentid FROM page WHERE id = '".$this->linkadress()."'");
		$dbb->next_record();
		if ($dbb->f("parentid") == "") {
			return "0";
   	}else{
			//echo "e".$dbb->f("parentid");die;
			return $dbb->f("parentid");
   	}
	}

	function linkadress($data = "") {
		if ($data!="") {
			$this->set_splitdata("linkadress", $data);
		}
		return $this->get_splitdata("linkadress");
	}

	function linklanguage($data = "") {
		if ($data!="") {
			$this->set_splitdata("linklanguage", $data);
		}
		return $this->get_splitdata("linklanguage");
	}

	function linkname($data = "") {
		if ($data!="") {
			$this->set_splitdata("linkname", $data);
		}
		return $this->get_splitdata("linkname");
	}

	function linkpath($data = "") {
		if ($data!="") {
			$this->set_splitdata("linkpath", $data);
		}
		return $this->get_splitdata("linkpath");
	}

	function popupwin($data = "", $set=False) {
		if ($set) {$this->set_splitdata("popupwin", $data);}
		if ($this->get_splitdata("popupwin") == "1") {
			$ret = "CHECKED";
		}
		return $ret;
	}

	function parsefolder($parentid, $level=0) {
		$db = new DB_CMS;
		$db->query(sprintf("SELECT * FROM folder WHERE sid = %s AND parentid = %s", $this->site->id, $parentid));
		while($db->next_record()) {
			$resp[$db->f("id")]["fname"] = str_repeat("-", $level+1).$db->f("fname");
			$resp[$db->f("id")]["id"] = $db->f("id");

			//echo $resp[$db->f("id")]."<br>";
			$resp2 = $this->parsefolder($db->f("id"), $level + 1);
			$resp = array_merge($resp, $resp2);

		}
		return $resp;
	}

	function admin_panel() {
		global $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_interna_link.html");
		if ($_REQUEST["id"] == $this->edid) {
			$this->linkname($_REQUEST["linkname"]);
			$this->linkadress($_REQUEST["linkadress"]);
			$this->linkpath($_REQUEST["linkpath"]);
			$this->popupwin($_REQUEST["popupwin"], True);
		}
		$t->set_var(array("id"=>$this->edid,
					"language"=>$this->language,
					"linkname"=>$this->linkname(),
					"popupwin"=>$this->popupwin(),
					"tid"=>$this->el->tid,
					"elid"=>$this->el->id,
					"pid"=>$this->pid,
					"sessid"=>fu(),
					"post"=>$PHP_SELF
			));
		$t->set_block("page", "adress", "la");
		$t->set_block("page", "folder", "fo");
		// ##################################### LANGUAGE HANDLING #######################################
		$t->set_block("page", "lang", "lalilu");
		$intpid = $this->linkadress();
		if ($intpid!="") {
			$pg2 = new page($intpid);
			if ($pg2->isMultiLanguage()) {
				$arr = $this->site->getLanguageAviable();

				if ($_REQUEST["llanguage"] != "") {$this->linklanguage($_REQUEST["llanguage"]);}
				$lang = $this->linklanguage();
				if ($lang == "") {$lang = $this->language;}

				foreach($arr as $key=>$val) {
					if ($lang == $val) {
						$rsl = "SELECTED";
					}else{
						$rsl = "";
					}
					$t->set_var(array("lang_short"=>$val,
											"lang_selected"=>$rsl,
											"lang_name"=>$this->site->getLanguageName($val)
											));
					$t->parse("lalilu","lang", true);
				}
    		} else {
				// Eine NON-MULTILANG Page
				$t->set_block("page", "multilang", "dadada");
				$t->set_var("dadada","");
			}
    	}
		// ###############################################################################################
		$t->set_var(array("folder_id"=>"0",
							  "folder_name"=>"Homepage"));
		if ($this->linkadressfolder() == "0" AND $_REQUEST["linkpath"]=="" and $_POST["id"] == $this->edid) {
			$t->set_var("fselected","SELECTED");
			$req = "0";
   	    }else{
			$t->set_var("fselected","");
			if ($_REQUEST["linkpath"] != ""  and $_POST["id"] == $this->edid) { 
                // DANN WIRD IN ERSTER LINIE DER POST BEARBEITET...(nich gespeichert aber angezeigt)
   			    $req = $_REQUEST["linkpath"];
			}else{
				$req = $this->linkadressfolder();
			}
		}
		$t->parse("fo", "folder", true);

		$dbb = new DB_CMS;

		$er = $this->parsefolder(0);
        if (is_array($er)) {
    		while(list($k,$v) = each($er)) {
	    		//echo $k.$v."<br>";
		    	$t->set_var(array("folder_id"=>$v["id"],
			    				  "folder_name"=>$v["fname"]
				    			));
    			if ($req == $v["id"]) {
    				$t->set_var("fselected","selected");
    			}else{
    				$t->set_var("fselected","");
    			}
    			$t->parse("fo", "folder", true);
        }
   	}

		$dbb->query("SELECT * FROM page WHERE sid = ".$this->site->id. " AND parentid = ".$req);
		while($dbb->next_record()) {
			if ($dbb->f("id") != $this->pid) {
				$t->set_var(array("adress_id"=>$dbb->f("id"),
								  "adress_name"=>$dbb->f("pname")
							));
				if ($this->linkadress() == $dbb->f("id")) {
					$t->set_var("selected","selected");
				}else{
					$t->set_var("selected","");
				}
				$t->parse("la", "adress", true);
			}
		}
		$t->parse("out", "page");
		return $t->get("out");
	}
}
?>