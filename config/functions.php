<?

function right($varname, $len) {
	if ($len >= strlen($varname)) {
		return $varname;
	}else{
		return substr($varname, strlen($varname) - $len, strlen($varname));
	}
}

function left($varname, $len) {
	return substr($varname, 0, $len);
}

function u($url) {						// prints the Session URL 
	global $sess;
	return $sess->url($url);
}

function fu() {							// returns a Formfield with the used Session ID
	global $sess;
	return $sess->form_sessid();
}
?>