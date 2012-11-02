<?php
/*##################### Pagix Content Management System #######################
$Id: soapserver.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: soapserver.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.3  2002/06/14 14:35:38  skulawik
*** empty log message ***

Revision 2.2  2002/04/19 09:00:05  skulawik
Fehler mit der Authentifizierung behoben, die Kontrollfunktion gab keinen Fehler zurück, wenn das nicht-eigene-Web überschrieben werden sollte

Revision 2.1  2002/04/12 12:48:33  skulawik
Versionsinfos eingetragen

###############################################################################
classes/class.soap_server.php

SOAPx4, a SOAP Toolkit for PHP: Server Class

Copyright (C) 2001  Dietrich Ayala <dietrich@ganx4.com>

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
									  
This project was inspired by the projects below, many thanks.
XML-RPC for PHP, by Edd Dumbill
SOAP for PHP, by Victor Zou

for example usage of the server, see the test_server.php file that
is included with the distribution.
#############################################################################*/

/*
changelog:
2001-07-05
- detection of character encoding in Content-Type header. server
will now call the soap_parser object with the specified encoding
- server will now return the Content-Type header with the sender's encoding type specified
must still learn more bout encoding, and figure out what i have to do to be able to
make sure that my response is *actually* encoded correctly
2001-07-21
- force proper error reporting for windows compatibility
2001-07-27
- get_all_headers() check for windows compatibility
*/

// make errors handle properly in windows
error_reporting(2039);

class soap_server {
	
	function soap_server() {
		// create empty dispatch map
		$this->dispatch_map = array();
		$this->debug_flag = false;
		$this->debug_str = "";
		$this->headers = "";
		$this->request = "";
		$this->xml_encoding = "UTF-8";
		$this->fault = false;
		$this->fault_code = "";
		$this->fault_str = "";
		$this->fault_actor = "";
		// for logging interop results to db
		$this->result = "successful";
	}
	
	// parses request and posts response
	function service($data){
		// $response is a soap_msg object
		$response = $this->parse_request($data);
		$this->debug("parsed request and got an object of this class '".get_class($response)."'");
		$this->debug("server sending...");
		// pass along the debug string
		if($this->debug_flag){
			$response->debug($this->debug_str);
		}
		$payload = $response->serialize();
		// print headers
		if($this->fault){
			$header[] = "HTTP/1.0 500 Internal Server Error\r\n";
		} else {
			$header[] = "HTTP/1.0 200 OK\r\n";
			$header[] = "Status: 200\r\n";
		}
		$header[] = "Server: SOAPx4 Server v0.5\r\n";
		$header[] = "Connection: Close\r\n";
		$header[] = "Content-Type: text/xml; charset=$this->xml_encoding\r\n";
		$header[] = "Content-Length: ".strlen($payload)."\r\n\r\n";
		reset($header);
		foreach($header as $hdr){
			header($hdr);
		}
		print $payload;
	}

	function parse_request($data="") {
		$this->debug("entering parseRequest() on ".date("H:i Y-m-d"));
		$this->debug("$request uri: ".$HTTP_SERVER_VARS["REQUEST_URI"]);
		// get headers
		if(function_exists("getallheaders")){
			$this->headers = getallheaders();
			foreach($this->headers as $k=>$v){
				$dump .= "$k: $v\r\n";
			}
			// get SOAPAction header
			if($headers_array["SOAPAction"]){
				$this->SOAPAction = str_replace('"','',$headers_array["SOAPAction"]);
				$this->service = $this->SOAPAction;
			}
			// get the character encoding of the incoming request
			if(ereg("=",$headers_array["Content-Type"])){
				$enc = str_replace("\"","",substr(strstr($headers_array["Content-Type"],"="),1));
				if(eregi("^(ISO-8859-1|US-ASCII|UTF-8)$",$enc)){
					$this->xml_encoding = $enc;
				} else {
					$this->xml_encoding = "us-ascii";
				}
			}
			$this->debug("got encoding: $this->xml_encoding");
		}
		$this->request = $dump."\r\n\r\n".$data;
		// parse response, get soap parser obj
		$parser = new soap_parser($data,$this->xml_encoding);
		// get/set methodname
		$this->methodname = $parser->root_struct_name;
		$this->debug("method name: $this->methodname");
		// does method exist?
		if(function_exists($this->methodname)){
			$this->debug("method '$this->methodname' exists");
		} else {
			// "method not found" fault here
			$this->debug("method '$this->methodname' not found!");
			$this->result = "fault: method not found";
			$this->make_fault("Server","method '$this->methodname' not defined in service '$this->service'");
			return $this->fault();
		}
		// if fault occurred during message parsing
		if($parser->fault()){
			// parser debug
			$this->debug($parser->debug_str);
			$this->result = "fault: error in msg parsing or eval";
			$this->make_fault("Server","error in msg parsing or eval:\n".$parser->get_response());
			// return soapresp
			return $this->fault();
		// else successfully parsed request into soapval object
		} else {
			// get eval_str
			$this->debug("calling parser->get_response()");
			// evaluate it, getting back a soapval object
			if(!$request_val = $parser->get_response()){
				return $this->fault();
			}
			// parser debug
			$this->debug($parser->debug_str);
			if($parser->namespaces["xsd"] != ""){
					//print "got ".$parser->namespaces["xsd"];
					global $XMLSchemaVersion,$namespaces;
					$XMLSchemaVersion = $parser->namespaces["xsd"];
					$tmpNS = array_flip($namespaces);
					$tmpNS["xsd"] = $XMLSchemaVersion;
					$tmpNS["xsi"] = $XMLSchemaVersion."-instance";
					$namespaces = array_flip($tmpNS);
				}
			if(get_class($request_val) == "soapval"){
				// verify that soapval objects in request match the methods signature
				if($this->verify_method($request_val)){
					$this->debug("request data - name: $request_val->name, type: $request_val->type, value: $request_val->value");
					if($this->input_value){// decode the soapval object, and pass resulting values to the requested method
						if(!$request_data = $request_val->decode()){
							$this->make_fault("Server","Unable to decode response from soapval object into native php type.");
							return $this->fault();
						}
						$this->debug("request data: $request_data");
					}
					
					// if there are return values
					if($this->return_type = $this->get_return_type()){
						$this->debug("got return type: '$this->return_type'");
						// if there are parameters to pass
						if($request_data){
							// call method with parameters
							$this->debug("about to call method '$this->methodname'");
							if(!$method_response = call_user_func_array("$this->methodname",$request_data)){
								$this->make_fault("Server","Method call failed for '$this->methodname' with params: ".join(",",$request_data));
								return $this->fault();
							}
						} else {
							// call method w/ no parameters
							$this->debug("about to call method '$this->methodname'");
							if(!$method_response = call_user_func("$this->methodname")){
								$this->make_fault("Server","Method call failed for '$this->methodname' with no params");
								return $this->fault();
							}
						}
					// no return values
					} else {
						if($request_data){
							// call method with parameters
							$this->debug("about to call method '$this->methodname'");
							call_user_func_array("$this->methodname",$request_data);
						} else {
							// call method w/ no parameters
							$this->debug("about to call method '$this->methodname'");
							call_user_func("$this->methodname",$request_data);
						}
					}
					
					// return fault
					if(get_class($method_response) == "soapmsg"){
						if(eregi("fault",$method_response->value->name)){
							$this->fault = true;
						}
						$return_msg = $method_response;
					} else {
						// return soapval object
						if(get_class($method_response) == "soapval"){
							$return_val = $method_response;
						// create soap_val object w/ return values from method, use method signature to determine type
						} else {
							$return_val = new soapval($this->methodname,$this->return_type,$method_response);
						}
						$this->debug($return_val->debug_str);
						// response object is a soap_msg object
						$return_msg =  new soapmsg($this->methodname."Response",array($return_val),"$this->service");
					}
					if($this->debug_flag){
						$return_msg->debug_flag = true;
					}
					$this->result = "successful";
					return $return_msg;
				} else {
					// debug
					$this->debug("ERROR: request not verified against method signature");
					$this->result = "fault: request failed validation against method signature";
					// return soapresp
					return $this->fault();
				}
			} else {
				// debug
				$this->debug("ERROR: parser did not return soapval object: $request_val ".get_class($request_val));
				$this->result = "fault: parser did not return soapval object: $request_val";
				// return fault
				$this->make_fault("Server","parser did not return soapval object: $request_val");
				return $this->fault();
			}
		}
	}
	
