<?
/*##################### Pagix Content Management System #######################
$Id: element_text.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_text.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:57  skulawik
Updated Versionsinfos

###############################################################################
Element: Text (Basiselement)
#############################################################################*/
class element_text extends element
{
	var $edid;
	var $data;
	var $db;
	var $el;
	var $pid;
	// Beispielklasse für das erste Element
	// HTML Vorlage für ADMIN ist admin_element_text.html
	function element_text($edid,$language="") {
		global $err, $el, $pid;
		$this->err = $err;
		$this->db = new DB_CMS;
		$this->el = $el;
		$this->pid = $pid;
		
		if ($edid=="" OR $edid < 0) {
			$this->err->raise_fatal("CLASS element_text::element_text", "No such ID given !");
		}
		$this->edid = $edid;
		$this->el_constructor($language);
	}
	
	function data($data = "") {
		if ($data!="") {
			$this->set_splitdata("data", $data);
		}
		return $this->get_splitdata("data");
	}

	function isEmpty() {
		// Gibt zurück, ob das Element gefüllt ist. Sollte im Regelfall überschrieben werden
		if ($this->data()=="") {
			return true;
		}else{
			return false;
		}
	}

	function admin_panel() {
		global $HTTP_POST_VARS, $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_text.html");
		if ($HTTP_POST_VARS["id"] == $this->edid) {
			$this->data($HTTP_POST_VARS["data"]);
		}
		$t->set_var(array("id"=>$this->edid,
					"data"=>$this->data(),
					"tid"=>$this->el->tid,
					"elid"=>$this->el->id,
					"language"=>$this->language,
					"pid"=>$this->pid,
					"sessid"=>fu(),
					"post"=>$PHP_SELF
			));
		$t->set_var("data",$this->data());
		$t->parse("out", "page");
		return $t->get("out");
	}
}
?>