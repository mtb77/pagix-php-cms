<?
/*##################### Pagix Content Management System #######################
$Id: admin.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: admin.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:35:49  skulawik
*** empty log message ***

###############################################################################
Defaultseite für die Administration
Über die Verwendung von IDs:
Die eindeutigen Bezeichner sind für jede Klasse eine ID.
Diese ist folgend benannt:
- ID		PAGE ID
- SID		SITE ID
- TID		TEMPLATE ID
- MID 	MODULE ID

Die Verwendung von Action Types:
Ein Actiontype definiert als allererstes die genutze Klasse für den Aufruf.
Weitere Parameter werden nicht über diese $action gepflegt, sondern werden als 
action2 übergeben, im Idealfall sollten keine weiteren Informationen angefragt 
werden sondern anhand der Benutzerberechtigungen aus der Datenbank auslesbar 
sein.
#############################################################################*/

require("config/prepend.php");
page_open(array("sess" => "CMS_Session",
				"auth" => "CMS_Auth"));
//if (($HTTP_REFERER=="") or (!isset($HTTP_REFERER))) {$force_relogin ="yes";}
$auth->login_if(isset($force_relogin));

$err = new error($debug);	// Errorobject öffnen, Übergabe ist Debuginfo.
//$action = $_REQUEST["action"];
switch($action)
{
	case "top":
		$t = new Template("templates/");
		$t->set_file("page","admin_top.html");
		$t->set_var(array("lnk_editieren"=>u("structure_frame.php"),
								"lnk_media"=>u("structure_media.php"),
								"lnk_vorschau"=>u("publish.php?action=demo"),
								"lnk_speichern"=>u("publish.php"),
								"lnk_module"=>u("admin.php?action=modules"),
								"lnk_verwalten"=>u("admin.php?action=template"),
								"lnk_benutzer"=>u("admin.php?action=user"),
								"lnk_einstellungen"=>u("admin.php?action=site&action2=settings"),
								"lnk_sites"=>u("admin.php?action=sites"),
								"lnk_main"=>u("index.php")
					));
		if (!$auth->r("page", "editpage")) {
			$t->set_block("page","editieren1","ed1");
			$t->set_block("page","editieren2","ed2");
			$t->set_var(array("ed1"=>"","ed2"=>""));
   	}
		if (!$auth->r("media", "mediaview")) {
			$t->set_block("page","media1","md1");
			$t->set_block("page","media2","md2");
			$t->set_var(array("md1"=>"","md2"=>""));
   	}
		if (!$auth->r("publish", "demo")) {
			$t->set_block("page","vorschau1","vs1");
			$t->set_block("page","vorschau2","vs2");
			$t->set_var(array("vs1"=>"","vs2"=>""));
   	}
		if (!$auth->r("publish", "live")) {
			$t->set_block("page","speichern1","sp1");
			$t->set_block("page","speichern2","sp2");
			$t->set_var(array("sp1"=>"","sp2"=>""));
   	}
		if (!$auth->r("modules", "view")) {
			$t->set_block("page","module1","me1");
			$t->set_block("page","module2","me2");
			$t->set_var(array("me1"=>"","me2"=>""));
   	}
		if (!$auth->r("template", "templateview")) {
			$t->set_block("page","verwalten1","vw1");
			$t->set_block("page","verwalten2","vw2");
			$t->set_var(array("vw1"=>"","vw2"=>""));
   	}
		if (!$auth->r("user", "userview")) {
			$t->set_block("page","benutzer1","be1");
			$t->set_block("page","benutzer2","be2");
			$t->set_var(array("be1"=>"","be2"=>""));
   	}
		if (!$auth->r("settings", "view")) {
			$t->set_block("page","einstellungen1","ei1");
			$t->set_block("page","einstellungen2","ei2");
			$t->set_var(array("ei1"=>"","ei2"=>""));
   	}
		if (!$auth->auth["masteradmin"]) {
			$t->set_block("page","sites1","st1");
			$t->set_block("page","sites2","st2");
			$t->set_var(array("st1"=>"","st2"=>""));
   	}
		$t->parse("out", "page");
		$t->p("out");
		break;
	case "page":
		$site = new site($auth->auth["sid"]);
		$page = new page($id);
		$page->admin_panel();
		break;
	case "template":
		$auth->r("template", "templateview", true);
		$site = new site($auth->auth["sid"]);
		$template = new cmstemplate($tid);
		$template->admin_panel();
		break;
  	case "modules":
		$site = new site($auth->auth["sid"]);
		$mdl = new modules();
		$mdl->admin_panel();
		break;
	case "el":
		$site = new site($auth->auth["sid"]);
		$template = new cmstemplate($tid);
		$el = new elements_list($elid, $tid,$language);
		$el->admin_panel(); 
		break;
	case "media":
		$auth->r("media", "mediaview", true);
		$site = new site($auth->auth["sid"]);
		$media = new media();
		$media->admin_panel();
		break;
	case "sites":
		if ($auth->auth["masteradmin"]) {
			$sites = new sites();
			$sites->admin_panel();
		}else{
			$t = new Template("templates/");
			$t->set_file("page","admin_access_denied.html");
			$t->parse("out", "page");
			$t->p("out");
		}
		break;
	case "user":
		$auth->r("user", "userview", true);
		$auth->admin_panel();
		break;
	default:
		$site = new site($auth->auth["sid"]);
		$site->admin_panel();
}
page_close();
?>