	function verify_method($request){
		//return true;
		$this->debug("entered verify_method() w/ request name: ".$request->name);
		$params = $request->value;
		// if there are input parameters required...
		if($sig = $this->dispatch_map[$this->methodname]["in"]){
			$this->input_value = count($sig);
			if(is_array($params)){
				$this->debug("entered verify_method() with ".count($params)." parameters");
				foreach($params as $v){
					$this->debug("param '$v->name' of type '$v->type'");
				}
				// validate the number of parameters
				if(count($params) == count($sig)){
					$this->debug("got correct number of parameters: ".count($sig));
					// make array of param types
					foreach($params as $param){
						$p[] = strtolower($param->type);
					}
					// validate each param's type
					for($i=0; $i < count($p); $i++){
						// type not match
						if(strtolower($sig[$i]) != strtolower($p[$i])){
							$this->debug("mismatched parameter types: $sig[$i] != $p[$i]");
							$this->make_fault("Client","soap request contained mismatching parameters of name $v->name had type $p[$i], which did not match signature's type: $sig[$i]");
							return false;
						}
						$this->debug("parameter type match: $sig[$i] = $p[$i]");
					}
					return true;
				// oops, wrong number of paramss
				} else {
					$this->debug("oops, wrong number of parameter!");
					$this->make_fault("Client","soap request contained incorrect number of parameters. method '$this->methodname' required ".count($sig)." and request provided ".count($params));
					return false;
				}
			// oops, no params...
			} else {
				$this->debug("oops, no parameters sent! Method '$this->methodname' requires ".count($sig)." input parameters!");
				$this->make_fault("Client","soap request contained incorrect number of parameters. method '$this->methodname' requires ".count($sig)." parameters, and request provided none");
				return false;
			}
			// no params
		} elseif( (count($params)==0) && (count($sig) <= 1) ){
			$this->input_values = 0;
			return true;
		} else {
			//$this->debug("well, request passed parameters to a method that requires none?");
			//$this->make_fault("Client","method '$this->methodname' requires no parameters. The request passed in ".count($params).": ".@implode(" param: ",$params) );
			return true;
		}
	}
	
	// get string return type from dispatch map
	function get_return_type(){
		if(count($this->dispatch_map[$this->methodname]["out"]) >= 1){
			$type = array_shift($this->dispatch_map[$this->methodname]["out"]);
			$this->debug("got return type from dispatch map: '$type'");
			return $type;
		}
		return false;
	}
	
	// dbg
	function debug($string){
		if($this->debug_flag){
			$this->debug_str .= "$string\n";
		}
	}
	
	// add a method to the dispatch map
	function add_to_map($methodname,$in,$out){
		$this->dispatch_map[$methodname]["in"] = $in;
		$this->dispatch_map[$methodname]["out"] = $out;
	}
	
	// set up a fault
	function fault(){
		return new soapmsg("Fault",
				array(
					"faultcode" => $this->fault_code,
					"faultstring" => $this->fault_str,
					"faultactor" => $this->fault_actor,
					"faultdetail" => $this->fault_detail.$this->debug_str
				),
			"http://schemas.xmlsoap.org/soap/envelope/"
		);
	}
	
	function make_fault($fault_code,$fault_string){
		$this->fault_code = $fault_code;
		$this->fault_str = $fault_string;
		$this->fault();
		$this->fault = true;
		
	}
}
/* 	##################################################################################################### 
   	classes/class.soap_client.php
   	##################################################################################################### */

// make errors handle properly in windows (thx, thong@xmethods.com)
error_reporting(2039);

// set default encoding
$soap_defencoding = "UTF-8";
// set schema version
$XMLSchemaVersion = "http://www.w3.org/2001/XMLSchema";

// load types into typemap array
$typemap["http://www.w3.org/2001/XMLSchema"] = array(
	"string","boolean","float","double","decimal","duration","dateTime","time",
	"date","gYearMonth","gYear","gMonthDay","gDay","gMonth","hexBinary","base64Binary",
	// derived datatypes
	"normalizedString","token","language","NMTOKEN","NMTOKENS","Name","NCName","ID",
	"IDREF","IDREFS","ENTITY","ENTITIES","integer","nonPositiveInteger",
	"negativeInteger","long","int","short","byte","nonNegativeInteger",
	"unsignedLong","unsignedInt","unsignedShort","unsignedByte","positiveInteger");
$typemap["http://www.w3.org/1999/XMLSchema"] = array(
	"i4","int","boolean","string","double","float","dateTime",
	"timeInstant","base64Binary","base64","ur-type");
$typemap["http://soapinterop.org/xsd"] = array("SOAPStruct");
$typemap["http://schemas.xmlsoap.org/soap/encoding/"] = array("base64","array","Array");

// load namespace uris into an array of uri => prefix
$namespaces = array(
	"http://schemas.xmlsoap.org/soap/envelope/" => "SOAP-ENV",
	$XMLSchemaVersion => "xsd",
	$XMLSchemaVersion."-instance" => "xsi",
	"http://schemas.xmlsoap.org/soap/encoding/" => "SOAP-ENC",
	"http://soapinterop.org/xsd"=>"si");

$xmlEntities = array("quot" => '"',"amp" => "&",
	"lt" => "<","gt" => ">","apos" => "'");

// $path can be a complete endpoint url, with the other parameters left blank:
// $soap_client = new soap_client("http://path/to/soap/server");
class soap_client {

