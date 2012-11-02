<?
/*##################### Pagix Content Management System #######################
$Id: class.media.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.media.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Media-Klasse
#############################################################################*/
class media
{
	var $err;
	var $site;
	var $db;

	function media() {
		global $db, $site, $err, $_PHPLIB;
		$this->db = $db;
		$this->site = $site;
		$this->err = $err;
		$directory = $_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_media"];
		if (!is_dir($directory)){
			mkdir($directory, 0777);
		}
	}
	
	function decompress_file($filename, $filedir) {
		$ftype = explode(".", $filename);
		$fty = strtolower($ftype[count($ftype)-1]);
		
		switch ($fty) {
			case "zip":
				$e = shell_exec("unzip \"".$filedir.$filename."\" -d \"".$filedir."\"");
				$e = shell_exec("rm \"".$filedir.$filename."\"");
				break;
			case "gz":
				$e = shell_exec("gunzip \"".$filedir.$filename."\"");
				//echo "gtar -xvf \"".$filedir.left($filename, strlen($filename) - 3)."\"";
				$e = shell_exec("gtar -xvf \"".$filedir.left($filename, strlen($filename) - 3)."\" -C \"".$filedir."\"");
				$e = shell_exec("rm \"".$filedir.left($filename, strlen($filename) - 3)."\"");
				break;
		}
	}
	
	function build_mediadb($aktdir = "", $level = 0) {
		if ($aktdir == "") {
			global $_PHPLIB;
			$aktdir = $_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_media"];
			$this->filepath = $aktdir;
			$this->db->query("DELETE FROM media WHERE sid = ".$this->site->id);
     	}
		//echo $aktdir."<br>";
		$dhdl = opendir($aktdir);
		while ($file = readdir($dhdl)) {
			if (($file != ".") && ($file != "..")) {
				if (is_dir($aktdir.$file)) {
					//echo $aktdir.$file."<br>";
					$this->build_mediadb($aktdir.$file."/", $level++);
				}else{
					$dir = right($aktdir, strlen($aktdir) - (strlen($this->filepath)- 1));
					$ftype = explode(".", $file);
					$fty = strtolower($ftype[count($ftype)-1]);
					$this->db->query(sprintf("INSERT INTO media (sid, path, filename, ftype) ".
								"VALUES (%s, '%s', '%s', '%s')",$this->site->id, $dir, $file, $fty));
				}
			}
		}
	}

	function dirparser($dir, $unixpath=1) {
		global $_PHPLIB;
		global $PHP_SELF;
		$webroot = left($PHP_SELF, strrpos($PHP_SELF, "/") + 1);
		// Validiert das aktuelle Verzeichnis und checkt, ob überhaupt Rechte da sind
		if (stristr($dir, "..")) {
			echo "HACK !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
			die;
		}
		if (left($dir, 1) == "/") {
			$dir = right($dir, strlen($dir) -1);
		}
		if ($unixpath==1) {
			$dir = $_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_media"].$dir;
		} else {
			//Schön, aber unsecure.... 
			$dir = $webroot.str_replace($_PHPLIB["cmsroot"], "", $_PHPLIB["sites_dir"]).
					$this->site->id.$_PHPLIB["dir_media"].$dir;
			//$dir = $this->site->url_demo.$dir;
			//echo $dir;
		}
		return $dir;
	}
	
	function delete_file($dir, $file) {
		global $PHP_SELF;
		$dir = $this->dirparser($dir);
		if (!@unlink($dir.$file)) {
			$this->err->msgbox("Die Datei kann nicht gelöscht werden. <br>".
							"Die Verzeichnisrechte für die Datei $file im Verzeichnis $dir stimmen nicht !", 
							u($PHP_SELF."?action=media"));
		}
	}
	
	function delete_folder($dir) {
		global $PHP_SELF;
		$dir = $this->dirparser($dir);
		if (!@rmdir($dir)) {
			$this->err->msgbox("Das Verzeichnis kann nicht gelöscht werden. <br>".
							"Für das Löschen von Verzeichnissen müssen Sie erst alle Unterdateien löschen !", 
							u($PHP_SELF."?action=media"));
		}
	}
	
