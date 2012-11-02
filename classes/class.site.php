<?
/*##################### Pagix Content Management System #######################
$Id: class.site.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.site.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.2  2002/06/07 18:49:55  cvs
_REQUEST Fehler (Apache 2.0)

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Klasse site
#############################################################################*/
class site
{
	var $id = 0;
	var $url = "";
	var $url_demo = "";
	var $admin = "";
	var $adminpass = "";
	var $kundenname = "";
	var $masteradmin = 0;
	var $err;
	var $db;
	var $demopublishing = false;

	function site($id = 0) {
		global $auth, $db, $err, $_PHPLIB;
		// CONSTRUCTOR
		$this->err = $err;
		$this->db = $db;
		$this->masteradmin = $auth->auth["masteradmin"];
		if ($id==0 or $id=="") {
			// Es muss eine neue Site erzeugt werden, wenn die ID = 0 ist
			// Erst überprüfen, ob der User schon eine SID hat, dann stimmte der Referenzwert nicht 
			// (Reload)
			//$this->db->query(sprintf("SELECT sid FROM users WHERE user_id = '%s'", $auth->auth["uid"]));
			//$this->db->next_record();
			//if ($this->db->f("sid") < 1) {
			if ($id < 1) {
				$this->db->query("INSERT INTO site (id) values (NULL)");
				$id = $this->db->insert_id();
				// Site ID in den Benutzer schreiben
				$this->db->query(sprintf("INSERT INTO users (username, password, siteadmin, sid) ".
										"VALUES ('%s', 'admin', 1, %s)", $id."_admin", $id));
				//$this->db->query(sprintf("UPDATE users SET sid = %s WHERE user_id = '%s'",
				//					$id, $auth->auth["uid"]));
				umask(0);					// Umask ist die Macht.... Und Gott schuf die komplizierten Befehle !
				mkdir($_PHPLIB["sites_dir"].$id, 0777);
			} else {
				$id = $this->db->f("sid");
			}
			//$auth->auth["sid"] = $id;
		}
		$this->id = $id;
	}
	
	function guid() {
		$this->db->query(sprintf("SELECT guid FROM site WHERE id = %s", $this->id));
		$this->db->next_record();
		if ($this->db->f("guid") == "") {
			$guid = md5 (uniqid(rand())); 
			$this->db->query(sprintf("UPDATE site SET guid = '%s' WHERE id = %s", $guid, $this->id));
			return $guid;
		}else{
			return $this->db->f("guid");
		}
	}
	
