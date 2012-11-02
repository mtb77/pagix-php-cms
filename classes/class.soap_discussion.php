<?
/*##################### Pagix Content Management System #######################
$Id: class.soap_discussion.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.soap_discussion.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Klasse soapdiscussion
#############################################################################*/
class soapdiscussion {
	var $servlet;
	var $errmsg;
	var $debug_flag;
	
	function soapdiscussion($servlet = "") {
		$this->servlet = $servlet;
		$this->errmsg = "";
		$this->debug_flag = false;
		$this->version_test();
	}
	
	function call($function, $arr_in, $arr_out) {
		foreach($arr_in as $key=>$val) {
			$arr_in[$key] = base64_encode($val);
		}
		$soap_message = new soapmsg($function,array($arr_in));
		$soap = new soap_client($this->servlet);
		if($return = $soap->send($soap_message,"urn:soapinterop")) {
			if(get_class($return) == "soapval") {
				$this->debug("<strong>Request:</strong><br><xmp>$soap->outgoing_payload</xmp><br>");
				$this->debug("<strong>Response:</strong><br><xmp>$soap->incoming_payload</xmp>");
				if ($return->value[1]->name == "faultstring") {
					$flt =  "<b>Error returned</b><br>";
					$flt .= "Error Source: ".$return->value[0]->value."<br>";
					$flt .= "Error Message: ".$return->value[1]->value."<br>";
					$flt .= "Error Detail: ".$return->value[2]->value."<br>";
					$arr_out["fault"] = $flt;
					$this->errout($flt);
					return false;
				}
				$arr_out = $return->value[0]->value;
				return true;
			} else {
				$this->errout("Client could not decode server's response");
			}
		} else {
			$this->errout("Was unable to send or receive.");
		}
	}
	
	function debug($string){
		if($this->debug_flag) {
			echo "$string\n";
		}
	}
	
	function errout($msg) {
//		$this->debug_flag = true;
		$this->errmsg = $msg;
//		$this->debug($msg);
		return false;
	}

	function version_test() {
 		$ks = "<p><font color=\"red\"> <b>Kritischer Abbruch:</B>".
				"<br>Der Empfangsserver auf dem von Ihnen angegebenen Ort hat einen Fehler gemeldet: <br>";
		$ke = "</font></p>";

 		$this->call("version", array("a"=>"ee"), &$arr);
		$cpl = $arr[0]->value;											// COMPLETE

		if ($cpl == "") {
			die($ks."Der Zielserver hat keine gültige Antwort gegeben. Möglicherweise ist der Zielserver ".
						"nicht korrekt konfiguriert oder die von Ihnen eingegebene URL ist nicht richtg.<br>".
						"Bitte kontaktieren Sie Ihren Administrator, wenn dieser Fehler erneut auftreten sollte.".$ke);}
		if ($cpl < "101") {
			die($ks."Diese Zielserver-Version unterstützt noch kein Versioning !".$ke);}
		if ($cpl < "102") {
			die($ks."Diese Zielserver-Version unterstützt noch kein Transfering !".$ke);}
   	return $cpl;
	}
}
?>