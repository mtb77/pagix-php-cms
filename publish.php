<?
/*##################### Pagix Content Management System #######################
$Id: publish.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: publish.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.5  2002/04/24 10:03:27  skulawik
Fehler beim Demo-Publishing

Revision 2.4  2002/04/19 09:14:26  skulawik
fehler im aufruf

Revision 2.3  2002/04/19 09:11:59  skulawik
*** empty log message ***

Revision 2.2  2002/04/19 09:00:05  skulawik
Fehler mit der Authentifizierung behoben, die Kontrollfunktion gab keinen Fehler zurück, wenn das nicht-eigene-Web überschrieben werden sollte

Revision 2.1  2002/04/12 12:48:33  skulawik
Versionsinfos eingetragen

###############################################################################
Publisher
Sendet Files an den SOAP Server
#############################################################################*/
require("config/prepend.php");
page_open(array("sess" => "CMS_Session",
				"auth" => "CMS_Auth"));
//if (($HTTP_REFERER=="") or (!isset($HTTP_REFERER))) {$force_relogin ="yes";}
$auth->login_if(isset($force_relogin));

//$debug = true;
$dbt = new DB_CMS;
$err = new error($debug);
$site = new site($auth->auth["sid"]);

$aktver = "104";
$pup = new publishing();

switch($action)
{
	case "publish_finally":						// Publiziert die entsprechenden Daten auf den Servern
	case "demo_finally":
		$fle = $_PHPLIB["cmsroot"]."templates/admin_publish_head.html";
 		readfile($fle);

		if ($action=="demo_finally") {
			$auth->r("publish", "demo", true);
			$site->demopublishing = true;
			$desthost = $site->url_demo();
		}else{
			$auth->r("publish", "live", true);
			$desthost = $site->url();
		}
		$pup->pm("Starte die Übertragung...");
		$guid = $site->guid();
		//UPDATECHECK VON SOAPSERVER
		$sd = new soapdiscussion($desthost."soapserver.php");
		if($sd->version_test() < $aktver) {
  			$pup->update_soapserver($desthost, $guid);
		}

		$arr = $site->getLanguageAviable();
		$dbt->query("SELECT id FROM page WHERE sid = ".$auth->auth["sid"]);
		while($dbt->next_record())
		{
			// ###########################LANGUAGES###########################
			foreach($arr as $key=>$languageshort) {
				$pup->publish_page($desthost, $guid, $dbt->f("id"), false, $languageshort);
			}
			// ###############################################################
		}   
		$sitesdir = $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_site"];
		$pup->publish_site($desthost, $guid, "", $sitesdir, "Site");
		$pup->publish_site($desthost, $guid, "media/", $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_media"], "Media");

		$pup->pm("Beendet !");

		$t = new Template("templates/");
		$t->set_file("page","admin_publish_body.html");
		$t->set_var("lnk_preview",$site->url_demo());
		$t->parse("out", "page");
		$t->p("out");
		break;
	case "preview":								// Zeigt die angegebene PID als Seite auf dem Demo Server 
		// PID muss gesetzt sein !
		$auth->r("page", "preview", true);

		if ($pid != "") {
			$site->demopublishing = true;
            $guid = $site->guid();
			$pg = new page($pid, $language);
			$path = $pg->get_current_dir($pg->parentid(), "");	
			$desthost = $site->url_demo();
			$pup->publish_page($desthost, $guid, $pid, true, $language);   
			$desthost = left($desthost, strlen($desthost) - 1);
			$pageurl = $desthost.$path.$pg->url();
			Header ("Cache-Control: no-cache, must-revalidate");
			if ($pg->isMultiLanguage()) {
				Header("Location: ".$pageurl.".".$language);
    		}else{
				Header("Location: ".$pageurl);
			}
		}
		break;
	case "demo":
		$auth->r("publish", "demo", true);
		$t = new Template("templates/");
		$t->set_file("page","admin_publish_demo.html");
		$t->set_var("lnk_demo",u($PHP_SELF."?action=demo_finally"));
		$t->set_var("lnk_preview",$site->url_demo());
		$t->parse("out", "page");
		$t->p("out");
		break;
	default:									// Ansicht der Adminseite für das LIVE PUBISHING
		$auth->r("publish", "live", true);
		$t = new Template("templates/");
		$t->set_file("page","admin_publish.html");
		$t->set_var("lnk_live",u($PHP_SELF."?action=publish_finally"));
		$t->parse("out", "page");
		$t->p("out");
		break;
}

page_close();
?>