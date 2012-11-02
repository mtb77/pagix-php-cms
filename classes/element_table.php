<?
/*##################### Pagix Content Management System #######################
$Id: element_table.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_table.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:57  skulawik
Updated Versionsinfos

###############################################################################
Element: Table
#############################################################################*/
class element_table extends element
{
	var $edid;
	var $data;
	var $db;
	var $el;
	var $pid;

	function element_table($edid,$language="") {
		global $err, $el, $pid;
		$this->err = $err;
		$this->db = new DB_CMS;
		$this->el = $el;
		$this->pid = $pid;

		if ($edid=="" OR $edid < 0) {
			$this->err->raise_fatal("CLASS element_table::element_table", "No such ID given !");
		}
		$this->edid = $edid;
		$this->el_constructor($language);
	}

	function isEmpty() {
		// Gibt zurück, ob das Element gefüllt ist. Sollte im Regelfall überschrieben werden
		$res = "";
		for ($i=1;$i<=$this->zeilen();$i++) {
			for ($j=1;$j<=$this->spalten();$j++) {
				$res = $res . $this->data("","0".$i."0".$j);
			}
   	}
		if ($res=="") {
			return true;
		}else{
			return false;
		}
	}

	function paint() {
		// eigene Paint Prozedur
		global $_PHPLIB;
		$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
		$t->set_file("page",$this->etemplatefilename());
		//$t->set_var("data", nl2br($this->data()));
		$t->set_block("page", "spalte", "sp");
		$t->set_block("page", "zeile", "ze");

		for ($i=1;$i<=$this->zeilen();$i++) {
			for ($j=1;$j<=$this->spalten();$j++) {
				$textvar = $this->data("","0".$i."0".$j);
				if (ltrim($textvar) == "") {$textvar = "&nbsp;";}
				if (($i==1) AND $this->ueberschrift() == "1") {$textvar = "<B>".$textvar."</B>";}
				$t->set_var("textfeld", $textvar);
				if ($j==1){ // Liste löschen... Weiss der Herrgott, warum er die da anfügt....
					$t->parse("sp","spalte");
				}else{
					$t->parse("sp","spalte",true);
				}
			}
			$t->parse("ze","zeile",true);
		}
		$t->parse("out", "page");
		return $t->get("out");
	}

	function data($data = "", $zeilspalte) {
		if ($data!="") {
			$this->set_splitdata("data".$zeilspalte, $data);
		}
		return $this->get_splitdata("data".$zeilspalte);
	}

	function ueberschrift($ueberschrift = "empty") {
		if ($ueberschrift!="empty") {
			$this->set_splitdata("ueberschrift", $ueberschrift);
		}
		return $this->get_splitdata("ueberschrift");
	}
	
	function zeilen($zeilen = "") {
		if ($zeilen!="") {
			$this->set_splitdata("zeilen", $zeilen);
		}
		return $this->get_splitdata("zeilen");
	}

	function spalten($spalten = "") {
		if ($spalten!="") {
			$this->set_splitdata("spalten", $spalten);
		}
		return $this->get_splitdata("spalten");
	}

	function admin_panel() {
		global $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_tabelle.html");
		if ($_REQUEST["zeilen"]!="") 	{$this->zeilen($_REQUEST["zeilen"]);}
		if ($this->zeilen()=="") 		{$this->zeilen(2);}
		if ($_REQUEST["spalten"]!="") {$this->spalten($_REQUEST["spalten"]);}
		if ($this->spalten()=="") 		{$this->spalten(2);}
		if ($_REQUEST["zeilen"]!="") {$this->ueberschrift($_REQUEST["ueberschrift"]);}
		$t->set_block("page", "spalte", "sp");
		$t->set_block("page", "zeile", "ze");

		for ($i=1;$i<=$this->zeilen();$i++) {
			for ($j=1;$j<=$this->spalten();$j++) {
				if ($_REQUEST["id"] == $this->edid) {
					$this->data($_REQUEST["textfeld"]["0".$i."0".$j], "0".$i."0".$j);
				}
				$t->set_var("fid","0".$i."0".$j);
				$t->set_var("vall",$this->data("","0".$i."0".$j));
				if ($j==1){ // Liste löschen... Weiss der Herrgott, warum er die da anfügt....
					$t->parse("sp","spalte");
				}else{
					$t->parse("sp","spalte",true);
				}
			}
			$t->parse("ze","zeile",true);
		}
		if ($this->ueberschrift()=="1") { $t->set_var("ueberschrift", "checked"); }
		$t->set_var("z".$this->zeilen(), "SELECTED");
		$t->set_var("s".$this->spalten(), "SELECTED");
		$t->set_var(array("id"=>$this->edid,
					"tid"=>$this->el->tid,
					"language"=>$this->language,
					"elid"=>$this->el->id,
					"pid"=>$this->pid,
					"sessid"=>fu(),
					"post"=>$PHP_SELF
			));
		//$t->set_var("data",$this->data());
		$t->parse("out", "page");
		return $t->get("out");
	}
}
?>