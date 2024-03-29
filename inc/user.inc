<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: user.inc 23 2002-10-26 14:32:40Z skulawik $
 *
 */ 

class User extends Session {
  var $classname  = "User";         ## Needed for object serialization.
  var $that_class = "Session_sql";  ## Name of data storage container

  ##
  ## End of parameters.
  ##

  var $name;                          ## Session name
  var $id;                            ## Unique Session ID

  var $pt = array();                  ## This Array contains the registered things
  var $that;

  ## get_id():
  ##
  ## Propagate the session id according to mode and lifetime.
  ## Will create a new id if necessary. To take over abandoned sessions,
  ## one may provide the new session id as a parameter (not recommended).
  function get_id($id = "") {
    $this->id = $id;
  }
  
  ## put_id():
  ## 
  ## Stop using the current session id (unset cookie, ...) and
  ## abandon a session.
  function put_id() {
    ;
  }

  ##
  ## Initialization
  ##

  function start($sid = "") {
    $this->get_id($sid);
	
    if(!isset($this->cookiename)) { $this->cookiename=""; };
	$this->name = $this->cookiename == "" ? $this->classname : $this->cookiename;
	$name = $this->that_class;
	$this->that = new $name;
	$this->that->ac_start();
 
    $this->thaw();
  }
}
?>