	function kundenname($kundenname = "") {
		// Reads or sets the current kundenname for this Page
		if ($kundenname!="") {
			$this->kundenname = $kundenname;
			$this->db->query(sprintf("UPDATE site SET kundenname = '%s' WHERE id = %s", $kundenname, $this->id));
		}
		$this->db->query(sprintf("SELECT kundenname FROM site WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->kundenname = $this->db->f("kundenname");
		return $this->kundenname;
	}
	
	function get_baseurl() {
		// Gibt das zugehörige Basisverzeichnis zurück, relativ zum Webroot.
		// Je nach dem, ob nun Demo Mode, oder halt Production Mode :)
		
		if ($this->demopublishing) {
			$siturl = $this->url_demo();
		}else{
			$siturl = $this->url();
		}
		if (right($siturl, 1) == "/") {$siturl = left($siturl, strlen($siturl) - 1);}
		
		return $siturl;
	}
	
	function url($url = "") {
		// Reads or sets the current URL for this Page
		if ($url!="") {
			if (right($url, 1) != "/"){$url.="/";}
			$this->url = $url;
			$this->db->query(sprintf("UPDATE site SET url_live = '%s' WHERE id = %s", $url, $this->id));
		}
		$this->db->query(sprintf("SELECT url_live FROM site WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->url = $this->db->f("url_live");
		return $this->url;
	}
	
	function url_demo($url = "") {
		// Reads or sets the current URL for this Page
		if ($url!="") {
			if (right($url, 1) != "/"){$url.="/";}
			$this->url_demo = $url;
			$this->db->query(sprintf("UPDATE site SET url_demo = '%s' WHERE id = %s", $url, $this->id));
		}
		$this->db->query(sprintf("SELECT url_demo FROM site WHERE id = %s", $this->id));
		$this->db->next_record();
		$this->url_demo = $this->db->f("url_demo");
		return $this->url_demo;
	}
	
	function admin($admin = "") {
		// Reads or sets the current admin for this Page
		if ($admin!="") {
			$this->admin = $admin;
			$this->db->query(sprintf("UPDATE users SET username = '%s' WHERE siteadmin = 1 AND sid = %s", $admin, $this->id));
		}
		$this->db->query(sprintf("SELECT username FROM users WHERE siteadmin = 1 AND sid = %s", $this->id));
		$this->db->next_record();
		$this->admin = $this->db->f("username");
		return $this->admin;
	}
	
	function adminpass($adminpass = "") {
		// Reads or sets the current adminpass for this Page
		if ($adminpass!="") {
			$this->adminpass = $adminpass;
			$this->db->query(sprintf("UPDATE users SET password = '%s' WHERE siteadmin = 1 AND sid = %s", $adminpass, $this->id));
		}
		$this->db->query(sprintf("SELECT password FROM users WHERE siteadmin = 1 AND sid = %s", $this->id));
		$this->db->next_record();
		$this->adminpass = $this->db->f("password");
		return $this->adminpass;
	}
	
	function is_page_in_site($pid) {
		$this->db->query("SELECT sid FROM page WHERE id = $pid");
		$this->db->next_record();
		if ($this->id == $this->db->f("sid")) {
			return true;
		}else{
			return false;
		}
	}
	
	function is_folder_in_site($pid) {
		$this->db->query("SELECT sid FROM folder WHERE id = $pid");
		$this->db->next_record();
		if ($this->id == $this->db->f("sid")) {
			return true;
		}else{
			return false;
		}
	}

	function delete_page($id) {
		$dbp = new DB_CMS;
		$dbp->query("DELETE FROM elements_data WHERE pid = $id");
		$dbp->query("DELETE FROM page WHERE id = $id");
	}
	
	function delete_folder($id) {
		if ($id != 0) {
			$dbb = new DB_CMS;
			$dbb->query("SELECT id FROM page WHERE parentid = $id");
			while($dbb->next_record()) {
				$this->delete_page($dbb->f("id"));
			}
			$dbb->query("DELETE FROM folder WHERE id = $id");
			$dbb->query("SELECT * FROM folder WHERE parentid = $id");
			while($dbb->next_record())
			{
				$this->delete_folder($dbb->f("id"));
			}
		}
	}

	// ###############################################################################################
	// ##################################### LANGUAGE HANDLING #######################################
	// ###############################################################################################
	
	function getLanguageFullarray() {
		$lang = array("de"=>"Deutsch",
							"en"=>"Englisch",
							"es"=>"Spanisch",
							"it"=>"Italienisch",
							"fr"=>"Französisch"
							);
		return $lang;
	}

	function getLanguageName($short) {
		$arr = $this->getLanguageFullarray();
		return $arr[$short];
	}

	function getLanguageDefault() {
		return $this->get_splitdata("langdefault");
	}

	function setLanguageDefault($short) {
		$this->set_splitdata("langdefault",$short);
	}

	function setLanguageAviable($shortarray) {
		$this->set_splitdata("languages", implode(",", $shortarray));
	}

	function getLanguageAviable() {
		$str = $this->get_splitdata("languages");
		return explode(",", $str);
	}

	function isLanguageAviable($short) {
		$arr = $this->getLanguageAviable();
		foreach ($arr as $key) {
			if ($key == $short) {
				return "SELECTED";
			}
   	}
	}

	function get_splitdata($varname) {
		$arr = $this->get_splitdata_fullarray();
		return $arr[$varname];
	}

	function get_splitdata_fullarray() {
		// Helperclass, für die spätere Validierung der entsprechenden Elemente
		// zum Lesen und zum schreiben der entsprechenden Datenwerte
		$dbs = new DB_CMS;
		$dbs->query("SELECT data FROM site WHERE id = ".$this->id);
		$dbs->next_record();
		$data = $dbs->f("data");
		$dat_arr = split("{/;}", $data);
		foreach ($dat_arr as $value) {
			if ($value != "") {
				$extdat = split("{==}", $value);
				//echo $extdat[0].$extdat[1];
				$tag = str_replace ("\"", "''", $extdat[1]);
		  			eval('$ext_arr["'.$extdat[0].'"] = "'.stripslashes($tag).'";');
			}
		}
		return $ext_arr;
	}

	function set_splitdata($varname, $varvalue) {
		// Trennzeichen intern (HOPEFULLY NOT USED ANYWHERE ELSE !!!) {/;} und {==}
		// Setzt den Datenwert in der Tabelle für eine Variable
		// JOJOJO.... Vielleicht das nächste mal als XML Datei :) Mangels PHP Parsers aber jetzt nich.
		$dbs = new DB_CMS;
		$data = $varname."{==}".$varvalue;
		$arr = $this->get_splitdata_fullarray();
		$arr[$varname] = $varvalue;
		$data = "";
		foreach ($arr as $key => $value) {
			if ($data != "") {$data.="{/;}";}
	    	$data.= $key."{==}".$value;
		}
		$dbs->query(sprintf("UPDATE site SET data = '%s' WHERE id = %s"
								,addslashes($data), $this->id));
	}

	// ###############################################################################################
	// ###############################################################################################
	// ###############################################################################################

	function admin_panel() {
		global $PHP_SELF, $action2, $id, $pname, $auth;
		switch($action2)
		{
			case "delete":
				$auth->r("page", "delpage",  true);
				$this->err->confirm("Wollen Sie wirklich die Seite <font color=\"#E7651A\">$pname</font> löschen ?",
									u($PHP_SELF."?action=site&action2=delete_confirmed&id=".$id),
									u($PHP_SELF."?action=site"));
				break;
			case "delete_confirmed":
				$auth->r("page", "delpage",  true);
				if ($this->is_page_in_site($id)) {$this->delete_page($id);}
				Header("Location: ".u($PHP_SELF."?action=site&submit=Submit"));
				break;
			case "delete_folder":
				$auth->r("page", "delpage",  true);
				if ($this->is_folder_in_site($id)) {
					$this->err->confirm("Wollen Sie wirklich den Ordner <font color=\"#E7651A\">$pname</font> mit allen enthaltenen Dateien und Ordnern löschen ?",
									u($PHP_SELF."?action=site&action2=delete_folder_confirmed&id=".$id),
									u($PHP_SELF."?action=site"));
				}
				break;
			case "delete_folder_confirmed":
				$auth->r("page", "delpage",  true);	
				$this->delete_folder($id);
				Header("Location: ".u($PHP_SELF."?action=site&submit=Submit"));
				break;
			case "struct":
				global $open, $curr, $close;
				// Frame Template erzeugen
				$t = new Template("templates/");
				$t->set_file("page","admin_struct_frame.html");
				$t->set_var("lnk_upper", u($PHP_SELF."?action2=struct_upper&open=$open&curr=$curr&close=$close"));
				$t->set_var("lnk_lower", u($PHP_SELF."?action2=struct_lower&curr=$curr&pname=$pname"));
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "struct_upper":
				$tr= new dir_Tree;
				$tr->build_tree($this->id, $id);
				$tr->go_through_tree();
				$t = new Template("templates/");
				$t->set_file("page","admin_struct.html");
				$t->set_var("tree", $tr->outp);
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "struct_lower":
				global $open, $curr, $auth;
				//echo $curr;
				$t = new Template("templates/");
				$t->set_file("page","admin_struct_lower.html");
				$nf='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
					'<img src="images/baum_struktur/button_seite_a.gif" border="0" width="56" height="33"></a>';
				$nnf='<img src="images/baum_struktur/button_seite_b.gif" border="0" width="56" height="33">';
				$nfo='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
					'<img src="images/baum_struktur/button_ordner_a.gif" border="0" width="56" height="33"></a>';
				$nnfo='<img src="images/baum_struktur/button_ordner_b.gif" border="0" width="56" height="33">';
				$cg='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
					'<img src="images/baum_struktur/button_aendern_a.gif" border="0" width="56" height="33"></a>';
				$ncg='<img src="images/baum_struktur/button_aendern_b.gif" border="0" width="56" height="33">';
				$del='<a href="%s" onfocus="if(this.blur)this.blur()" target="main">'.
					'<img src="images/baum_struktur/button_loeschen_a.gif" border="0" width="56" height="33"></a>';
				$ndel='<img src="images/baum_struktur/button_loeschen_b.gif" border="0" width="56" height="33">';

				if (left($curr, 2) == "fi") { 						//file
					$idd = right($curr, strlen($curr) - 4);
					if ($auth->r("page", "delpage")) {
						$lnk_delete=u($PHP_SELF."?action2=delete&id=".$idd."&pname=".$pname);
						$t->set_var("delete", sprintf($del, $lnk_delete));
					}else{
						$t->set_var("delete", $ndel);
					}
					$lnk_change=u($PHP_SELF."?action=page&action2=config&id=".$idd."&pname=".$pname);
    				$t->set_var("new_file", $nnf);
					$t->set_var("new_folder", $nnfo);
               $t->set_var("change", sprintf($cg, $lnk_change));
				}elseif (left($curr, 2) == "di"){					//folder
					$idd = right($curr, strlen($curr) - 3);
					if ($auth->r("page", "addpage")) {
						$lnk_new_file=u($PHP_SELF."?action=page&action2=config&id=0&parentid=".$idd);
						$lnk_new_folder=u($PHP_SELF."?action=page&action2=folder_config&id=0&parentid=".$idd);
						$t->set_var("new_file", sprintf($nf, $lnk_new_file));
						$t->set_var("new_folder", sprintf($nfo, $lnk_new_folder));
					}else{
						$t->set_var("new_file", $nnf);
     					$t->set_var("new_folder", $nnfo);
					}
					if ($auth->r("page", "delpage")) {
						$lnk_delete=u($PHP_SELF."?action2=delete_folder&id=".$idd."&pname=".$pname);
						$t->set_var("delete", sprintf($del, $lnk_delete));
					}else{
						$t->set_var("delete", $ndel);
					}
					$lnk_change=u($PHP_SELF."?action=page&action2=folder_config&id=".$idd);
					$t->set_var("change", sprintf($cg, $lnk_change));
				}else{
					if ($auth->r("page", "addpage")) {
						$lnk_new_file=u($PHP_SELF."?action=page&action2=config&id=0&parentid=0");
						$lnk_new_folder=u($PHP_SELF."?action=page&action2=folder_config&id=0&parentid=0");
						$t->set_var("new_file", sprintf($nf, $lnk_new_file));
     					$t->set_var("new_folder", sprintf($nfo, $lnk_new_folder));
					}else{
						$t->set_var("new_file", $nnf);
     					$t->set_var("new_folder", $nnfo);
					}
					$t->set_var("change", $ncg);
					$t->set_var("delete", $ndel);
				}
				$t->parse("out", "page");
				$t->p("out");
				break;
			case "settings":
				$t = new Template("templates/");
				$t->set_file("page","admin_sitesettings.html");
				$t->set_var(array("surl"=> $this->url($_REQUEST["surl"]),
								"url_demo"=> $this->url_demo($_REQUEST["url_demo"]),
								"post"=>$PHP_SELF,
								"action2"=>"settings",
								"sessid"=>fu()
								));
        		$t->set_block("page", "modules", "mod");

				if ($this->masteradmin == 0) {
					$t->set_block("page", "masteradmin", "madm");
					$t->set_var("madm", "");
					$t->set_var("mod", "");
				} else {
					$mdl = new modules();
					$glb = $mdl->getGlobalVariables($this->id);
					if (sizeof($glb) >= 1) {								//22.03.2002 BUG
						foreach($glb as $key=>$val) {
							if ($_POST["Submit"] != "") {
								$mdl->setGlobalVariableValue($val["field"], $_POST[$val["field"]]);
							}
							$t->set_var(array("variable_name"=>$val["name"],
													"variable"=>$val["field"],
													"variable_value"=>$mdl->getGlobalVariableValue($val["field"])
													));
      					$t->parse("mod","modules", true);
						}
					}
     			}
				// ###########################LANGUAGES###########################
				if ($_POST["Submit"] != "") {
      			$this->setLanguageAviable($_POST["language"]);
					$this->setLanguageDefault($_POST["lang_default"]);
				}
				$t->set_block("page", "lange", "lalilu");
				$arr = $this->getLanguageAviable();
				foreach($arr as $key=>$val) {
					if ($this->getLanguageDefault() == $val) {
						$rsl = "SELECTED";
					}else{
						$rsl = "";
					}
					$t->set_var(array("lang_short"=>$val,
										"lang_selected"=>$rsl,
										"lang_name"=>$this->getLanguageName($val)
										));
					$t->parse("lalilu","lange", true);
				}

				$t->set_block("page", "lang", "lalala");
				$t->set_var("lang_default",$this->getLanguageDefault());
				$arr = $this->getLanguageFullarray();
				foreach($arr as $key=>$val) {
					$t->set_var(array("lang_short"=>$key,
										"lang_selected"=>$this->isLanguageAviable($key),
										"lang_name"=>$val
										));
					$t->parse("lalala","lang", true);
				}
				// ###############################################################

				$t->parse("out", "page");
				$t->p("out");
				break;
			default:
				$t = new Template("templates/");
				$t->set_file("page","admin_splash.html");
				$t->parse("out", "page");
				$t->p("out");
				break;
		}
	}
}
?>