<?
/*##################### Pagix Content Management System #######################
$Id: class.sites.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.sites.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Klasse sites
#############################################################################*/
class sites {
	var $db;
	
	function sites() {
		global $db, $err;
		$this->db = $db;
		$this->err = $err;
	}

	function delete_site($sid) {
		if ($sid != "") {
			global $_PHPLIB;
			$dbb = new DB_CMS;
			$dbd = new DB_CMS;
			
			$this->db->query("DELETE FROM site WHERE id = $sid");
			$this->db->query("SELECT user_id FROM users WHERE sid = $sid");
			while ($this->db->next_record()) {
				$dbb->query("DELETE FROM userperms WHERE user_id = ".$this->db->f("user_id"));
			}
			$this->db->query("DELETE FROM users WHERE sid = $sid");
			$this->db->query("DELETE FROM page WHERE sid = $sid");
			$this->db->query("DELETE FROM folder WHERE sid = $sid");
	
			$this->db->query("SELECT id FROM template WHERE sid = $sid");
			while ($this->db->next_record()) {
				$dbb->query("SELECT id FROM template_elements_list WHERE tid = "	.$this->db->f("id"));
				$dbb->next_record();
				//$dbb->f("id") // elid
				$dbd->query("DELETE FROM template_elements_allowed WHERE elid = "	.$dbb->f("id"));
				$dbd->query("DELETE FROM elements_data WHERE elid = "				.$dbb->f("id"));
				$dbd->query("DELETE FROM template_elements_list WHERE id = "		.$dbb->f("id"));
				
			}
			$this->db->query("DELETE FROM template WHERE sid = $sid");

			$dir = $_PHPLIB["sites_dir"].$sid;
			passthru("rm -rf \"".$dir."\"");
		}
	}
	
	function admin_panel() {
		global $auth, $PHP_SELF, $action2, $sid, $url, $url_demo, $kundenname, $admin, $adminpass, $_PHPLIB, $HTTP_POST_VARS;
		switch($action2) {
			case "delete":
				$this->err->confirm("Wollen Sie wirklich die Site $kundenname löschen ?",
									u($PHP_SELF."?action=sites&action2=delete_confirmed&sid=".$sid),
									u($PHP_SELF."?action=sites"),
									true);
				break;
			case "delete_confirmed":
				$this->delete_site($sid);
				Header("Location: ".u($PHP_SELF."?action=sites"));
				break;
			case "edit":
				$t = new Template("templates/");
				$t->set_file("page","admin_sitemanagement_edit.html");
				$this->db->query("SELECT * FROM site WHERE id = ".$sid);
				$this->db->next_record();
				$site = new site($sid);
				if ($HTTP_POST_VARS["ftpdemo"]=="true") {
				 	$ftp = new ftpservice($HTTP_POST_VARS["demohost"],$HTTP_POST_VARS["demouser"],$HTTP_POST_VARS["demopasswd"]);
					$ftp->cd($HTTP_POST_VARS["demodir"]);
					$ftp->put($_PHPLIB["cmsroot"]."soapserver.php", "soapserver.php");
					$ftp->close();
				}
				if ($sid == "0") {
					// Beim Neuerstellen einer Site werden die benötigten Verzeichnisse noch angelegt,
					// und die entsprechenden Default Templates kopiert, damit man schon "loslegen" kann
					mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_media"], 0777);
					mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_templates"], 0777);
					mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_elements"], 0777);
					mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_site"], 0777);
					mkdir($_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_elmedia"], 0777);
					$dhdl = opendir($_PHPLIB["element_templates_dir"]);
					if ($dhdl) {
						while ($file = readdir($dhdl)) {
							if (($file != ".") and ($file != "..")){
								if (!copy ($_PHPLIB["element_templates_dir"].$file, 
										   $_PHPLIB["sites_dir"].$site->id.$_PHPLIB["dir_elements"].$file)) {
		    						print ("failed to copy $file...<br>\n");
								}
							}
						}
					}
				}
				$sid = $site->id;
				$t->set_var(array("post"=>$PHP_SELF,
								"sessid"=>fu(),
								"action"=>"sites",
								"action2"=>"edit",
								"sid"=>$sid,			// WICHTIG ! Bei id=0 wird in der Site die ID erzeugt !
								"kundenname"=>$site->kundenname($kundenname),
								"admin"=>$site->admin($admin),
								"adminpass"=>$site->adminpass($adminpass),
								"url"=>$site->url($url),
								"url_demo"=>$site->url_demo($url_demo)
								));
				$t->parse("out", "page");
				$t->p("out");
				break;
			default:
				$dbb = new DB_CMS();
				$t = new Template("templates/");
				$t->set_file("page","admin_sitemanagement.html");
				$t->set_var("lnk_neuesite",u($PHP_SELF."?action=sites&action2=edit&sid=0"));
				$t->set_block("page", "liste", "mlist");

				$this->db->query("SELECT * FROM site ORDER BY kundenname ASC");
				while($this->db->next_record()) {
					$dbb->query("SELECT * FROM users WHERE siteadmin = 1 AND sid = ".$this->db->f("id"));
					$dbb->next_record();
					$t->set_var(array("sid"=>$this->db->f("id"),
									  "kundenname"=>$this->db->f("kundenname"),
									  "admin"=>$dbb->f("username"),
									  "adminpass"=>$dbb->f("password"),
									  "lnk_edit"=>u($PHP_SELF."?action=sites&action2=edit&sid=".$this->db->f("id")),
									  "lnk_delete"=>u($PHP_SELF."?action=sites&action2=delete&sid=".$this->db->f("id")."&kundenname=".$this->db->f("kundenname"))
								));
					$t->parse("mlist", "liste", true);
				}
				$t->parse("out", "page");
				$t->p("out");
		}
	}
}
?>