	 function soap_client($path, $server=false, $port=false){
		$this->port = 80;
		$this->path = $path;
		$this->server = $server;
		$this->errno;
		$this->errstring;
		$this->debug_flag = false;
		$this->debug_str = "";
		$this->username = "";
		$this->password = "";
		$this->action = "";
		$this->incoming_payload = "";
		$this->outgoing_payload = "";
		$this->response = "";
		$this->action = "";
		
		// endpoint mangling
		if(ereg("^http://",$path)){
			$path = str_replace("http://","",$path);
			$this->path = strstr($path,"/");
			$this->debug("path = $this->path");
			if(ereg(":",$path)){
				$this->server = substr($path,0,strpos($path,":"));
				$this->port = substr(strstr($path,":"),1);
				$this->port = substr($this->port,0,strpos($this->port,"/"));
			} else {
				$this->server = substr($path,0,strpos($path,"/"));
			}
		}
		if($port){
			$this->port = $port;
		}
	 }
	
	function setCredentials($username, $pword) {
		$this->username = $username;
		$this->password = $pword;
	}
	
	 function send($msg, $action, $timeout=0) {
		// where msg is an soapmsg
		if($this->debug_flag){
			$msg->debug_flag = true;
		}
		$this->action = $action;
		return $this->sendPayloadHTTP10(
			$msg,
			$this->server,
			$this->port,
			$timeout,
			$this->username,
			$this->password
		);
	 }
	
	function sendPayloadHTTP10($msg, $server, $port, $timeout=0, $username="", $password="") {
		
		if($timeout > 0){
			$fp = fsockopen($server, $port,&$this->errno, &$this->errstr, $timeout);
		} else {
			$fp = fsockopen($server, $port,&$this->errno, &$this->errstr);
		}
		if (!$fp) {
			$this->debug("Couldn't open socket connection to server!");
			$this->debug("Server: $this->server"); 
			return 0;
		}
		
		$credentials = "";
		if($username != "") {
			$credentials = "Authorization: Basic ".base64_encode("$username:$password")."\r\n";
		}
		
		$soap_data = $msg->serialize();
		$this->outgoing_payload = 
			"POST ".$this->path." HTTP/1.0\r\n".
			"User-Agent: SOAPx4 v0.5\r\n".
			"Host: ".$this->server."\r\n".
			$credentials. 
			"Content-Type: text/xml\r\nContent-Length: ".strlen($soap_data)."\r\n".
			"SOAPAction: \"$this->action\""."\r\n\r\n".
			$soap_data;
		// send
		if(!fputs($fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->debug("Write error");
		}
		
		// get reponse
		while($data = fread($fp, 32768)) {
	    	$incoming_payload .= $data;
		}
		
		fclose($fp);
		$this->incoming_payload = $incoming_payload;
		// $response is a soapmsg object
		$this->response = $msg->parseResponse($incoming_payload);
		$this->debug($msg->debug_str);
		return $this->response;
	}
	
	function debug($string){
		if($this->debug_flag){
			$this->debug_str .= "$string\n";
		}
	}

} // end class soap_client

// soap message class
class soapmsg {
	// params is an array of soapval objects
	function soapmsg($method,$params,$method_namespace="http://testuri.org",$new_namespaces=false){
		// globalize method namespace
		global $methodNamespace;
		$methodNamespace = $method_namespace;
		// make method struct
		$this->value = new soapval($method,"struct",$params,$method_namespace);
		if(is_array($new_namespaces)){
			global $namespaces;
			$i = count($namespaces);
			foreach($new_namespaces as $v){
				$namespaces[$v] = "ns".$i++;
			}
			$this->namespaces = $namespaces;
		}
		$this->payload = "";
		$this->debug_flag = false;
		$this->debug_str = "entering soapmsg() with soapval ".$this->value->name."\n";
  	}
	
	function make_envelope($payload) {
		global $namespaces;
		foreach($namespaces as $k => $v){
			$ns_string .= "xmlns:$v=\"$k\" ";
		}
		return "<SOAP-ENV:Envelope $ns_string SOAP-ENV:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">\n".
			   $payload.
			   "</SOAP-ENV:Envelope>\n";
	}
	
	function make_body($payload) {
		return "<SOAP-ENV:Body>\n".
				$payload.
				"</SOAP-ENV:Body>\n";
	}
	
	function createPayload() {
		$value = $this->value;
		$payload = $this->make_envelope($this->make_body($value->serialize()));
		$this->debug($value->debug_str);
		$payload = "<?xml version=\"1.0\"?>\n".$payload;
		if($this->debug_flag){
			$payload .= $this->serializeDebug();
		}
		$this->payload = str_replace("\n","\r\n", $payload);
	}
	
	function serialize(){
		if($this->payload == ""){
			$this->createPayload();
			return $this->payload;
		} else {
			return $this->payload;
		}
	}
	
	// returns a soapval object
    function parseResponse($data) {
		$this->debug("Entering parseResponse()");
		//$this->debug(" w/ data $data");
		// strip headers here
		//$clean_data = ereg_replace("\r\n","\n", $data);
		if(ereg("^.*\r\n\r\n<",$data)) {
			$this->debug("found proper separation of headers and document");
			$this->debug("getting rid of headers, stringlen: ".strlen($data));
			$clean_data = ereg_replace("^.*\r\n\r\n<","<", $data);
			$this->debug("cleaned data, stringlen: ".strlen($clean_data));
		} else {
			// return fault
			return new soapval("fault","SOAPStruct",array(new soapval("faultcode","string","SOAP-MSG"),new soapval("faultstring","string","HTTP error"),new soapval("faultdetail","string","HTTP headers were not immediately followed by '\r\n\r\n'")));
		}
		/* if response is a proper http response, and is not a 200
		if(ereg("^HTTP",$clean_data) && !ereg("200$", $clean_data)){
			// get error data
			$errstr = substr($clean_data, 0, strpos($clean_data, "\n")-1);
			// return fault
			return new soapval("fault","SOAPStruct",array(new soapval("faultcode","string","SOAP-MSG"),new soapval("faultstring","string","HTTP error")));
		}*/
		$this->debug("about to create parser instance w/ data: $clean_data");
		// parse response
		$response = new soap_parser($clean_data);
		// return array of parameters
		$ret = $response->get_response();
		$this->debug($response->debug_str);
		return $ret;
 	}
	
	// dbg
	function debug($string){
		if($this->debug_flag){
			$this->debug_str .= "$string\n";
		}
	}
	
	// preps debug data for encoding into soapmsg
	function serializeDebug() {
		if($this->debug_flag){
			return "<!-- DEBUG INFO:\n".$this->debug_str."-->\n";
		} else {
			return "";
		}
	}
}

// soap value object
class soapval {

