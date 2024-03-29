<?php
/*##################### Pagix Content Management System #######################
$Id: class.soap_server.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.soap_server.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
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
#############################################################################*/

// make errors handle properly in windows
error_reporting(2039);

class soap_server {
	
	function soap_server() {
		// create empty dispatch map
		$this->dispatch_map = array();
		$this->debug_flag = true;
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
		$this->debug("request uri: ".$HTTP_SERVER_VARS["REQUEST_URI"]);
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
		$this->fault = true;
	}
}

?>
