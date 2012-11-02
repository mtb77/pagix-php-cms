<?
/*##################### Pagix Content Management System #######################
$Id: index.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: index.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.5  2002/04/12 15:14:06  sascha
no message

Revision 2.4  2002/04/12 12:30:32  skulawik
*** empty log message ***

###############################################################################
Indexseite
#############################################################################*/
require("config/prepend.php");
page_open(array("sess" => "CMS_Session",
				"auth" => "CMS_Auth"));
$auth->login_if(isset($force_relogin));
?>
<html>
<head>
<title>Pagix net CMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<FRAMESET ROWS="228,*,10" COLS="*,741,*" FRAMEBORDER="NO" BORDER="0" FRAMESPACING="0"> 
  <FRAME name="links" SRC="links.html" MARGINWIDTH="0" MARGINHEIGHT="0" NORESIZE SCROLLING="NO" FRAMEBORDER="NO">
<FRAME name="topFrame" SRC="<?=u("admin.php?action=top");?>" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO">
<FRAME name="rechts" SRC="rechtsoben.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO">
<FRAME name="links2" SRC="links.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO">
<FRAME name="mainFrame" SRC="newsseite.html" SCROLLING="AUTO" NORESIZE FRAMEBORDER="NO" MARGINWIDTH="0" MARGINHEIGHT="0">
<FRAME name="rechts2" SRC="rechtsmitte.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO">
<FRAME name="links3" SRC="links.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO">
<FRAME name="bottomFrame" SRC="bottom.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO"> 
<FRAME name="rechts3" SRC="rechtsunten.html" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="NO" NORESIZE FRAMEBORDER="NO"> 
</FRAMESET>
<noframes><body bgcolor="#FFFFFF" text="#000000">
</body></noframes>
</html>
<?
page_close();
?>