<?
/*##################### Pagix Content Management System #######################
$Id: element_medialink.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_medialink.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Element: Media-Link
#############################################################################*/
class element_medialink extends element
{
	var $edid;
	var $linkadress;
	var $linkname;
	var $db;
	var $el;
	var $pid;
	// Beispielklasse für das erste Element
	// HTML Vorlage für ADMIN ist admin_element_text.html
	function element_medialink($edid,$language="") {
		global $err, $el, $pid, $site;
		$this->err = $err;
		$this->site = $site;
		$this->db = new DB_CMS;
		$this->el = $el;
		$this->pid = $pid;
		
		if ($edid=="" OR $edid < 0) {
			$this->err->raise_fatal("CLASS element_medialink::element_link", "No such ID given !");
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
		// Parsen der Zieladresse
		/*
		if ($site->demopublishing) {
			$siturl = $site->url_demo();
		}else{
			$siturl = $site->url();
		}
		if (right($siturl, 1) == "/") {$siturl = left($siturl, strlen($siturl) - 1);}*/
		$siturl = $site->get_baseurl();
		$dir = $this->path();

		if (left($dir, 1) == "/") {$dir = right($dir, strlen($dir) - 1);}
		$t->set_var("linkadress", $siturl.$_PHPLIB["dir_media"].$dir.$this->filename());
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

	function linkname($data = "{empty}") {
		if ($data!="{empty}") {
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
	
	function path($data = "{empty}") {
		if ($data!="{empty}") {
			$this->set_splitdata("path", $data);
		}
		return $this->get_splitdata("path");
	}

	function filename($data = "{empty}") {
		if ($data!="{empty}") {
			$this->set_splitdata("filename", $data);
		}
		return $this->get_splitdata("filename");
	}
	
	function admin_panel() {
		global $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_medialink.html");
		if ($_REQUEST["id"] == $this->edid) {
			$this->linkname($_REQUEST["linkname"]);
			$this->popupwin($_REQUEST["popupwin"], True);
			$this->path($_REQUEST["path"]);
			$this->filename($_REQUEST["filename"]);
		}
		$t->set_block("page", "folderlist", "listf");
		if (isset($_REQUEST["path"]) and $_REQUEST["path"]!="") {
			$path = $_REQUEST["path"];
		}elseif ($this->path() != "") {
			$path = $this->path();
		}else{
			//$path = "/";
			$t->set_var("val", "");
			$t->set_var("foldername", "");
			$t->parse("listf", "folderlist", true);
		}

		$this->db->query(sprintf("SELECT path FROM media WHERE sid = %s GROUP BY path",$this->site->id));
		while ($this->db->next_record()) {
			$t->set_var("val", $this->db->f("path"));
			$t->set_var("foldername", $this->db->f("path"));
			//if ($path ==""){$path=$this->db->f("path");}
			if ($path == $this->db->f("path")) {
				$t->set_var("sele", "SELECTED");
			}else{
				$t->set_var("sele", "");
			}
			$t->parse("listf", "folderlist", true);
		}

		$t->set_block("page", "filelist", "listfi");
		$this->db->query(sprintf("SELECT filename FROM media WHERE sid = %s AND path = '%s' ORDER BY filename",
								$this->site->id, $path));
		while ($this->db->next_record()) {
			$t->set_var("fval", $this->db->f("filename"));
			$t->set_var("filename", $this->db->f("filename"));
			if ($this->filename()=="") {$this->filename($this->db->f("filename"));}

			if ($this->filename() == $this->db->f("filename")) {
				$t->set_var("fsele", "SELECTED");
			}else{
				$t->set_var("fsele", "");
			}

			$t->parse("listfi", "filelist", true);
		}

		$t->set_var(array("id"=>$this->edid,
					"linkname"=>$this->linkname(),
					"popupwin"=>$this->popupwin(),
					"language"=>$this->language,
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