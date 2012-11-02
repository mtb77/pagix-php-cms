<?
/*##################### Pagix Content Management System #######################
$Id: class.error.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.error.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:54  skulawik
Updated Versionsinfos

###############################################################################
Error-Klasse
#############################################################################*/
class error
{
	var $debug;
	
	function error($debug = false) {
		// CONSTRUCTOR
		if ($debug) {$this->debug = true;}else{$this->debug = false;}
	}
	
	function raise($source, $text) {
		echo "[".$source."] has reported following Error :<br>".$text;
	}
	
	function raise_fatal($source, $text) {
		$this->raise($source, $text);
		die;
	}
	
	function debug($text) {
		// prints debugoutput
		if ($this->debug) {
			echo $text;
		}
	}
	function d($text) {
		$this->debug($text);
	}
	
	function msgbox($text, $dest) {
		$t = new Template("templates/");
		$t->set_file("page","admin_msgbox.html");
		$t->set_var(array("text"=>$text,
					"dest"=>$dest
					));
		$t->parse("out", "page");
		$t->p("out");
		die;
	}
	
	function confirm($text, $yesdest, $nodest="", $fullsize = false) {
		$t = new Template("templates/");
		if ($fullsize) {
			$t->set_file("page","admin_confirm_breit.html");
   	}else{
			$t->set_file("page","admin_confirm.html");
		}
		$t->set_var(array("text"=>$text,
					"lnk_yes"=>$yesdest,
					"lnk_no"=>$nodest
					));
		$t->parse("out", "page");
		$t->p("out");
	}
}
?>