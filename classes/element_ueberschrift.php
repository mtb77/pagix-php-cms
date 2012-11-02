<?
/*##################### Pagix Content Management System #######################
$Id: element_ueberschrift.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_ueberschrift.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:57  skulawik
Updated Versionsinfos

###############################################################################
Element: Überschrift
#############################################################################*/
class element_ueberschrift extends element_text
{
	function element_ueberschrift($edid, $language="") {
 		$this->element_text($edid,$language);
	}

	function admin_panel() {
		global $HTTP_POST_VARS, $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_ueberschrift.html");
		if ($HTTP_POST_VARS["id"] == $this->edid) {
			$this->data($HTTP_POST_VARS["data"]);
		}
		$t->set_var(array("id"=>$this->edid,
					"language"=>$this->language,
					"data"=>$this->data(),
					"tid"=>$this->el->tid,
					"elid"=>$this->el->id,
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