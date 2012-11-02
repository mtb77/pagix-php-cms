<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: local.inc 23 2002-10-26 14:32:40Z skulawik $
 *
 */ 

class DB_CMS extends DB_Sql {
  function DB_CMS() {
  	  // CONSTRUCTOR für das setzen der Variablen aus der prepend.php
  	  global $_PHPLIB;
	  $this->Host     = $_PHPLIB["db_host"];
 	  $this->Database = $_PHPLIB["db_database"];
  	  $this->User     = $_PHPLIB["db_user"];
  	  $this->Password = $_PHPLIB["db_password"];
  }
  
  function subquery($query)
    {
    	$substart = strpos($query, "(");
    	if($substart != false)
    	{
    		$subend = strrpos($query, ")");
    		$before = substr($query, 0, $substart);
    		$after = substr($query, $subend+1, -1);
    		$subquery = substr($query, $substart+1, $subend-$substart-1);
    	
    		$this->subquery($subquery);
    		$subtext = $this->sub();
    		return $this->query($before . $subtext . $after);
    	}
    	else return $this->query($query);
    }
	
	function sub()
    {	
    	if($this->num_rows() == 0) {
    		return "(99999999)";
    	}else{
	    	$this->next_record();
	    	$strResult = "( ".$this->f(0);	
	    	while($this->next_record()){
	    		$strResult = $strResult.", ".$this->f(0);
			}
    		$strResult = "$strResult )";
    		return $strResult;
    	}
    }
}

class CMS_CT_Sql extends CT_Sql {
  var $database_class = "DB_CMS";         ## Which database to connect...
  var $database_table = "active_sessions"; ## and find our session data in this table.
  var $split_length = 4096 ;                   ## Split rows every 4096 bytes
}

class CMS_Session extends Session {
  var $classname 	  = "CMS_Session";
  var $cookiename     = "";                ## defaults to classname
  var $magic          = "dfsdfdfgdsfgsdfgsdfgwretw";      ## ID seed
  var $mode           = "get";          ## We propagate session IDs with cookies
  var $fallback_mode  = "cookie";
  var $lifetime       = 0;                 ## 0 = do session cookies, else minutes
  var $that_class     = "CMS_CT_Sql"; ## name of data storage container
  var $gc_probability = 5;  
  
  function form_sessid()
  {
    return sprintf("<input type=\"hidden\" name=\"%s\" value=\"%s\">\n", $this->name, $this->id);
  }
}

class CMS_User extends User {
  var $classname = "CMS_User";

  var $magic          = "rtwerhjkful7uf";     ## ID seed
  var $that_class     = "Example_CT_Sql"; ## data storage container
}

class CMS_Auth extends Auth {
	var $classname      = "CMS_Auth";
	var $lifetime       =  0;
	var $database_class = "DB_CMS";
	var $database_table = "users";
	var $open_user_id;
	
	function auth_loginform() {
		global $sess;
		global $_PHPLIB;
		include($_PHPLIB["cmsroot"] . "templates/loginform.html");
	}
  
	function auth_validatelogin() {
		global $username, $password;
		
		if(isset($username)) {
			$this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
		}
		$uid = false;
		$this->db->query(sprintf("select * ".
		                   "        from %s ".
		                   "       where username = '%s' ".
		                   "         and password = '%s'",
		                $this->database_table,
		                addslashes($username),
		                addslashes($password)));
		
		while($this->db->next_record()) {
			$uid = $this->db->f("user_id");
			$this->auth["uid"] = $this->db->f("user_id");
			$this->auth["sid"] = $this->db->f("sid");
			if ($this->db->f("masteradmin") == "1") {
				$this->auth["masteradmin"] = true;
			}else{
				$this->auth["masteradmin"] = false;
			}
			if ($this->db->f("siteadmin") == "1") {
				$this->auth["siteadmin"] = true;
			}else{
				$this->auth["siteadmin"] = false;
			}
		}
		return $uid;
	}
	
	function get_rightid($callerclass = "", $right) {
		// Ermittelt die RechteID aus der Datenbanktabelle
		$dbb = new DB_CMS;
		if ($callerclass == "") {
			$dbb->query(sprintf("SELECT id FROM permissions WHERE permname = '%s'",$right));
		}else{
			$dbb->query(sprintf("SELECT id FROM permissions WHERE permname = '%s' AND class = '%s'",$right, $callerclass));
		}
		$dbb->next_record();
		return $dbb->f("id");
	}
	