  	function soapval($name="",$type=false,$value=-1,$namespace=false,$type_namespace=false) {
		global $soapTypes, $typemap, $namespaces, $methodNamespace, $XMLSchemaVersion;
		// detect type if not passed
		if(!$type){
			if(is_array($value) && count($value) >= 1){
				if(ereg("[a-zA-Z0-9\-]*",key($v))){
					$type = "struct";
				} else {
					$type = "array";
				}
			} elseif(is_int($v)){
				$type = "int";
			} elseif(is_float($v) || $v == "NaN" || $v == "INF"){
				$type = "float";
			} else {
				$type = gettype($value);
			}
		}
		// php type name mangle
		if($type == "integer"){
			$type = "int";
		}
		
		$this->soapTypes = $soapTypes;
		$this->name = $name;
		$this->value = "";
		$this->type = $type;
		$this->type_code = 0;
		$this->type_prefix = false;
		$this->array_type = "";
		$this->debug_flag = false;
		$this->debug_str = "";
		$this->debug("Entering soapval - name: '$name' type: '$type'");
		
		if($namespace){
			$this->namespace = $namespace;
			if(!isset($namespaces[$namespace])){
				$namespaces[$namespace] = "ns".(count($namespaces)+1);
			}
			$this->prefix = $namespaces[$namespace];
		}
		
		// get type prefix
		if(ereg(":",$type)){
			$this->type = substr(strrchr($type,":"),1,strlen(strrchr($type,":")));
			$this->type_prefix = substr($type,0,strpos($type,":"));
		} elseif($type_namespace){
			if(!isset($namespaces[$type_namespace])){
				$namespaces[$type_namespace] = "ns".(count($namespaces)+1);
			}
			$this->type_prefix = $namespaces[$type_namespace];
		// if type namespace was not explicitly passed, and we're not in a method struct:
		} elseif(!$this->type_prefix && !isset($this->namespace)){
			// try to get type prefix from typeMap
			if(!$ns = $this->verify_type($type)){
				// else default to method namespace
				$this->type_prefix = $namespaces[$methodNamespace];
			} else {
				$this->type_prefix = $namespaces[$ns];
			}
		}
		
		// if scalar
		if(in_array($this->type,$typemap[$XMLSchemaVersion])) {
			$this->type_code = 1;
			$this->addScalar($value,$this->type,$name);
		// if array
		} elseif(eregi("^(array|ur-type)$",$this->type)){
			$this->type_code = 2;
			$this->addArray($value);
		// if struct
		} elseif(eregi("struct",$this->type)){
			$this->type_code = 3;
			$this->addStruct($value);
		} else {
			$this->type_code = 3;
			$this->addStruct($value);
		}
    }

	function addScalar($value, $type, $name=""){
		$this->debug("adding scalar '$name' of type '$type'");
		
		// if boolean, change value to 1 or 0
		if ($type == "boolean") {
			if((strcasecmp($value,"true") == 0) || ($value == 1)) {
				$value = 1;
			} else {
				$value = 0;
			}
		}
		
		$this->value = $value;
		return true;
    }

	function addArray($vals){
		$this->debug("adding array '$this->name' with ".count($vals)." vals");
		$this->value = array();
		if(is_array($vals) && count($vals) >= 1){
			foreach($vals as $k => $v){
				$this->debug("checking value $k : $v");
				// if soapval, add..
				if(get_class($v) == "soapval"){
					$this->value[] = $v;
					$this->debug($v->debug_str);
				// else make obj and serialize
				} else {
					if(is_array($v)){
						if(ereg("[a-zA-Z\-]*",key($v))){
							$type = "struct";
						} else {
							$type = "array";
						}
					} elseif(!ereg("^[0-9]*$",$k) && $this->verify_type($k)){
						$type = $k;
					} elseif(is_int($v)){
						$type = "int";
					} elseif(is_float($v) || $v == "NaN" || $v == "INF"){
						$type = "float";
					} else {
						$type = gettype($v);
					}
					$new_val =  new soapval("item",$type,$v);
					$this->debug($new_val->debug_str);
					$this->value[] = $new_val;
				}
			}
		}
		return true;
	}

	function addStruct($vals){
		$this->debug("adding struct '$this->name' with ".count($vals)." vals");
		if(is_array($vals) && count($vals) >= 1){
			foreach($vals as $k => $v){
				// if serialize, if soapval
				if(get_class($v) == "soapval"){
					$this->value[] = $v;
					$this->debug($v->debug_str);
				// else make obj and serialize
				} else {
					if(is_array($v)){
						foreach($v as $a => $b){
							if($a == "0"){
								$type = "array";
							} else {
								$type = "struct";
							}
							break;
						}
					} elseif($this->verify_type($k)){
						$this->debug("got type '$type' for value '$v' from typemap!");
						$type = $k;
					} elseif(is_int($v)){
						$type = "int";
					} elseif(is_float($v) || $v == "NaN" || $v == "INF"){
						$type = "float";
					} else {
						$type = gettype($v);
						$this->debug("got type '$type' for value '$v' from php gettype()!");
					}
					$new_val = new soapval($k,$type,$v);
					$this->debug($new_val->debug_str);
					$this->value[] = $new_val;
				}
			}
		} else {
			$this->value = array();
		}
		return true;
	}
	
	// turn soapvals into xml, woohoo!
	function serializeval($soapval=false) {
		if(!$soapval){
			$soapval = $this;
		}
		$this->debug("serializing '$soapval->name' of type '$soapval->type'");
		if($soapval->name == ""){
			$soapval->name = "return";
		}
		
		switch($soapval->type_code) {
			case 3:
				// struct
				$this->debug("got a struct");
				if($soapval->prefix && $soapval->type_prefix){
					$xml .= "<$soapval->prefix:$soapval->name xsi:type=\"$soapval->type_prefix:$soapval->type\">\n";
				} elseif($soapval->type_prefix){
					$xml .= "<$soapval->name xsi:type=\"$soapval->type_prefix:$soapval->type\">\n";
				} elseif($soapval->prefix){
					$xml .= "<$soapval->prefix:$soapval->name>\n";
				} else {
					$xml .= "<$soapval->name>\n";
				}
				if(is_array($soapval->value)){
					foreach($soapval->value as $k => $v){
						$xml .= $this->serializeval($v);
					}
				}
				if($soapval->prefix){
					$xml .= "</$soapval->prefix:$soapval->name>\n";
				} else {
					$xml .= "</$soapval->name>\n";
				}
				break;
			case 2:
				// array
				foreach($soapval->value as $array_val){
					$array_types[$array_val->type] = 1;
					$xml .= $this->serializeval($array_val);
				}
				if(count($array_types) > 1){
					$array_type = "xsd:ur-type";
				} elseif(count($array_types) >= 1){
					$array_type = $array_val->type_prefix.":".$array_val->type;
				}
				
				$xml = "<$soapval->name xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"".$array_type."[".sizeof($soapval->value)."]\">\n".$xml."</$soapval->name>\n";
				break;
			case 1:
				$xml .= "<$soapval->name xsi:type=\"$soapval->type_prefix:$soapval->type\">$soapval->value</$soapval->name>\n";
				break;
			default:
				break;
		}
		return $xml;
	}
	