	function create_folder($dir) {
		global $PHP_SELF;
		$dir = $this->dirparser($dir);
		if (!@is_dir($dir)) {
			umask(000); 
			if (!@mkdir($dir, 0777)) {
				$this->err->msgbox("Das Verzeichnis $dir kann nicht erstellt werden. <br>".
								"Die Verzeichnisrechte für das Verzeichnis stimmen nicht !", 
								u($PHP_SELF."?action=media"));
			}
		}
	}
	
	function rename_folder($dir, $oldname, $newname) {
		$dir = $this->dirparser($dir);
		if (@is_dir($dir.$oldname)) {
			passthru("mv \"".$dir.$oldname."\" \"".$dir.$newname."\"");
		}
	}
	
	function edit_picture($dir, $file, $ftype) {
		global $PHP_SELF;
		$pic = new picture($ftype);
		$picfle = $this->dirparser($dir.$file, 1);
		$pic->open($picfle);

		if ($_REQUEST["height"] == ""){$aus_height = $pic->height()/4;}else{$aus_height = $_REQUEST["height"];}
		if ($_REQUEST["width"] == "") {$aus_width = $pic->width()/4;}else{$aus_width = $_REQUEST["width"];}
		if ($_REQUEST["move"] == "") 	{$_REQUEST["move"] = 50;}
		if ($_REQUEST["xs"]=="" or $_REQUEST["ys"]=="" or $_REQUEST["xe"]=="" or $_REQUEST["ye"]== "") {
			$_REQUEST["xs"]=10;
			$_REQUEST["ys"]=10;
		}
	//	echo "aush $aus_height ausw $aus_width ";
		$_REQUEST["xe"] = $_REQUEST["xs"] + $aus_width;
		$_REQUEST["ye"] = $_REQUEST["ys"] + $aus_height;
	//	echo "xe:".$_REQUEST["xe"]." <br>";
	//	echo "ye:".$_REQUEST["ye"]." <br>";
		switch($_REQUEST["Submit"]) {
			case "Up":
				$_REQUEST["ys"] = $_REQUEST["ys"] - $_REQUEST["move"];
				$_REQUEST["ye"] = $_REQUEST["ye"] - $_REQUEST["move"];
				break;
			case "Down":
				$_REQUEST["ys"] = $_REQUEST["ys"] + $_REQUEST["move"];
				$_REQUEST["ye"] = $_REQUEST["ye"] + $_REQUEST["move"];
				break;
			case "Left":
				$_REQUEST["xs"] = $_REQUEST["xs"] - $_REQUEST["move"];
				$_REQUEST["xe"] = $_REQUEST["xe"] - $_REQUEST["move"];
				break;
			case "Right":
				$_REQUEST["xs"] = $_REQUEST["xs"] + $_REQUEST["move"];
				$_REQUEST["xe"] = $_REQUEST["xe"] + $_REQUEST["move"];
				break;
			case "ZOOMIN":
				$_REQUEST["ys"] = $_REQUEST["ys"] - $_REQUEST["move"];
				$_REQUEST["ye"] = $_REQUEST["ye"] + $_REQUEST["move"];
				$_REQUEST["xs"] = $_REQUEST["xs"] - $_REQUEST["move"];
				$_REQUEST["xe"] = $_REQUEST["xe"] + $_REQUEST["move"];
				break;
			case "ZOOMOUT":
				$_REQUEST["ys"] = $_REQUEST["ys"] + $_REQUEST["move"];
				$_REQUEST["ye"] = $_REQUEST["ye"] - $_REQUEST["move"];
				$_REQUEST["xs"] = $_REQUEST["xs"] + $_REQUEST["move"];
				$_REQUEST["xe"] = $_REQUEST["xe"] - $_REQUEST["move"];
				break;
		}
//		$aus_width = $_REQUEST["xe"] - $_REQUEST["xs"];
//		$aus_height = $_REQUEST["ye"] - $_REQUEST["ys"];

		$t = new Template("templates/");
		$t->set_file("page","admin_media_edit_picture.html");
		$t->set_var(array("lnk_image"	=>u("pic.php?file=".$dir.$file."&typ=".$ftype.
										  "&action=border".
										  "&xs=".$_REQUEST["xs"]."&ys=".$_REQUEST["ys"]."&xe=".$_REQUEST["xe"]."&ye=".$_REQUEST["ye"]),
						"lnk_preview"	=>u("picpreview.php?file=".$dir.$file."&typ=".$ftype.
										  "&action=show".
										  "&xs=".$_REQUEST["xs"]."&ys=".$_REQUEST["ys"]."&xe=".$_REQUEST["xe"]."&ye=".$_REQUEST["ye"].
										  "&height=".$aus_height."&width=".$aus_width),
						"lnk_save"	=>u("pic.php?file=".$dir.$file."&typ=".$ftype.
										  "&action=show&action2=save".
										  "&xs=".$_REQUEST["xs"]."&ys=".$_REQUEST["ys"]."&xe=".$_REQUEST["xe"]."&ye=".$_REQUEST["ye"].
										  "&height=".$aus_height."&width=".$aus_width),
						"post"			=>$PHP_SELF,
						"sessid"		=>fu(),
						"action"		=>"media",
						"action2"		=>"file_edit",
						"dir"			=>$dir,
						"file"			=>$file,
						"xs"			=>$_REQUEST["xs"],
						"ys"			=>$_REQUEST["ys"],
						"xe"			=>$_REQUEST["xe"],
						"ye"			=>$_REQUEST["ye"],
						"move"		=> $_REQUEST["move"],
						"height"	=> 	$aus_height,
						"width"		=> $aus_width
						));
		$t->parse("out", "page");
		$t->p("out");
	}

