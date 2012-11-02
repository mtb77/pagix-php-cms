<?
/*##################### Pagix Content Management System #######################
$Id: element_link.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_link.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Element: Link
#############################################################################*/
class element_link extends element
{
	var $edid;
	var $linkadress;
	var $linkname;
	var $db;
	var $el;
	var $pid;
	// Beispielklasse für das erste Element
	// HTML Vorlage für ADMIN ist admin_element_text.html
	function element_link($edid,$language="") {
		global $err, $el, $pid;
		$this->err = $err;
		$this->db = new DB_CMS;
		$this->el = $el;
		$this->pid = $pid;
		
		if ($edid=="" OR $edid < 0) {
			$this->err->raise_fatal("CLASS element_link::element_link", "No such ID given !");
		}
		$this->edid = $edid;
		$this->el_constructor($language);
	}
	
	function paint() {
		// eigene Paint Prozedur
		global $_PHPLIB;
		$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
		$t->set_file("page",$this->etemplatefilename());
		$t->set_var("linkname", $this->linkname());
		$t->set_var("linkadress", $this->linkadress());
		if ($this->popupwin() == "CHECKED") {$t->set_var("popupwin", 'Target="_blank"');}
		$t->parse("out", "page");
		return $t->get("out");
	}

	function isEmpty() {
		// Gibt zurück, ob das Element gefüllt ist. Sollte im Regelfall überschrieben werden
		if ($this->linkname()=="") {
			return true;
		}else{
			return false;
		}
	}

	function linkadress($data = "") {
		if ($data!="") {
			$this->set_splitdata("linkadress", $data);
		}
		return $this->get_splitdata("linkadress");
	}

	function linkname($data = "") {
		if ($data!="") {
			$this->set_splitdata("linkname", $data);
		}
		return $this->get_splitdata("linkname");
	}
	
	function popupwin($data = "", $set=False) {
		if ($set) {$this->set_splitdata("popupwin", $data);}
		if ($this->get_splitdata("popupwin") == "1") {
			$ret = "CHECKED";
		}
		return $ret;
	}
	
	function admin_panel() {
		global $HTTP_POST_VARS;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_link.html");
		if ($HTTP_POST_VARS["id"] == $this->edid) {
			$this->linkname($HTTP_POST_VARS["linkname"]);
			$this->linkadress($HTTP_POST_VARS["linkadress"]);
			$this->popupwin($HTTP_POST_VARS["popupwin"], True);
		}
		$t->set_var(array("id"=>$this->edid,
					"linkname"=>$this->linkname(),
					"linkadress"=>$this->linkadress(),
					"language"=>$this->language,
					"popupwin"=>$this->popupwin(),
					"tid"=>$this->el->tid,
					"elid"=>$this->el->id,
					"pid"=>$this->pid,
					"sessid"=>fu(),
					"post"=>$PHP_SELF
			));
		$t->parse("out", "page");
		return $t->get("out");
	}
}
?>