	// serialize
	function serialize() {
		return $this->serializeval($this);
    }
	
	function decode($soapval=false){
		if(!$soapval){
			$soapval = $this;
		}
		// scalar decode
		if($soapval->type_code == 1){
			return $soapval->value;
		// array decode
		} elseif($soapval->type_code == 2){
			if(is_array($soapval->value)){
				foreach($soapval->value as $item){
					$return[] = $this->decode($item);
				}
				return $return;
			} else {
				return array();
			}
		// struct decode
		} elseif($soapval->type_code == 3){
			if(is_array($soapval->value)){
				foreach($soapval->value as $item){
					$return[$item->name] = $this->decode($item);
				}
				return $return;
			} else {
				return array();
			}
		}
	}
	
	// pass it a type, and it attempts to return a namespace uri
	function verify_type($type){
		global $typemap,$namespaces;
		/*foreach($typemap as $namespace => $types){
			if(is_array($types) && in_array($type,$types)){
				return $namespace;
			}
		}*/
		foreach($namespaces as $uri => $prefix){
			if(is_array($typemap[$uri]) && in_array($type,$typemap[$uri])){
				return $uri;
			}
		}
		return false;
	}
	
	// alias for verify_type() - pass it a type, and it returns it's prefix
	function get_prefix($type){
		if($prefix = $this->verify_type($type)){
			return $prefix;
		}
		return false;
	}
	
	function debug($string){
		if($this->debug_flag){
			$this->debug_str .= "$string\n";
		}
	}
}

class soap_parser {

	function soap_parser($xml,$encoding="UTF-8"){
		//global $soapTypes;
		//$this->soapTypes = $soapTypes;
		$this->xml = $xml;
		$this->xml_encoding = $encoding;
		$this->root_struct = "";
		// determines where in the message we are (envelope,header,body,method)
		$this->status = "";
		$this->position = 0;
		$this->pos_stat = 0;
		$this->depth = 0;
		$this->default_namespace = "";
		$this->namespaces = array();
		$this->message = array();
		$this->fault = false;
		$this->fault_code = "";
		$this->fault_str = "";
		$this->fault_detail = "";
		$this->eval_str = "";
		$this->depth_array = array();
		$this->debug_flag = true;
		$this->debug_str = "";
		$this->previous_element = "";
		
		$this->entities = array ( "&" => "&amp;", "<" => "&lt;", ">" => "&gt;",
        "'" => "&apos;", '"' => "&quot;" );
		
		// Check whether content has been read.
        if(!empty($xml)){
			$this->debug("Entering soap_parser()");
			//$this->debug("DATA DUMP:\n\n$xml");
            // Create an XML parser.
            $this->parser = xml_parser_create($this->xml_encoding);
            // Set the options for parsing the XML data.
            //xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
            xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
            // Set the object for the parser.
            xml_set_object($this->parser, &$this);
            // Set the element handlers for the parser.
            xml_set_element_handler($this->parser, "start_element","end_element");
            xml_set_character_data_handler($this->parser,"character_data");
			xml_set_default_handler($this->parser, "default_handler");
            
            // Parse the XML file.
            if(!xml_parse($this->parser,$xml,true)){
                // Display an error message.
                $this->debug(sprintf("XML error on line %d: %s",
                	xml_get_current_line_number($this->parser),
                	xml_error_string(xml_get_error_code($this->parser))));
				$this->fault = true;
            } else {
				// get final eval string
				$this->eval_str = "\$response = ".trim($this->build_eval($this->root_struct)).";";
			}
			xml_parser_free($this->parser);
        } else {
			$this->debug("xml was empty, didn't parse!");
		}
	}

	// loop through msg, building eval_str
	function build_eval($pos){
		$this->debug("inside build_eval() for $pos: ".$this->message[$pos]["name"]);
		$eval_str = $this->message[$pos]["eval_str"];
		// loop through children, building...
		if($this->message[$pos]["children"] != ""){
			$this->debug("children string = ".$this->message[$pos]["children"]);
			$children = explode("|",$this->message[$pos]["children"]);
			$this->debug("it has ".count($children)." children");
			foreach($children as $c => $child_pos){
				//$this->debug("child pos $child_pos: ".$this->message[$child_pos]["name"]);
				if($this->message[$child_pos]["eval_str"] != ""){
					$this->debug("entering build_eval() for ".$this->message[$child_pos]["name"].", array pos $c, pos: $child_pos");
					$eval_str .= $this->build_eval($child_pos).", ";
				}
			}
			$eval_str = substr($eval_str,0,strlen($eval_str)-2);
		}
		// add current node's eval_str
		$eval_str .= $this->message[$pos]["end_eval_str"];
		return $eval_str;
	}
	
	// start-element handler
	function start_element($parser, $name, $attrs) {
		// position in a total number of elements, starting from 0
		// update class level pos
		$pos = $this->position++;
		// and set mine
		$this->message[$pos]["pos"] = $pos;
		// parent/child/depth determinations
		
		// depth = how many levels removed from root?
		// set mine as current global depth and increment global depth value
		$this->message[$pos]["depth"] = $this->depth++;
		
		// else add self as child to whoever the current parent is
		if($pos != 0){
			$this->message[$this->parent]["children"] .= "|$pos";
		}
		// set my parent
		$this->message[$pos]["parent"] = $this->parent;
		// set self as current value for this depth
		$this->depth_array[$this->depth] = $pos;
		// set self as current parent
		$this->parent = $pos;
		
		// set status
		if(ereg(":Envelope$",$name)){
			$this->status = "envelope";
		} elseif(ereg(":Header$",$name)){
			$this->status = "header";
		} elseif(ereg(":Body$",$name)){
			$this->status = "body";
		// set method
		} elseif($this->status == "body"){
			$this->status = "method";
			if(ereg(":",$name)){
				$this->root_struct_name = substr(strrchr($name,":"),1);
			} else {
				$this->root_struct_name = $name;
			}
			$this->root_struct = $pos;
			$this->message[$pos]["type"] = "struct";
		}
		// set my status
		$this->message[$pos]["status"] = $this->status;
		
		// set name
		$this->message[$pos]["name"] = htmlspecialchars($name);
		// set attrs
		$this->message[$pos]["attrs"] = $attrs;
		// get namespace
		if(ereg(":",$name)){
			$namespace = substr($name,0,strpos($name,":"));
			$this->message[$pos]["namespace"] = $namespace;
			$this->default_namespace = $namespace;
		} else {
			$this->message[$pos]["namespace"] = $this->default_namespace;
		}
		// loop through atts, logging ns and type declarations
		foreach($attrs as $key => $value){
			// if ns declarations, add to class level array of valid namespaces
			if(ereg("xmlns:",$key)){
				$prefix = substr(strrchr($key,":"),1);
				$this->namespaces[substr(strrchr($key,":"),1)] = $value;
				// set method namespace
				if($name == $this->root_struct_name){
					$this->methodNamespace = $value;
				}
			// if it's a type declaration, set type
			} elseif($key == "xsi:type"){
				$this->message[$pos]["type"] = substr(strrchr($value,":"),1);
				// should do something here with the namespace of specified type?
			}
		}
		
		// debug
		//$this->debug("parsed $name start, eval = '".$this->message[$pos]["eval_str"]."'");
	}
	