	function view_picture($dir, $file) {
		$dir = $this->dirparser($dir, 0);
		$t = new Template("templates/");
		$t->set_file("page","admin_media_view_picture.html");
		$t->set_var(array("lnk_picture"=>$dir.$file,
						"lnk_file"=>$dir.$file,
						"filename"=>$file
						));
		$t->parse("out", "page");
		$t->p("out");
	}
	
	function edit_file($dir, $file) {
		$dir = $this->dirparser($dir, 0);
		$t = new Template("templates/");
		$t->set_file("page","admin_media_view_filelink.html");
		$t->set_var(array("lnk_file"=>$dir.$file,
						"filename"=>$file
						));
		$t->parse("out", "page");
		$t->p("out");
	}
		
	function admin_panel() {
		global $HTTP_POST_FILES, $PHP_SELF, $file, $dir, $name, $name_old, $open, $curr, $fty, $close;
		switch($_REQUEST["action2"])
		{
			case "struct":
				// Frame Template erzeugen
				$t = new Template("templates/");
				$t->set_file("page","admin_struct_frame.html");
				$t->set_var("lnk_upper", u($PHP_SELF."?action=media&action2=struct_upper&open=$open&curr=$curr&close=$close"));
				$t->set_var("lnk_lower", u($PHP_SELF."?action=media&action2=struct_lower&curr=$curr&pname=$pname&fty=$fty&dir=$dir&name=$name&file=$file"));
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "struct_upper":
				$t = new Template("templates/");
				$t->set_file("page","admin_struct.html");
				$tr= new media_Tree;
				$tr->build_tree($this->site->id);
				$tr->go_through_tree();
				$t->set_var("tree", $tr->outp);
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "struct_lower":
				global $open, $curr,$fty, $dir, $name, $file;
				$t = new Template("templates/");
				$t->set_file("page","admin_struct_lower.html");

                                $nf='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
                                    '<img src="images/baum_struktur/button_hinzufuegen_a.gif" border="0" width="56" height="33"></a>';
                                $nnf='<img src="images/baum_struktur/button_hinzufuegen_b.gif" border="0" width="56" height="33">';
                                $nfo='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
                                     '<img src="images/baum_struktur/button_ordner_a.gif" border="0" width="56" height="33"></a>';
                                $nnfo='<img src="images/baum_struktur/button_ordner_b.gif" border="0" width="56" height="33">';
                                $cg='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
                                    '<img src="images/baum_struktur/button_aendern_a.gif" border="0" width="56" height="33"></a>';
                                $ncg='<img src="images/baum_struktur/button_aendern_b.gif" border="0" width="56" height="33">';
                                $del='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
                                     '<img src="images/baum_struktur/button_loeschen_a.gif" border="0" width="56" height="33"></a>';
                                $ndel='<img src="images/baum_struktur/button_loeschen_b.gif" border="0" width="56" height="33">';

				if ($fty == "dir"){							//folder
					$idd = right($curr, strlen($curr) - 3);
					$lnk_delete=u($PHP_SELF."?action=media&action2=delete_folder&dir=".$dir."/"."&name=".$name);
					$lnk_new_file=u($PHP_SELF. "?action=media&action2=file_new&dir=".$dir.$file."/");
					$lnk_new_folder=u($PHP_SELF."?action=media&action2=folder_new&dir=".$dir.$file."/");
					//Für Folder muss aufgesplittet werden, da der entsprechende Filename nicht übermittelt wird.
					$dr=left($dir, strrpos($dir, "/")+1);
					$fl=right($dir, strlen($dir)-strrpos($dir, "/")-1);
					$lnk_folder_edit=u($PHP_SELF. "?action=media&action2=folder_edit&dir=".$dr."&name=".$fl);
					//&action2=folder_edit&dir=".$dir."&name=".$file),
					$t->set_var("new_file", sprintf($nf, $lnk_new_file));
					$t->set_var("new_folder", sprintf($nfo, $lnk_new_folder));
					$t->set_var("change", sprintf($cg, $lnk_folder_edit));
					$t->set_var("delete", sprintf($del, $lnk_delete));

				}elseif ($fty != ""){						//file
					$idd = right($curr, strlen($curr) - 4);
					$lnk_delete= u($PHP_SELF."?action=media&action2=delete_file&dir=".$dir."&file=".$file);

                                        $t->set_var("new_file", $nnf);
                                        $t->set_var("new_folder", $nnfo);
                                        $t->set_var("change", $ncg);
                                        $t->set_var("delete", sprintf($del, $lnk_delete));
				}else{										//root
					$lnk_new_file=u($PHP_SELF. "?action=media&action2=file_new&dir=/");
					$lnk_new_folder=u($PHP_SELF."?action=media&action2=folder_new&dir=/");

                                        $t->set_var("new_file", sprintf($nf, $lnk_new_file));
                                        $t->set_var("new_folder", sprintf($nfo, $lnk_new_folder));
                                        $t->set_var("change", $ncg);
                                        $t->set_var("delete", $ndel);
				}
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "delete_file":
				$this->err->confirm("Wollen Sie wirklich die Datei <font color=\"#E7651A\">$file</font> löschen ?",
									u($PHP_SELF."?action=media&action2=delete_file_confirmed&file=$file&dir=$dir"),
									u($PHP_SELF."?action=media"));
				break;
			case "delete_file_confirmed":
				$this->delete_file($dir, $file);
				Header("Location: ".u($PHP_SELF."?action=media&submit=Submit"));
				$this->build_mediadb();
				break;
			case "delete_folder":
				$this->err->confirm("Wollen Sie wirklich den Ordner <font color=\"#E7651A\">$name</font> löschen ?",
									u($PHP_SELF."?action=media&action2=delete_folder_confirmed&dir=$dir"),
									u($PHP_SELF."?action=media"));
				break;
			case "delete_folder_confirmed":
				$this->delete_folder($dir);
				Header("Location: ".u($PHP_SELF."?action=media&submit=Submit"));
				$this->build_mediadb();
				break;
			case "folder_new":
				$t = new Template("templates/");
				$t->set_file("page","admin_media_folder_neuer.html");
				$t->set_var(array("post"=>$PHP_SELF,
					"action"=>"media",
					"action2"=>"folder_new_confirmed",
					"dir"=>$dir,
					"sessid"=>fu()
					));
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "folder_new_confirmed":
				$this->create_folder($dir.$name);
				Header("Location: ".u($PHP_SELF."?action=media&submit=Submit"));
				break;
			case "folder_edit":
				$t = new Template("templates/");
				$t->set_file("page","admin_media_folder.html");
				$t->set_var(array("post"=>$PHP_SELF,
					"action"=>"media",
					"action2"=>"folder_edit_confirmed",
					"name"=>$name,
					"sessid"=>fu(),
					"name_old"=>$name,
					"dir"=>$dir
					));
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "folder_edit_confirmed":
				$this->rename_folder($dir, $name_old, $name);
				$this->build_mediadb();
				Header("Location: ".u($PHP_SELF."?action=media&action2=folder_edit&dir=$dir&name=$name&submit=Submit"));
				break;
			case "file_edit":
				$ftype = explode(".", $file);
				$fty = strtolower($ftype[count($ftype)-1]);
				switch ($fty) {
					case "jpg":
					case "png":
						$this->edit_picture($dir, $file, $fty);
						break;
					case "gif":
					case "tiff":
						$this->view_picture($dir, $file);
						break;
					default:
						$this->edit_file($dir, $file);
						break;
				}
				break;
			case "importfile": 
				global $ungzip;
				// Variable $dir Parsen und VALIDIEREN !!!!!!!!
				$dir = $this->dirparser($dir);

				$userfile = $HTTP_POST_FILES["file2"]['name']; 
				$type = $HTTP_POST_FILES["file2"]['type']; 
				$size = $HTTP_POST_FILES["file2"]['size']; 
				$location = $HTTP_POST_FILES["file2"]['tmp_name']; 
				if($userfile and is_uploaded_file($location)) { 
					move_uploaded_file($location, $dir.$userfile); 
					if ($ungzip == "1") {
						$this->decompress_file($userfile, $dir);
					}
					$file = $userfile;
				} 
				$userfile = $HTTP_POST_FILES["file3"]['name']; 
				$type = $HTTP_POST_FILES["file3"]['type']; 
				$size = $HTTP_POST_FILES["file3"]['size']; 
				$location = $HTTP_POST_FILES["file3"]['tmp_name']; 
				if($userfile and is_uploaded_file($location)) { 
					move_uploaded_file($location, $dir.$userfile); 
					if ($ungzip == "1") {
						$this->decompress_file($userfile, $dir);
					}
					$file = $userfile;
				} 
				// Handle only first File for forwarding
				$userfile = $HTTP_POST_FILES["file1"]['name']; 
				$type = $HTTP_POST_FILES["file1"]['type']; 
				$size = $HTTP_POST_FILES["file1"]['size']; 
				$location = $HTTP_POST_FILES["file1"]['tmp_name']; 
				if($userfile and is_uploaded_file($location)) { 
					move_uploaded_file($location, $dir.$userfile); 
					if ($ungzip == "1") {
						$this->decompress_file($userfile, $dir);
					}
					$file = $userfile;
				}
				$this->build_mediadb();
				Header("Location: ".u($PHP_SELF."?action=media&submit=Submit"));
				break;
			case "file_new":
				$t = new Template("templates/");
				$t->set_file("page","admin_media_upload.html");
				$t->set_var(array("post"=>$PHP_SELF,
								"action"=>"media",
								"action2"=>"importfile",
								"dir"=>$dir,
								"sessid"=>fu()
								));
				$t->parse("out", "page");
				$t->p("out");
				break;
    		default:
				//SPLASH SCREEN
    			$t = new Template("templates/");
				$t->set_file("page","admin_media_splash.html");
				$t->parse("out", "page");
				$t->p("out");
				break;
		}
	}
}
?>