	function r($callerclass, $right, $killscript = false) {
		// returnt, ob der User die geforderten Rechte besitzt
		// MASTERADMIN & SITEADMIN haben eh alle Rechte.
		
		if ($this->auth["masteradmin"] or $this->auth["siteadmin"]) {return true;}
		$retid = $this->get_rightid($callerclass, $right);
		if ($retid=="") {$retid=0;}
		
		$dbq = new DB_CMS;
		$dbq->query("SELECT count(*) as menge FROM userperms WHERE permid = $retid AND user_id = ".$this->auth["uid"]);
		$dbq->next_record();
		$ret = false;
		if ($dbq->f("menge") >= 1) {
			$ret = true;
		}

		if ($killscript and !$ret) {
			$t = new Template("templates/");
			$t->set_file("page","admin_access_denied.html");
			$t->parse("out", "page");
			$t->p("out");
			die;
		}else{
			return $ret;
		}
		/*
		$arr = get_declared_classes();
		foreach ($arr as $val) {
			//echo $val."<br>";
			//$are = "";
			$evall .= '$are = @'.$val.'::rights();'."\n";
			
			//eval($evall);
		}		
		echo $evall."<br>";
		eval($evall);
		echo $are;
		echo "ffffffffffffffffffffffffff";*/
	}
	
	function r_by_id($rightid, $uid) {
		//if ($this->auth["masteradmin"] or $this->auth["siteadmin"]) {return true;}
		$dbq = new DB_CMS;
		$dbq->query("SELECT count(*) as menge FROM userperms WHERE permid = $rightid AND user_id = ".$uid);
		$dbq->next_record();
		if ($dbq->f("menge") >= 1) {
			$ret = true;
		}else{
			$ret = false;
		}
		return $ret;
	}
	
	function set_r($callerclass, $right) {
		$dbb = new DB_CMS;
		$uid = $this->open_user_id;
		$permid = $this->get_rightid($callerclass, $right);
		$dbb->query("INSERT INTO userperms (user_id, permid, folderid) VALUES ($uid, $permid, 0)");
	}
	
	function set_r_by_id($rightid) {
		$dbb = new DB_CMS;
		$uid = $this->open_user_id;
		if (!$this->r_by_id($rightid, $uid)) {
			$dbb->query("INSERT INTO userperms (user_id, permid, folderid) VALUES ($uid, $rightid, 0)");
		}
	}
	
	function del_all_r() {
		$dbb = new DB_CMS;
		$uid = $this->open_user_id;
		$dbb->query("DELETE FROM userperms WHERE user_id = $uid");
	}
	
	function username($data) {
		$dfg = new DB_CMS;
		if ($data != "") {
			if ($this->open_user_id == "0") {
				// Neu anlegen des Users
				$dfg->query(sprintf("INSERT INTO users (user_id, username, password, sid) ".
					"VALUES (NULL, '%s', 'NOTHING', %s)", $data, $this->auth["sid"]));
				// Das hier, weil das Feld leider nicht ID heisst... da hat man keine andere Möglichkeit...
				$dfg->query(sprintf("SELECT * FROM users WHERE username = '%s' AND password = 'NOTHING'", $data));
				$dfg->next_record();
				$this->open_user_id = $dfg->f("user_id");
			}else{
				$dfg->query(sprintf("UPDATE users SET username = '%s' WHERE user_id = %s",$data, $this->open_user_id));
			}
		}
		// returning data 
		$dfg->query("SELECT username FROM users WHERE user_id = $this->open_user_id");
		$dfg->next_record();
		return $dfg->f("username");
	}
	
	function password($data) {
		$dfg = new DB_CMS;
		if ($data != "") {
			$dfg->query(sprintf("UPDATE users SET password = '%s' WHERE user_id = %s",$data, $this->open_user_id));
		}
		// returning data 
		$dfg->query("SELECT password FROM users WHERE user_id = $this->open_user_id");
		$dfg->next_record();
		return $dfg->f("password");
	}
	
	function uid_in_site($uid) {
		// Quasi Hackschutz...
		if ($uid == "0"){ return true;}
		$dfg = new DB_CMS;
		$dfg->query("SELECT * FROM users WHERE user_id = $uid");
		$dfg->next_record();
		if ($dfg->f("sid") == $this->auth["sid"]) {
			return true;
		}else{
			return false;
		}
	}
	
