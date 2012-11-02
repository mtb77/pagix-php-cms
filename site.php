<?
/*##################### Pagix Content Management System #######################
$Id: site.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: site.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:48:33  skulawik
Versionsinfos eingetragen

###############################################################################
Siteview (veraltet)
Zeigt eine Preview der Seite an
#############################################################################*/
require("config/prepend.php");
page_open(array("sess" => "CMS_Session",
				"auth" => "CMS_Auth"));

$err = new error($debug);	// Errorobject öffnen, Übergabe ist Debuginfo.
$site = new site($auth->auth["sid"]);
if ($id == "" OR $id < 0) {
	// Keine ID angegeben, Startseite finden
	$db->query("SELECT id FROM page WHERE sid = ".$auth->auth["sid"]."");
	$db->next_record();
	$id = $db->f("id");
}
$pg = new page($id);
$pg->paint();

page_close();
?>