<?
/*##################### Pagix Content Management System #######################
$Id: class.template.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.template.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Klasse cmstemplate
#############################################################################*/
class cmstemplate
{
	var $id = 0;
	var $site;
	var $err;
	var $db;
	var $tpldir;
	var $tname;
	var $filename;
	
	function cmstemplate($tids=0) {
		// CONSTRUCTOR
		global $site, $err, $db, $_PHPLIB, $tid;
		
		$this->site = $site;
		$this->err = $err;
		$this->db = $db;
		if ($tids > 0 and $tids!="") {$this->id = $tids;}
		$this->tpldir=$_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_templates"];
		$this->check_directory($this->tpldir);
	}
	
	function has_elementlist($rid) {
		//Überprüft, ob eine Elementliste in der Datenbank für das Template definiert wurde
		if ($rid=="") {$localid = $this->id;}else{$localid = $rid;}
		$dbb = new DB_CMS;
		$dbb->query(sprintf("SELECT count(*) as menge FROM template_elements_list WHERE tid = ".$rid,
							$this->site->id));
		$dbb->next_record();
		if ($dbb->f("menge")>0) {return "Ja";}else{return "Nein";}			
	}
	
	function exists_in_db($templatename) {
		$this->db->query(sprintf("SELECT count(*) as menge FROM template WHERE sid = %s AND filename = '%s'",
								$this->site->id, $templatename));
		$this->db->next_record();
		if ($this->db->f("menge") == 0) {
			return FALSE;
		}else{
			return TRUE;
		}
	}
	
	function exists_in_fs($templatename) {
		$ret = FALSE;
		$d = dir($this->tpldir);
		while($entry=$d->read()) {
			if ($entry==$templatename) {
				$ret = TRUE;
				break;
			}
		}
		return $ret;
	}
	
	function add($templatename) {
		if ($this->exists_in_fs($templatename)) {
			$this->db->query(sprintf("INSERT INTO template (id, sid, tname, filename) ".
									"VALUES (NULL, %s, '%s', '%s')",
									$this->site->id, $templatename, $templatename));
		}
	}
	
	function check_directory($directory) {
		if (!is_dir($directory)){
			mkdir($directory, 0777);
		}
	}
	
	function delete_dbentry() {
		$this->db->query(sprintf("SELECT * FROM template WHERE sid = %s", $this->site->id));
		while ($this->db->next_record()) {
			if (!file_exists($this->tpldir.$this->db->f("filename"))){
				$this->delete($this->db->f("id"));
			}
		}
	}
	
	function delete($tid) {
		// ELEMENTLISTE LÖSCHEN
		// TEMPLATE LÖSCHEN
		$dbb = new DB_CMS;
		$dbd = new DB_CMS;
		$dbd->query(sprintf("SELECT id FROM template_elements_list WHERE tid = %s", $tid));
		while ($dbd->next_record()) {
			$dbb->query("DELETE FROM template_elements_allowed WHERE elid = ".$dbd->f("id"));
		}
		$dbb->query("DELETE FROM template_elements_list WHERE tid = ".$tid);
		$dbb->query("DELETE FROM template WHERE id = ".$tid);
	}
	
	function tname($tname = "") {
		// Reads or sets the current pagename for this Page
		if ($tname!="") {
			$this->tname = $tname;
			if ($this->id == 0) {
				// Hier keine Ausgabe möglich
			}else{
				$this->db->query(sprintf("UPDATE template SET tname = '%s' WHERE id = %s"
								,$this->tname, $this->id));
			}
		}
		$this->db->query(sprintf("SELECT tname FROM template WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->tname = $this->db->f("tname");
		return $this->tname;
	}
	
	function filename() {
		$this->db->query(sprintf("SELECT filename FROM template WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->filename = $this->db->f("filename");
		return $this->filename;
	}
	
	function get_all_element_lists() {
		$dbb = new DB_CMS;
		$dbb->query("SELECT * FROM template_elements_list WHERE tid = ".$this->id);
		return $dbb;
	}
	
	function admin_panel() {
		global $HTTP_POST_VARS, $PHP_SELF;
		// Paints the current admin panel for a template or lists the current aviable templates
		// for one Site !
		$this->err->d("TID:".$this->id);
		if ($this->id==0) {
			// Paint the Template List
			$this->admin_panel_list();
		}else{
			// Ein definiertes Template editieren
			$t = new Template("templates/");
			$t->set_file("page","admin_template_edit.html");
			$t->set_block("page", "list", "tlist");
			$t->set_var(array("tname"=>$this->tname($HTTP_POST_VARS["tname"]),
							"filename"=>$this->filename(),
							"lnk_elnew"=>u($PHP_SELF."?action=el&elid=0&tid=".$this->id),
							"lnk_back"=>u($PHP_SELF."?action=template"),
							"tid"=>$this->id,
							"post"=>$PHP_SELF,
							"sessid"=>fu(),
							"action"=>"template"
							));
			$this->db->query(sprintf("SELECT * FROM template_elements_list WHERE tid = %s", $this->id));
			while ($this->db->next_record()) {
				$t->set_var(array("elplaceholder"=>$this->db->f("elplaceholder"),
								"lnk_eledit"=>u($PHP_SELF."?action=el&elid=".$this->db->f("id")."&tid=".$this->id),
								"lnk_eldelete"=>u($PHP_SELF."?action=el&action2=delete&elid=".$this->db->f("id")."&tid=".$this->id)
								));
				$t->parse("tlist", "list", true);
			}
		//if (!$found) {$t->set_var("tlist","");}
			$t->parse("out", "page");
			$t->p("out");
		}
	}
	
	function admin_panel_list() {
		global $HTTP_POST_VARS, $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_template.html");
		$t->set_var("lnk_back",u($PHP_SELF));
		$t->set_block("page", "list", "tlist");
		$this->delete_dbentry();
		$this->db->query(sprintf("SELECT * FROM template WHERE sid = %s", $this->site->id));
		// Erst einmal werden alle Datensätze durchsucht und die, die im Filesystem nicht mehr
		// existieren gelöscht
		// Erst einmal werden alle aus der Datenbank ausgegeben
		while ($this->db->next_record()) {
			$t->set_var(array("tedit_lnk"=>u($PHP_SELF."?action=template&tid=".$this->db->f("id")),
							  "tname"=>$this->db->f("tname"),
							  "new"=>"Nein",
							  "path"=>$this->tpldir.$this->db->f("filename"),
							  "elexists"=>$this->has_elementlist($this->db->f("id"))
						));
			$t->parse("tlist", "list", true);
		}
		$d = dir($this->tpldir);
		while($entry=$d->read()) {
			if (substr($entry,0,1)!=".") {
				if ($this->exists_in_db($entry)==false and is_dir($this->tpldir.$entry)==false) {
					$this->add($entry);
					$t->set_var(array("tedit_lnk"=>u($PHP_SELF."?action=template&tid="),
								  "tname"=>$entry,
								  "new"=>"Ja",
								  "path"=>$this->tpldir.$entry,
								  "elexists"=>"Nein"
							));
					$t->parse("tlist", "list", true);
				}
			}
		}
		$t->parse("out", "page");
		$t->p("out");
	}
}

?>