	// end-element handler
	function end_element($parser, $name) {
		// position of current element is equal to the last value left in depth_array for my depth
		$pos = $this->depth_array[$this->depth];
		// bring depth down a notch
		$this->depth--;
		
		// get type if not explicitly declared in an xsi:type attribute
		// man is this fucked up. can't do wsdl like dis!
		if($this->message[$pos]["type"] == ""){
			if($this->message[$pos]["children"] != ""){
				$this->message[$pos]["type"] = "SOAPStruct";
			} else {
				$this->message[$pos]["type"] = "string";
			}
		}
		
		// set eval str start if it has a valid type and is inside the method
		if($pos >= $this->root_struct){
			$this->message[$pos]["eval_str"] .= "\n new soapval(\"".htmlspecialchars($name)."\", \"".$this->message[$pos]["type"]."\" ";
			$this->message[$pos]["end_eval_str"] = ")";
			$this->message[$pos]["inval"] = "true";
			
			if($this->message[$pos]["children"] != ""){
				$this->message[$pos]["eval_str"] .= ", array( ";
				$this->message[$pos]["end_eval_str"] .= " )";
			}
		}
		
		// if i have no children and have cdata...then i must be a scalar value, so add my data to the eval_str
		if($this->status == "method" && $this->message[$pos]["children"] == ""){
			// add cdata w/ no quotes if only int/float/dbl
			if($this->message[$pos]["type"] == "string"){
				$this->message[$pos]["eval_str"] .= ", \"".$this->message[$pos]["cdata"]."\"";
			} elseif($this->message[$pos]["type"] == "int" || $this->message[$pos]["type"] == "float" || $this->message[$pos]["type"] == "double"){
				//$this->debug("adding cdata w/o quotes");
				$this->message[$pos]["eval_str"] .= ", ".trim($this->message[$pos]["cdata"]);
			} elseif(is_string($this->message[$pos]["cdata"])){
				//$this->debug("adding cdata w/ quotes");
				$this->message[$pos]["eval_str"] .= ", \"".$this->message[$pos]["cdata"]."\"";
			}
		}
		// if in the process of making a soap_val, close the parentheses and move on...
		if($this->message[$pos]["inval"] == "true"){
			$this->message[$pos]["inval"] == "false";
		}
		// if tag we are currently closing is the method wrapper
		if($pos == $this->root_struct){
			$this->status = "body";
		} elseif(ereg(":Body",$name)){
			$this->status = "header";
 		} elseif(ereg(":Header",$name)){
			$this->status = "envelope";
		}
		// set parent back to my parent
		$this->parent = $this->message[$pos]["parent"];
		$this->debug("parsed $name end, type '".$this->message[$pos]["type"]."'eval_str = '".trim($this->message[$pos]["eval_str"])."' and children = ".$this->message[$pos]["children"]);
	}
	
	// element content handler
	function character_data($parser, $data){
		$pos = $this->depth_array[$this->depth];
		$this->message[$pos]["cdata"] .= $data;
		//$this->debug("parsed ".$this->message[$pos]["name"]." cdata, eval = '$this->eval_str'");
	}
	
	// default handler
	function default_handler($parser, $data){
		//$this->debug("DEFAULT HANDLER: $data");
	}
	
	// function to check fault status
	function fault(){
		if($this->fault){
			return true;
		} else {
			return false;
		}
	}
	
	// have this return a soap_val object
	function get_response(){
		$this->debug("eval()ing eval_str: $this->eval_str");
		@eval("$this->eval_str");
		if($response){
			$this->debug("successfully eval'd msg");
			return $response;
		} else {
			$this->debug("ERROR: did not successfully eval the msg");
			$this->fault = true;
			return new soapval("Fault","struct",array(new soapval("faultcode","string","SOAP-ENV:Server"),new soapval("faultstring","string","couldn't eval \"$this->eval_str\"")));
		}
	}
	
	function debug($string){
		if($this->debug_flag){
			$this->debug_str .= "$string\n";
		}
	}
	
	function decode_entities($text){
		foreach($this->entities as $entity => $encoded){
			$text = str_replace($encoded,$entity,$text);
		}
		return $text;
	}
}

/* soapx4 high level class

usage:

// instantiate client with server info
$soapclient = new soapclient( string path [ ,boolean wsdl] );

// call method, get results
echo $soapclient->call( string methodname [ ,array parameters] );

// bye bye client
unset($soapclient);

*/

class soapclient {

	function soapclient($endpoint,$wsdl=false,$portName=false){
		$this->debug_flag = false;
		$this->endpoint = $endpoint;
		$this->portName = false;
		
		// make values
		if($wsdl){
			$this->endpointType = "wsdl";
			$this->wsdlFile = $this->endpoint;
			// instantiate wsdl class
			$this->wsdl = new wsdl($this->endpoint);
			if($portName){
				$this->portName = $portName;
			}
		}
	}
	
	function call($method,$params=array(),$namespace=false,$soapAction=false){
		if($this->endpointType == "wsdl"){
			// get portName
			if(!$this->portName){
				$this->portName = $this->wsdl->getPortName($method);
			}
			// get endpoint
			if(!$this->endpoint = $this->wsdl->getEndpoint($this->portName)){
				die("no port of name '$this->portName' in the wsdl at that location!");
			}
			$this->debug("endpoint: $this->endpoint");
			$this->debug("portName: $this->portName");
			// get operation data
			if($opData = $this->wsdl->getOperationData($this->portName,$method)){
				$soapAction = $opData["soapAction"];
				// set input params
				$i = count($opData["input"]["parts"])-1;
				foreach($opData["input"]["parts"] as $name => $type){
					$params[$i] = new soapval($name,$type,$params[$i]);
				}
			} else {
				die("could not get operation info from wsdl for operation: $method<br>");
			}
		}
		$this->debug("soapAction: $soapAction");
		// get namespace
		if(!$namespace){
			if($this->endpointType != "wsdl"){
				die("method call requires namespace if wsdl is not available!");
			} elseif(!$namespace = $this->wsdl->getNamespace($this->portName,$method)){
				die("no namespace found in wsdl for operation: $method!");
			}
		}
		$this->debug("namespace: $namespace");
		
		// make message
		$soapmsg = new soapmsg($method,$params,$namespace);
		
		// instantiate client
		$dbg = "calling server at '$this->endpoint'...";
		if($soap_client = new soap_client($this->endpoint)){
			//$soap_client->debug_flag = true;
			$this->debug($dbg."instantiated client successfully");
			$this->debug("client data:<br>server: $soap_client->server<br>path: $soap_client->path<br>port: $soap_client->port");
			// send
			$dbg = "sending msg w/ soapaction '$soapAction'...";
			if($return = $soap_client->send($soapmsg,$soapAction)){
				$this->request = $soap_client->outgoing_payload;
				$this->response = $soap_client->incoming_payload;
				$this->debug($dbg."sent message successfully and got a '$return' back");
				// check for valid response
				if(get_class($return) == "soapval"){
					// fault?
					if(eregi("fault",$return->name)){
						$this->debug("got fault");
						$faultArray = $return->decode();
						foreach($faultArray as $k => $v){
							print "$k = $v<br>";
						}
						return false;
					} else {
						$returnArray = $return->decode();
						if(is_array($returnArray)){
							return array_shift($returnArray);
						} else {
							$this->debug("didn't get array back from decode() for $return->name");
							return false;
						}
					}
				} else {
					$this->debug("didn't get soapval object back from client");
					return false;
				}
			} else {
				$this->debug("client send/recieve error");
				return false;
			}
		}
	}
	
