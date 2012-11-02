<?
/*##################### Pagix Content Management System #######################
$Id: element_mediapicture_edit.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: element_mediapicture_edit.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Element: Mediapicture Edit
#############################################################################*/
class element_mediapicture_edit extends element_mediapicture
{
	var $mode;
	var $w;
	var $h;
	var $tid;
	var $elid;

	function element_mediapicture_edit($edid,$language="") {
		// Oberen Constructor aufrufen
		$this->element_mediapicture($edid,$language);
	}

	function setsizes($haveel=false) {
		if ($haveel==false) {
			$el = new elements_list($this->elid,$this->tid);
   	}else{
			$el = $this->el;
   	}
		$height=$el->property(2);
		$width=$el->property(1);
		$defheight=$el->property(4);
		$defwidth=$el->property(3);
		if($defheight!="") {
			//ES IST EINE DEFINIERTE HÖHE ANGEGEBEN,
			//ALSO ZÄHLT ENTWEDER EINE DEFINIERTE BREITE ODER VARIABLE BREITE
			$h=$defheight;
			if($defwidth!="") {
				//DEFINIERTE BREITE UND HÖHE
				$w=$defwidth;
				$mode="cuttomiddle";	//SCHNEIDE AUF MITTE ZURECHT ODER RESIZE, BIS ES PASST (WENN BILD ZU KLEIN)
			}else{
				//DEFINIERTE HÖHE UND VARIABLE BREITE
				$w=$width;
				$mode="defheight";
			}
		}else{
			//ES IST EINE VARIABLE HÖHE
			$h=$height;
			if($defwidth!="") {
				//DEFINIERTE BREITE
				$w=$defwidth;
				$mode="defwidth";
			}else{
				//BREITE AUCH VARIABEL
				$w=$width;
				$mode="resizetomax";
			}
		}
		if($h<1) {$h=9999;}
		if($w<1) {$w=9999;}
		if($w==9999 AND $h=9999) {$w=400;$h=400;}
		$this->mode = $mode;
		$this->w = $w;
		$this->h = $h;
	}

	function paint() {
		// eigene Paint Prozedur
		global $_PHPLIB, $site, $auth;
		$this->setsizes();

		$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_elements"]);
		$t->set_file("page",$this->etemplatefilename());
		$t->set_var("linkname", $this->linkname());
		// Parsen der Zieladresse
		$dir = $this->path();
		if (left($dir, 1) == "/") {$dir = right($dir, strlen($dir) - 1);}
		$siteurl = $site->get_baseurl();

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
		if ($this->filename() != "") {
			// DIESE PAINT PROZEDUR IST ETWAS ANDERS: DAS ENTSPRECHEND RESIZETE BILD WIRD
			// AUCH SCHON MIT AUF DEN SERVER GEPOSTET - DAHER VIELLEICHT ETWAS UNGEWÖHNLICH
			@mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_elmedia"], 0777);
			// ANPASSEN DES FILENAMES
			$typ=right($this->filename(), strlen($this->filename()) - (strrpos($this->filename(), ".") +1 ));
			$flenew = left($this->filename(), strrpos($this->filename(), "."));
			$flenew.= "_".$this->mode."_".$this->h."_".$this->w.".".$typ;

			// ERSTELLEN DER LOKALEN DATEI
			$pe = new picture_edit($typ, $dir.$this->filename());
			$exe='$pe->'.$this->mode.'('.$this->w.','.$this->h.');';
			eval($exe);
			$pe->save($_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_elmedia"].$flenew);

			//KOPIEREN DES FILES AUF DEN ZIELSERVER
			//$posturl=u("publish.php?action2=".$action2."&action=elementfile&file=".$flenew);

			if ($site->demopublishing == true) {
				$auth->r("publish", "demo", true);
				$desthost = $site->url_demo();
			}else{
				$auth->r("publish", "live", true);
				$desthost = $site->url();
			}
			$pup = new publishing();
			$pup->publish_singlemedia($desthost,$flenew, true);

			//echo '<a href="/'.$posturl.'">test</a>';  die;
		/*	$fh=fopen($_PHPLIB["cmsbaseurl"].$posturl,"r");
			while($l=fgets($fh,1024)) {
				echo $l."";//"rr";
			}
			fclose($fh);
			die;*/

			//die;
   	}

		$t->set_var("pictureadress", $siteurl.$_PHPLIB["dir_elmedia"].$flenew);

		$t->parse("out", "page");
		return $t->get("out");
	}

	function admin_panel() {
		global $PHP_SELF;
		$t = new Template("templates/");
		$t->set_file("page","admin_element_mediapicture_edit.html");

		if ($_POST["id"] == $this->edid) {
			// Alle Settings werden innerhalb der Abfrage ID = ME gemacht.
			// (Reanto-Bug vom 22.1.02 (Alle Elemente des selben Typs ändern sich sonst auf einer Seite)
			$this->linkname($_POST["linkname"]);
			$this->popupwin($_POST["popupwin"], True);
			if ($_POST["path"] != $this->path()) {
				$this->filename("");     						// Wenn sich Pfad geändert hat, muss der Filename zurück
																		// gesetzt werden
			}  else{
				$this->filename($_POST["filename"]);
			}
			$this->path($_POST["path"]);

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
		$this->db->query(sprintf("SELECT filename, path FROM media WHERE sid = %s AND path = '%s' ".
								"AND ftype IN ('jpg', 'png') ORDER BY filename",
								$this->site->id, $path));
		while ($this->db->next_record()) {
			$t->set_var("fval", $this->db->f("filename"));
			$t->set_var("filename", $this->db->f("filename"));

			if ($this->filename() == "") {
				$this->filename($this->db->f("filename"));
			}

			if ($this->filename() == $this->db->f("filename")) {
				$t->set_var("fsele", "SELECTED");
				$filename=$this->db->f("filename");
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
		// Variablen aus mediapicture
		$t->set_var(array("id"=>$this->edid,
					"linkname"=>$this->linkname(),
					"link_extern"=>$this->link_extern(),
					"language"=>$this->language,
					"popupwin"=>$this->popupwin(),
					"tid"=>$this->el->tid,
					"elid"=>$this->el->id,
					"pid"=>$this->pid,
					"sessid"=>fu(),
					"post"=>$PHP_SELF
			));

		// Variablen aus edit_picture
		$this->setsizes(true);
		$typ=right($filename, strlen($filename) - (strrpos($filename, ".") +1 ));

		/*
  		echo "MODE: $this->mode<br>";
		echo "W: $this->w<br>";
		echo "H: $this->h<br>";
		echo '<A TARGET="_blank" HREF="'.u("pic.php?file=".$path.$filename."&typ=".$typ.
										  			"&action=".$this->mode."&height=".$this->h."&width=".$this->w).'">TEST</A>';
  		*/

		$t->set_var(array("lnk_image"=>u("pic.php?file=".$path.$filename."&typ=".$typ.
										  			"&action=".$this->mode."&height=".$this->h."&width=".$this->w)
			));
		$t->parse("out", "page");
		return $t->get("out");
	}

}
?>