	function delete_user($user_id) {
		$dfg = new DB_CMS;
		$dfg->query("DELETE FROM users WHERE user_id = $user_id");
		$dfg->query("DELETE FROM userperms WHERE user_id = $user_id");
	}
	
	function admin_panel() {
		global $action2, $PHP_SELF, $HTTP_POST_VARS, $user_id, $err;
		if (isset($user_id)) {$this->open_user_id = $user_id;}
		switch($action2) {
			case "delete":
				if ($this->uid_in_site($user_id)) {
					$err->confirm("Wollen Sie wirklich den Benutzer $username löschen ?",
									u($PHP_SELF."?action=user&action2=delete_confirmed&user_id=".$user_id),
									u($PHP_SELF."?action=user"),
									true);
				}
				break;
			case "delete_confirmed":
				if ($this->uid_in_site($user_id)) {
					$this->delete_user($user_id);
				}
				Header("Location: ".u($PHP_SELF."?action=user"));
				break;
			case "edit":
				if ($this->uid_in_site($user_id)) {
					if ($this->open_user_id == $this->auth["uid"]) {
						$err->confirm("Sie können sich nicht selber ändern !", u($PHP_SELF."?action=user"));die;
					}
					$t = new Template("templates/");
					$t->set_file("page","admin_user_edit.html");
					$t->set_var(array("post"=>u($PHP_SELF),
									"action"=>"user",
									"action2"=>"edit",
									"username"=>$this->username($HTTP_POST_VARS["username"]),
									"password"=>$this->password($HTTP_POST_VARS["password"]),
									"user_id"=>$user_id,
									"lnk_back"=>u($PHP_SELF."?action=user")
									));
					if (isset($HTTP_POST_VARS["Submit"])) {$this->del_all_r();}// Erst alle Rechte löschen
					$this->db->query("SELECT * FROM permissions ORDER BY displaygroup");
					$t->set_block("page", "liste", "mlist");
					$prev_grp = "";
					while($this->db->next_record()) {
						if ($HTTP_POST_VARS[$this->db->f("id")] == "1"
							or $this->r_by_id($this->db->f("id"), $this->open_user_id)) {
							$this->set_r_by_id($this->db->f("id"));
							$t->set_var("checked", "checked");
						}else{
							$t->set_var("checked", "");
						}
						$out = "";
						if ($prev_grp != $this->db->f("displaygroup")) {
							$prev_grp =$this->db->f("displaygroup");
							$t->set_file("page_grp","admin_user_el_permission_group.html");
							$t->set_var("displaygroup",$this->db->f("displaygroup"));
							$t->parse("gpout", "page_grp");
							$out = $t->get("gpout");
						}
						$t->set_file("page_perm","admin_user_el_permission.html");
						$t->set_var(array("rightid"=>$this->db->f("id"),
										"displayname"=>$this->db->f("displayname")
										));
						$t->parse("permout", "page_perm");
						$out = $out . $t->get("permout");
						$t->set_var("permission",$out);
						$t->parse("mlist", "liste", true);
					}
					$t->parse("out", "page");
					$t->p("out");
				}
				break;
			default:
				$dbb = new DB_CMS();
				$t = new Template("templates/");
				$t->set_file("page","admin_user_list.html");
				$t->set_var("lnk_add",u($PHP_SELF."?action=user&action2=edit&user_id=0"));
				$t->set_block("page", "liste", "mlist");
				
				$this->db->query("SELECT * FROM users WHERE sid =  ".$this->auth["sid"]." ORDER BY username ASC");
				while($this->db->next_record()) {
					$t->set_var("username",$this->db->f("username"));
					if ($this->db->f("siteadmin") != "1") {
						$t->set_var(array("lnk_edit"=>u($PHP_SELF."?action=user&action2=edit&user_id=".$this->db->f("user_id")),
									  	"lnk_delete"=>u($PHP_SELF."?action=user&action2=delete&user_id=".$this->db->f("user_id"))
									));
					}else{
						// ERSTMAl sind Superadmins und Siteadmins nicht änderbar.
						// ... man sollte aber vielleicht doch noch das Passwd ändern können :)
						$t->set_var(array("lnk_edit"=>u($PHP_SELF."?action=user"),
										"lnk_delete"=>u($PHP_SELF."?action=user")
									));
					}
					$t->parse("mlist", "liste", true);
				}
				$t->parse("out", "page");
				$t->p("out");
		}
	}
}
?>
