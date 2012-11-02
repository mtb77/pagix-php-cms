<?
/*##################### Pagix Content Management System #######################
$Id: element_mediapicture.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_mediapicture.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Element: Mediapicture
#############################################################################*/
class element_mediapicture extends element_medialink
{
	var $edid;
	var $linkadress;
	var $linkname;
	var $db;
	var $el;
	var $pid;

	function element_mediapicture($edid,$language="") {
		// Oberen Constructor aufrufen
		$this->element_medialink($edid,$language);
	}

	function link_janein($data = "{empty}") {
		if ($data!="{empty}") {
			$this->set_splitdata("link_janein", $data);
		}
		return $this->get_splitdata("link_janein");
	}

	function link_extern($data = "{empty}") {
		if ($data!="{empty}") {
			$this->set_splitdata("link_extern", $data);
		}
		return $this->get_splitdata("link_extern");
	}
	
	function link_intern($data = "{empty}") {
		if ($data!="{empty}") {
			$this->set_splitdata("link_intern", $data);
		}
		return $this->get_splitdata("link_intern");
	}

	function paint() {
		// eigene Paint Prozedur
		global $_PHPLIB, $site;

		if ($this->filename() != "") {
			$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
			$t->set_file("page",$this->etemplatefilename());
			$t->set_var("linkname", $this->linkname());
			// Parsen der Zieladresse
			$dir = $this->path();
			if (left($dir, 1) == "/") {$dir = right($dir, strlen($dir) - 1);}
			$siteurl = $site->get_baseurl();
			$t->set_var("pictureadress", $siteurl.$_PHPLIB["dir_media"].$dir.$this->filename());

			if ($this->link_extern() != "") {								// Externe URL
				$t->set_var("linkadress",$this->link_extern());
			} else {
				if ($this->link_intern() != "") {
					$pg2 = new page($this->link_intern());
					$dir = $pg2->get_current_dir($pg2->parentid(), "");
					if ($pg2->url()=="") {										// Wenn keine URL, dann keinen Link angeben
						$t->set_block("page", "link", "ll");
						$t->set_block("page", "linke", "le");
						$t->set_var("ll","");
						$t->set_var("le","");
					}else{														// Interne URL
						$t->set_var("linkadress", $siteurl.$dir.$pg2->url());
						//$t->parse("page","ll");
					}
				} else {
					$t->set_block("page", "link", "ll");
					$t->set_block("page", "linke", "le");
					$t->set_var("ll","");
					$t->set_var("le","");
				}
			}
			$t->parse("out", "page");
			return $t->get("out");
		}
	}

	function admin_panel() {
		global $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_mediapicture.html");

		if ($_POST["id"] == $this->edid) {
			// Alle Settings werden innerhalb der Abfrage ID = ME gemacht.
			// (Reanto-Bug vom 22.1.02 (Alle Elemente des selben Typs ändern sich sonst auf einer Seite)
			$this->linkname($_POST["linkname"]);
			$this->popupwin($_POST["popupwin"], True);
			$this->path($_POST["path"]);
			$this->filename($_POST["filename"]);
			if ($this->link_janein($_POST["linkjn"])==""){$this->link_janein("nein");}		//DEFAULT: NEIN

			switch ($_POST["link"]) {
				case "i":
					$this->link_extern("");
					$this->link_intern($_POST["link_intern"]);
					break;
				case "e":
					$this->link_extern($_POST["link_extern"]);
					$this->link_intern("");
					break;
			}
		}
		$selitem = "k";
		$t->set_block("page", "folderlist", "listf");

		if (isset($_POST["path"]) and $_POST["id"] == $this->edid) {
			$path = $_POST["path"];
		}elseif ($this->path() != "") {
			$path = $this->path();
		}else{
			$path = "/";
		}

		// BILD FILEPATH
		$this->db->query(sprintf("SELECT path FROM media WHERE sid = %s GROUP BY path",$this->site->id));
		while ($this->db->next_record()) {
			$t->set_var("val", $this->db->f("path"));
			$t->set_var("foldername", $this->db->f("path"));
			if ($path == $this->db->f("path")) {
				$t->set_var("sele", "SELECTED");
			}else{
				$t->set_var("sele", "");
			}
			$t->parse("listf", "folderlist", true);
		}

		// BILD FILENAME
		$t->set_block("page", "filelist", "listfi");
		$this->db->query(sprintf("SELECT filename FROM media WHERE sid = %s AND path = '%s' ".
								"AND ftype IN ('jpg', 'png', 'gif') ORDER BY filename",
								$this->site->id, $path));
		while ($this->db->next_record()) {
			$t->set_var("fval", $this->db->f("filename"));
			$t->set_var("filename", $this->db->f("filename"));

			if ($this->filename() == $this->db->f("filename")) {
				$t->set_var("fsele", "SELECTED");
			}else{
				$t->set_var("fsele", "");
			}

			$t->parse("listfi", "filelist", true);
		}

		$t->set_block("page", "adress", "la");
		$t->set_block("page", "linker", "linkbereich");

		// INTERNER LINK
		$this->db->query("SELECT * FROM page WHERE sid = ".$this->site->id);
		while($this->db->next_record()) {
			if ($this->db->f("id") != $this->pid) {
				$t->set_var(array("adress_id"=>$this->db->f("id"),
								  "adress_name"=>$this->db->f("pname")
							));
				if ($this->link_intern() == $this->db->f("id")) {
					$t->set_var("asele","selected");
					$selitem = "i";
				}else{
					$t->set_var("asele","");
				}
				$t->parse("la", "adress", true);
			}
		}

		if ($this->link_extern() != "") {$selitem = "e";}
		switch ($selitem) {
			case "i":
				$t->set_var(array("ichecked"=>"checked",
								"echecked"=>""));
				break;
			case "e":
				$t->set_var(array("ichecked"=>"",
								"echecked"=>"checked"));
				break;
		}

		if ($this->link_janein() == "ja") {
			$t->set_var("linkjchecked", "checked");
   		$t->parse("linkbereich", "linker");
		}else{
			$t->set_var("linknchecked", "checked");
   	}

		$t->set_var(array("id"=>$this->edid,
					"linkname"=>$this->linkname(),
					"link_extern"=>$this->link_extern(),
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