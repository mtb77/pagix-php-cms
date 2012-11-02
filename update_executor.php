<?
/*##################### Pagix Content Management System #######################
$Id: update_executor.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: update_executor.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:48:33  skulawik
Versionsinfos eingetragen

###############################################################################
Dies ist der Update Executor.
Er wird auf den Zielserver gespielt, um ein update des soapservers zu 
ermöglichen und wird danach vom Zielserver automatisch gelöscht.
Diesen nicht lokal ausführen lassen !
#############################################################################*/
  if (rename("soapserver.php","soapserver.php.old")) {
  		if (rename("soapserver.php.new","soapserver.php")) {
			unlink("soapserver.php.old");
   	}else{
			rename("soapserver.php.old","soapserver.php");
		}
  }
  echo "<b>Update des 'Soapservers' erfolgt</b><br>";
?>