	function debug($string){
		if($this->debug_flag){
			print $string."<br>";
		}
	}
}

/* 	##################################################################################################### 
   	PUBLISHING FUNCTIONS
   	##################################################################################################### */
$server = new soap_server;

$server->add_to_map("echoString",
	array("string"),	// array of input types
	array("string")		// array of output types
);
function echoString($string){
	return $string;
}

function check_allowing($guid) {
	global $PHP_SELF;
	// Checkt die Gleichheit mit der Datei .htpublish
	// Wenn Datei nicht existent, wird sie erzeugt.
	$adir = dirname(__FILE__)."/";
	$afname = $adir.".htpublish";
	
	if (file_exists($afname)) {
		// Überprüfen
		$fh = fopen($afname, "r");
		$line = fgets($fh,1024);
		if ($line==$guid) {$ret=true;}else{$ret=false;}
	}else{
		$fh = fopen($afname, "w");
		fwrite($fh, $guid);
		$ret = true;
	}
	fclose($fh);
	//return file_exists($afname);
	return $ret;
}
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

function make_dir_tree($dirpath) {
	error_reporting(0);
	$base = dirname(__FILE__);
	$ret = "";
	if (@chdir($base.$dirpath)) { 
		return $ret; 
	} // Verzeichnis existiert schon
	else {
		$dirs = split("/", $dirpath);
		foreach($dirs as $key=>$val) {
			if ($val != "/") {
				if (right($base, 1) != "/") {$base .= "/";}
				$base .= $val;
				//echo "<b>$base<br></b>";
				if (!@chdir($base)) {
					umask(000); 
					if (!@mkdir($base,0777)) {
						$dp = $base;
						$err = true;
						break;
					}
				}
			}
		}
	}
	if ($err) {
		$ret = make_err("make_dir_tree", "Cant create Filepath", "Following Path: ".$dp." couldnt be created. Possible same Directory created by Hand ?Beware the Filerights ! You MUST use CHMOD 777 or give the Files and Directorys the same Group as your Webserver!");
	}
	return $ret;
}

function put_file($destdir, $destfile, $b64filecontent) {	
	$afname = dirname(__FILE__).$destdir.$destfile;
	$fh = fopen($afname, "w");
	fwrite($fh, base64_decode($b64filecontent));
	fclose($fh);
	return $afname;
}                  

function make_err($source="RPC-XML-SOAPSERVER", $string="Unknown Error", $message="") {
	$params = array(
		"faultcode" => $source,
		"faultstring" => $string,
		"detail" => $message
		);
	$faultmsg  = new soapmsg("Fault",$params,"http://schemas.xmlsoap.org/soap/envelope/");
	return $faultmsg;
}

$server->add_to_map("update_soapserver", array("struct"), array("struct"));
function update_soapserver($inp) {
	if (!is_array($inp)) {
		return make_err("update_soapserver", "Don't detected Array Input !");
	}
	if (check_allowing($inp["guid"])) {
 		switch (base64_decode($inp["process"])) {
		case "gotnew":
			put_file("/","soapserver.php.new", base64_decode($inp["newsoapserver"]));
			put_file("/","execute_update.php", base64_decode($inp["newexecutor"]));
			break;
   	case "delete_executer":
    		unlink(dirname(__FILE__)."/execute_update.php");
			break;
   	}
	}else{
		return make_err("update_soapserver", "No Access for this site");
	}
}


$server->add_to_map("check_file", array("struct"), array("struct"));
function check_file($inp) {
	global $server;
	if (!is_array($inp)) {
		return make_err("check_file", "Don't detected Array Input !");
	}
	if (check_allowing($inp["guid"])) {
		$fname = dirname(__FILE__).base64_decode($inp["fileurl"]).base64_decode($inp["filename"]);
//		$fname = "/www/helios/cms_live".base64_decode($inp["fileurl"]).base64_decode($inp["filename"]);
		if (file_exists($fname)) {
			$out["file_exists"] = "1";
			$out["file_size"] = filesize($fname);
			$out["file_ctime"] = filectime($fname);
		}else{
			$out["file_exists"] = "0";
		}
	}else{
		return make_err("check_file", "No Access for this site");
		//$out["err"] = "No Access for this site";
	}
	foreach($out as $key=>$val) {
		$out[$key] = base64_encode($val);
	}
	return $out;
}

$server->add_to_map("del_file", array("struct"), array("struct"));
function del_file($inp) {
	global $server;
	if (!is_array($inp)) {
		return make_err("del_file", "Don't detected Array Input !");
	}
	if (check_allowing($inp["guid"])) {
		$fname = dirname(__FILE__).base64_decode($inp["fileurl"]).base64_decode($inp["filename"]);
//		$fname = "/www/helios/cms_live".base64_decode($inp["fileurl"]).base64_decode($inp["filename"]);
		if (file_exists($fname)) {
			$out["file_exists"] = "1";
			$out["file_size"] = filesize($fname);
			$out["file_ctime"] = filectime($fname);
   		$out["return"] = unlink($fname);
		}else{
			$out["file_exists"] = "0";
		}
	}else{
		return make_err("del_file", "No Access for this site");
	}
	foreach($out as $key=>$val) {
		$out[$key] = base64_encode($val);
	}
	return $out;
}

$server->add_to_map("del_dir", array("struct"), array("struct"));
function del_dir($inp) {
	global $server;
	if (!is_array($inp)) {
		return make_err("del_file", "Don't detected Array Input !");
	}
	if (check_allowing($inp["guid"])) {
		$dirname = dirname(__FILE__).base64_decode($inp["fileurl"]);
		$out["return"] = rmdir($dirname);

	}else{
		return make_err("del_dir", "No Access for this site");
	}
	foreach($out as $key=>$val) {
		$out[$key] = base64_encode($val);
	}
	return $out;
}

$server->add_to_map("import_file", array("struct"), array("struct"));
function import_file($inp) {
	global $server;
	if (!is_array($inp)) {
		return make_err("import_file", "Don't detected Array Input !");
	}
	if (check_allowing($inp["guid"])) {
		$ret = make_dir_tree(base64_decode($inp["fileurl"]));
		if ($ret != "") { return $ret; }
		$out["docroot"] = put_file(base64_decode($inp["fileurl"]), base64_decode($inp["filename"]), $inp["filecontent"]);
	}else{
		$out["err"] = "No Access for this site";
	}
	
	$out["maindir"] = dirname(__FILE__).base64_decode($inp["fileurl"]);
	//$out["guid"] =  base64_decode($inp["guid"]);
	//$out["pagecontent"] = base64_decode($inp["filecontent"]);
	
	foreach($out as $key=>$val) {
		$out[$key] = base64_encode($val);
	}
	return $out;
}

$server->add_to_map("testit", array("struct"), array("struct"));
function test($inp) {
	global $server;
	if (!is_array($inp)) {
		$server->make_fault("Client","Don't detected Array Input !");
		return false;
	}
	$inp["name"] .= "_resp";
	$inp["tag"] .= "_tagresp";
	return $inp;
}

$server->add_to_map("version", array("struct"), array("struct"));
function version($inp) {
	return array("complete"=>"104",
					"major"=>"1",
					"version"=>"0",
					"revision"=>"4");
}

/* 	##################################################################################################### 
   	soaplib.soapinterop.php
   	##################################################################################################### */
$server->add_to_map(
	"echoString",
	array("string"),
	array("string")
);
function echoStraing($inputString){
	if(! $inputString){
		$params = array(
		"faultcode" => "Server",
		"faultstring" => "Empty Input",
		"detail" => "No string detected."
		);
		
		$faultmsg  = new soapmsg("Fault",$params,"http://schemas.xmlsoap.org/soap/envelope/");
		return $faultmsg;
	}
	
	$returnSoapVal = new soapval("return","string",$inputString);
	return $returnSoapVal;
}

$server->add_to_map(
	"echoStringArray",
	array("array"),
	array("array")
);

function echoStringArray($inputStringArray){
	return $inputStringArray;
}

$server->add_to_map(
	"echoInteger",
	array("int"),
	array("int")
);
function echoInteger($inputInteger){
	return $inputInteger;
}

$server->add_to_map(
	"echoIntegerArray",
	array("array"),
	array("array")
);
function echoIntegerArray($inputIntegerArray){
	return $inputIntegerArray;
}

$server->add_to_map(
	"echoFloat",
	array("float"),
	array("float")
);
function echoFloat($inputFloat){
	return $inputFloat;
}

$server->add_to_map(
	"echoFloatArray",
	array("array"),
	array("array")
);
function echoFloatArray($inputFloatArray){
	return $inputFloatArray;
}

$server->add_to_map(
	"echoStruct",
	array("SOAPStruct"),
	array("SOAPStruct")
);
function echoStruct($inputStruct){
	return $inputStruct;
}

$server->add_to_map(
	"echoStructArray",
	array("array"),
	array("array")
);
function echoStructArray($inputStructArray){
	return $inputStructArray;
}

$server->add_to_map(
	"echoVoid",
	array(),
	array()
);
function echoVoid(){
}

$server->add_to_map(
	"echoBase64",
	array("base64Binary"),
	array("base64Binary")
);
function echoBase64($b_encoded){
	return base64_encode(base64_decode($b_encoded));
}

$server->add_to_map(
	"echoDate",
	array("dateTime"),
	array("dateTime")
);
function echoDate($timeInstant){
	return $timeInstant;
}
/* 	##################################################################################################### 
   	soaplib.soapware.php
   	##################################################################################################### */
//include("soaplib.soapware.php");

// meta, for userland validator readout
$server->add_to_map("whichToolkit",array(),array("struct"));
function whichToolkit(){
	return array(
		"toolkitDocsUrl"=>"http://dietrich.ganx4.com/soapx4",
		"toolkitName"=>"SOAPx4",
		"toolkitVersion"=>"0.5",
		"toolkitOperatingSystem"=>"PHP / SUN Solaris"
	);
}

$server->add_to_map("countTheEntities",array("string"),array("struct"));
function countTheEntities($s){
	$arr = count_chars($s,1);
	$ret = array(
		"ctLeftAngleBrackets" => 0,
		"ctRightAngleBrackets" => 0,
		"ctAmpersands" => 0,
		"ctApostrophes" => 0,
		"ctQuotes" => 0);
	foreach($arr as $k => $v){
		//print chr($k)." : $v<br>";
		switch(chr($k)){
			case "<":
				$ret["ctLeftAngleBrackets"] = $v;
				break;
			case ">":
				$ret["ctRightAngleBrackets"] = $v;
				break;
			case "&":
				$ret["ctAmpersands"] = $v;
				break;
			case "'":
				$ret["ctApostrophes"] = $v;
				break;
			case '"':
				$ret["ctQuotes"] = $v;
				break;
		}
	}
	return $ret;
}

$server->add_to_map("easyStructTest",array("SOAPStruct"),array("int"));
function easyStructTest($stooges){
	foreach($stooges as $v){
		$sum += $v;
	}
	return $sum;
}

$server->add_to_map("echoStructTest",array("SOAPStruct"), array("SOAPStruct"));
function echoStructTest($struct){
	return $struct;
}

$server->add_to_map("manyTypesTest",array("int","boolean","string","float","timeInstant","string"),array("array"));
function manyTypesTest($num,$bool,$state,$doub,$dat,$bin){
	return array($num,"boolean"=>$bool,$state,"float"=>$doub,"timeInstant"=>$dat,$bin);
}

//echo "moderatesizearraycheck test: ".moderatesizearraycheck(array("to be ","kitty","or not to be"));
$server->add_to_map("moderateSizeArrayCheck",array("array"),array("string"));
function moderateSizeArrayCheck($arr){
	$a = array_shift($arr);
	$b = array_pop($arr);
	return $a.$b;
}

$server->add_to_map("nestedStructTest",array("SOAPStruct"),array("int"));
function nestedStructTest($struct){
	foreach($struct as $k => $v){
		if($k == "year2000"){
			foreach($v as $a => $b){
				if($a == "month04"){
					foreach($b as $c => $d){
						if($c == "day01"){
							foreach($d as $e){
								$f += $e;
							}
							return $f;
						}
					}
				}
			}
		}
	}
}

$server->add_to_map("simpleStructReturnTest",array("int"),array("SOAPStruct"));
function simpleStructReturnTest($num){
	return array("times10"=>$num*10,"times100"=>$num*100,"times1000"=>$num*1000);
}

if (substr($_SERVER["SERVER_SOFTWARE"],0,8)=="Apache/2") {     
	$tlen = strlen($HTTP_RAW_POST_DATA)/2;     
	$test = substr($HTTP_RAW_POST_DATA, 0, $tlen);
}else{ 
	$test = $HTTP_RAW_POST_DATA;
}

$server->service($test);
?>