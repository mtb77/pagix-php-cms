<?
/*##################### Pagix Content Management System #######################
$Id: class.ftpservice.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.ftpservice.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
FTPService-Klasse
#############################################################################*/
class ftpservice
{
	//PUBLIC
	var $host;
	var $user;
	var $passwd;
 	//PRIVATE
	var $cn;			//Connection ID
	var $err;
	var $passive;


	function ftpservice($host="", $user="", $passwd="") {
		global $err;
		$this->err=$err;
		$this->host=$host;
		$this->user=$user;
		$this->passwd=$passwd;
		$this->passive = true;
		if ($host!="" AND $user!="" AND $passwd!="") {
		 	$this->open();
		}
	}

	function open() {
 		$this->cn = ftp_connect($this->host, 21);
		$ret = ftp_login($this->cn, $this->user, $this->passwd);
		ftp_pasv($this->cn, $this->passive);
	}

	function close() {
		ftp_quit($this->cn);
	}

	function cd($dir) {
	 	ftp_chdir($this->cn, $dir);
	}

	function put($file, $remfilename="") {
		// Remote FIlename is the same as the local Filename
		if ($remfilename==""){
			$fl=right($file, strlen($file)-strrpos($file, "/")-1);
		}else{
			$fl=$remfilename;
   	}
 		ftp_put($this->cn, $fl, $file, "FTP_BINARY");
	}
}
?>