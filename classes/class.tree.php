<?
/*##################### Pagix Content Management System #######################
$Id: class.tree.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.tree.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:56  skulawik
Updated Versionsinfos

###############################################################################
Klassen zum Directory Tree
#############################################################################*/
class dir_Tree extends Tree {
     var $classname = "dir_Tree";
     var $delimiter="|";
     var $sid;
	 var $db;
	 var $startmsg;
	 
	 var $imgRoot		='<img src="images/baum_struktur/globus_2.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgStamm		='<img src="images/baum_struktur/baum_zweig_1.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgOrdner		='<img src="images/baum_struktur/ordner_geschl.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgOrdnerOffen='<img src="images/baum_struktur/ordner_offen.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 
	 var $imgFile		='<img src="images/baum_struktur/file_html.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 // Viewable File Formats
	 var $imgFile_jpg	='<img src="images/baum_struktur/file_jpg.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_gif 	='<img src="images/baum_struktur/file_gif.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_png 	='<img src="images/baum_struktur/file_png.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 // Editable File Formats
	 var $imgFile_htm 	='<img src="images/baum_struktur/file_html.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_html 	='<img src="images/baum_struktur/file_html.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_php 	='<img src="images/baum_struktur/file_php.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_php3 	='<img src="images/baum_struktur/file_php3.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 // Other File Formats
	 var $imgFile_doc 	='<img src="images/baum_struktur/file_doc.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_pdf 	='<img src="images/baum_struktur/file_pdf.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_ppt 	='<img src="images/baum_struktur/file_ppt.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_pps 	='<img src="images/baum_struktur/file_ppt.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_xls 	='<img src="images/baum_struktur/file_xls.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';	 
	 var $imgFile_rar 	='<img src="images/baum_struktur/file_rar.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_zip 	='<img src="images/baum_struktur/file_zip.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_gz 	='<img src="images/baum_struktur/file_zip.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_avi 	='<img src="images/baum_struktur/file_avi.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_rm 	='<img src="images/baum_struktur/file_rm.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_ram 	='<img src="images/baum_struktur/file_ram.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_mpg 	='<img src="images/baum_struktur/file_mpg.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_mov 	='<img src="images/baum_struktur/file_mov.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_qt 	='<img src="images/baum_struktur/file_qt.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_swf 	='<img src="images/baum_struktur/file_swf.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_mp3 	='<img src="images/baum_struktur/file_mp3.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_wmv 	='<img src="images/baum_struktur/file_wmv.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_wma 	='<img src="images/baum_struktur/file_wma.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_asf 	='<img src="images/baum_struktur/file_asf.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_exe	='<img src="images/baum_struktur/file_exe.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgFile_msi	='<img src="images/baum_struktur/file_msi.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 
	 var $imgEnde		='<img src="images/baum_struktur/baum_zweig_2.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgEnde_plus	='<img src="images/baum_struktur/baum_plus_ende.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgEnde_minus	='<img src="images/baum_struktur/baum_minus_ende.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgZweig		='<img src="images/baum_struktur/baum_zweig_3.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgZweig_plus	='<img src="images/baum_struktur/baum_plus.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgZweig_minus='<img src="images/baum_struktur/baum_minus.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	 var $imgLeer		='<img src="images/baum_struktur/baum_leer.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	// var $imgOpen		='<img src="images/open_2.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	// var $imgDelete		='<img src="images/btn_delete_s_2.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	// var $imgNewFile	='<img src="images/baum_struktur/neue_seite.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';
	// var $imgNewFolder	='<img src="images/baum_struktur/ordner_geschl.gif" border="0" WIDTH="20" HEIGHT="20" ALIGN="TOP">';

	 var $pretxt 		='<font face="Arial, Helvetica, sans-serif" size="1">';
	 var $pretxt2 		='<font face="Arial, Helvetica, sans-serif" size="1">';
	 var $endtxt 		='</font>';
	 
	 var $tbl_sta 		='<tr><td width="250" height="20">';
	 var $tbl_sta_sel	='<tr><td width="250" height="20" bgcolor="#FDE8CF">'; //background="images/hg_grau.gif"
	 
	 var $tbl_row 		='</td><td width="22" height="16">';
	 var $tbl_end 		='</td></tr>';
	 
	 var $lnk_folder 	='<A href="#" onclick="parent.location.href=\'%s\'">%s</A>';
	 var $lnk 			='<A href="#" onclick="parent.location.href=\'%s\'; parent.parent.main.location.href=\'%s\'">%s</A>';
	 
	 var $array_add;
	 
	 var $last_url="";
	 var $next_url="";
	 var $set_last_url=true;
	 var $set_next_url=false;
	 
	 function starttree () {
		global $PHP_SELF, $sess, $curr;
		if ($curr == "0" or $curr=="") {
			$tblsta = $this->tbl_sta_sel;
		}else{
			$tblsta = $this->tbl_sta;
		}
		$this->outp.=	'<nobr><table border="0" cellspacing="0" cellpadding="0" width="100%">'.
						$tblsta.
						sprintf($this->lnk,u($PHP_SELF."?action2=struct&curr=0"), u($PHP_SELF),$this->imgRoot.$this->pretxt2.'&nbsp;'.$this->startmsg.$this->endtxt).
/*						$this->tbl_row.
						$this->pretxt.'&nbsp;'.$this->endtxt.
						$this->tbl_row.
						sprintf($this->lnk,"#",$PHP_SELF."?action=page&id=0&parentid=0", $this->imgNewFile).
						$this->tbl_row.
						sprintf($this->lnk,"#",$PHP_SELF."?action=page&action2=folder&id=0&parentid=0", $this->imgNewFolder).*/
						$this->tbl_end;
		$this->flag=true;
	}
	
	function echotree($tr, $l = 0) {
		foreach($tr as $k=>$v) {
			for ($i = 0; $i<$l; ++$i) {echo "--";}
			echo "KEY: $k VALUE : $v<br>";
			if (is_array($v)) {
				$this->echotree($v, ++$l);
			} 
		}
	}
		
	function textlink($link, $text, $level = 0, $ownlink="#") {
		$cutoff = 27 - ($level * 4);
		if ($cutoff < 6) {$cutoff = 6;}
		if (strlen($text) > $cutoff) {
			$text = substr($text, 0, $cutoff)."...";
		}
		return sprintf($this->lnk,$ownlink,$link,$this->pretxt.$text.$this->endtxt);
	}
	
	function growtree ($key,$value,$path,$depth,$count,$pcount) {
		global $curr;
		if ($key == $curr) {
			$this->outp.= $this->tbl_sta_sel . join($this->prfx,"");		
		}else{
			$this->outp.= $this->tbl_sta . join($this->prfx,"");		
		}
		if ($count==$pcount) {
			$fld=$this->imgEnde_minus;
		} else {
			$fld=$this->imgZweig_minus;
		}
		$this->outp.= sprintf($this->lnk_folder,
								  $this->array_add[$key]["lnk_popup"]."&close=".$this->array_add[$key]["id"], 
								  $fld.$this->imgOrdnerOffen);
								   
		$this->outp.= $this->textlink($this->array_add[$key]["lnk_edit"], $this->array_add[$key]["name"],
								0,$this->array_add[$key]["lnk_popup"]).
					/*$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete).
					$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_file"], $this->imgNewFile).
					$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_folder"], $this->imgNewFolder).*/
					$this->tbl_end;
			 
		if ($count > $pcount) {
			$this->prfx[$depth]=$this->imgStamm;
		} else {
			$this->prfx[$depth]=$this->imgLeer;
		}
		$this->flag=true;
	}

	function leaftree($key,$value,$path,$depth,$count,$pcount) {
		global $curr;
		if ($key == $curr) {
			$this->outp.= $this->tbl_sta_sel . join($this->prfx,"");		
		}else{
			$this->outp.= $this->tbl_sta . join($this->prfx,"");		
		}
		
		switch($this->array_add[$key]["type"]) {
			case "folder":
				if ($count==$pcount) {
					$fld.=$this->imgEnde_plus;
				} else {
					$fld.=$this->imgZweig_plus;
				}
				$this->outp.= sprintf($this->lnk,
									  $this->array_add[$key]["lnk_popup"], $this->array_add[$key]["lnk_edit"],
									  $fld.$this->imgOrdner.
									  $this->pretxt.$this->array_add[$key]["name"].$this->endtxt).
									  $this->tbl_end;
									  
				/*$this->outp.= sprintf($this->lnk_folder,
									  $this->array_add[$key]["lnk_popup"], 
									  $this->imgOrdner);
				$this->outp.= $this->textlink($this->array_add[$key]["lnk_edit"], $this->array_add[$key]["name"],
									0,$this->array_add[$key]["lnk_popup"]).
									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete).
									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_file"], $this->imgNewFile).
									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_folder"], $this->imgNewFolder).
									$this->tbl_end;*/
				break;
			default:
				if ($count==$pcount) {
					$fld=$this->imgEnde;
				} else {
					$fld=$this->imgZweig;
				}
				$this->outp.= sprintf($this->lnk,
									  $this->array_add[$key]["lnk_popup"], $this->array_add[$key]["lnk_edit"],
									  $fld.$this->imgFile.
									  $this->pretxt.$this->array_add[$key]["name"].$this->endtxt).
									  $this->tbl_end;
/*									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete).
									$this->tbl_row.
									$this->pretxt.'&nbsp;'.$this->endtxt.
									$this->tbl_row.
									$this->pretxt.'&nbsp;'.$this->endtxt.
									$this->tbl_end;*/
				break;
		}
		$this->flag=false;
	}
	
	function shrinktree ($key,$depth) {
		unset($this->prfx[$depth]);
	}

	function endtree () {
		$this->outp.='</table></nobr>';
	}
	
	function build_dir($level, $id) {
		global $auth, $open, $close, $PHP_SELF;
		$dbb = new DB_CMS;
		// Liste erst alle Folder auf
		// Aktuelles Element finden

		$dbb->query(sprintf("SELECT id, fname FROM folder WHERE sid = %s AND parentid = %s",
					$this->sid, $id));
		while($dbb->next_record()) {
			$groupname = $dbb->f("fname");
			$groupid = "dir".$dbb->f("id");
			// Nach geöffneten suchen
			$openarr = explode(",", $open);
			$found = false;
			$open = "";
			// Offene Elemente ausmachen und mit berücksichtigen.
			// Elemente, die gerade per "close" geschlossen werden, werden schon rausgenommen
			foreach ($openarr as $openid) {
				if ($openid == $dbb->f("id")) {
					$found = true;
				}
				if ($close != $openid) {
					if ($open=="") {
						$open .= $openid;
					}else{
						$open .= ",".$openid;
					}
				}
			}
			// Aktives Element zum Öffnen hinzufügen
			if ($open == "") {
				$openakt = $dbb->f("id");
			}else{
				$openakt = $open; 	
				if (!$found) {$openakt.= ",".$dbb->f("id");}
			}
			
			if ($found) {
				if ($close != $dbb->f("id")) {
					$opened = "0";
					$array[$groupid]=$this->build_dir($level+1, $dbb->f("id"));
				}else{
					$opened = "1";
					$array[$groupid]="folder";
				}
			}else{
				$array[$groupid]="folder";
			}
			$this->array_add[$groupid]=array("lnk_delete"=>u($PHP_SELF."?action=site&action2=delete_folder&id=".$dbb->f("id").
															"&pname=".$dbb->f("fname")),
											"lnk_edit"=>u($PHP_SELF."?action=page&action2=folder&id=".$dbb->f("id")),
											"lnk_popup"=>u($PHP_SELF."?action2=struct&open=".$openakt."&curr=".$groupid."&pname=".$dbb->f("fname")),
											"lnk_new_file"=>u($PHP_SELF."?action=page&id=0&parentid=".$dbb->f("id")),
											"lnk_new_folder"=>u($PHP_SELF."?action=page&action2=folder&id=0&parentid=".$dbb->f("id")),
											"type"=>"folder",
											"opened"=>$opened,
											"id"=>$dbb->f("id"),
											"name"=>$dbb->f("fname")
											);
		}
		
		// Listet alle Files in dem aktuellen Ordner auf
		$dbq = new DB_CMS;
		$dbb->query(sprintf("SELECT * FROM page WHERE sid = %s AND parentid = %s", $this->sid, $id));
		while($dbb->next_record()) {
			$groupid = "file".$dbb->f("id");
			$array[$groupid]="file";
			/*// Alle Elementlisten auflisten und als Array zurück geben...
			$dbq->query("SELECT elplaceholder FROM template_elements_list WHERE tid = ".$dbb->f("tid"));
			while($dbq->next_record()) {
				$elarr[$dbq->f("id")] = $dbq->f("elplaceholder");
			}*/
			$this->array_add[$groupid]=array("lnk_delete"=>u($PHP_SELF."?action=site&action2=delete&id=".$dbb->f("id")."&pname=".$dbb->f("pname")),
												"lnk_edit"=>u($PHP_SELF."?action=page&id=".$dbb->f("id")),
												"lnk_popup"=>u($PHP_SELF."?action2=struct&open=".$open."&curr=".$groupid."&pname=".$dbb->f("pname")),
												"type"=>"file",
												"id"=>$dbb->f("id"),
												"name"=>$dbb->f("pname"),
												"opened"=>"0",
												"el"=>$elarr
												);
			unset($elarr);
		}
		return $array;
	}
	
    function build_tree($sid) {
		//global $curr;
		$this->startmsg = "Homepage";
	 	$this->sid=$sid;
        $this->tree=$this->build_dir(0,0);
		$this->outp.='<META name="Author" content="net GmbH, Sascha-Matthias Kulawik">';
    }
}

class media_Tree extends dir_Tree {
	var $pretxt 		='<font face="Arial, Helvetica, sans-serif" size="1">';
	var $filepath;
	
	function starttree () {
		global $PHP_SELF, $sess, $curr;
		if ($curr == "0" or $curr=="") {
			$tblsta = $this->tbl_sta_sel;
		}else{
			$tblsta = $this->tbl_sta;
		}
		$this->outp.=	'<nobr><table border="0" cellspacing="0" cellpadding="0">'.
						$tblsta.
						sprintf($this->lnk,u($PHP_SELF."?action=media&action2=struct"), u($PHP_SELF."?action=media"),$this->imgRoot.$this->pretxt2.$this->startmsg.$this->endtxt).
						/*$this->tbl_row.
						$this->pretxt.'&nbsp;'.$this->endtxt.
						$this->tbl_row.
						sprintf($this->lnk,"#",$PHP_SELF."?action=media&action2=file&dir=/", $this->imgNewFile).
						$this->tbl_row.
						sprintf($this->lnk,"#",$PHP_SELF."?action=media&action2=folder_new&dir=/", $this->imgNewFolder).*/
						$this->tbl_end;
		$this->flag=true;
	}
	
	function growtree ($key,$value,$path,$depth,$count,$pcount) {
		// Geöffneter Folder wird hier angezeigt
		global $curr;
		if ($key == $curr) {
			$this->outp.= $this->tbl_sta_sel . join($this->prfx,"");		
		}else{
			$this->outp.= $this->tbl_sta . join($this->prfx,"");		
		}
		if ($count==$pcount) {
			$fld=$this->imgEnde_minus;
		} else {
			$fld=$this->imgZweig_minus;
		}
		$this->outp.= sprintf($this->lnk_folder,
								  $this->array_add[$key]["lnk_popup"]."&close=".$key, 
								  $fld.$this->imgOrdnerOffen);
								   
		$this->outp.= $this->textlink($this->array_add[$key]["lnk_edit"], $this->array_add[$key]["name"],
									0,$this->array_add[$key]["lnk_popup"]).
					/*$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete).
					$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_file"], $this->imgNewFile).
					$this->tbl_row.
					sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_folder"], $this->imgNewFolder).*/
					$this->tbl_end;
			 
		if ($count > $pcount) {
			$this->prfx[$depth]=$this->imgStamm;
		} else {
			$this->prfx[$depth]=$this->imgLeer;
		}
		$this->flag=true;
	}
	
	function leaftree ($key,$value,$path,$depth,$count,$pcount) {
		// Normaler Strukturbaum
		global $curr;
		if ($key == $curr) {
			$this->outp.= $this->tbl_sta_sel . join($this->prfx,"");		
		}else{
			$this->outp.= $this->tbl_sta . join($this->prfx,"");		
		}
		switch($value) {
			case "folder":
			case "Array":
				if ($count==$pcount) {
					$fld=$this->imgEnde_plus;
				} else {
					$fld=$this->imgZweig_plus;
				}
				$this->outp.= sprintf($this->lnk,
									  $this->array_add[$key]["lnk_popup"], $this->array_add[$key]["lnk_edit"],
									  $fld.$this->imgOrdner.
									  $this->pretxt.$this->array_add[$key]["name"].$this->endtxt).
									  $this->tbl_end;
				//$this->outp.= $this->textlink($this->array_add[$key]["lnk_edit"], $this->array_add[$key]["name"],
					//					0,$this->array_add[$key]["lnk_popup"]).
									/*$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete).
									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_file"], $this->imgNewFile).
									$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_new_folder"], $this->imgNewFolder).*/
				break;
			default: 
				if ($count==$pcount) {
					$this->outp.=$this->imgEnde;
				} else {
					$this->outp.=$this->imgZweig;
				}
				
				eval('$evall = $this->imgFile_'.$value.";");
				if ($evall == "") {
					$this->outp.=$this->imgFile;
				}else{
					$this->outp.= $evall;
				}
				$this->outp.= $this->textlink($this->array_add[$key]["lnk_edit"], $this->array_add[$key]["name"], $depth,
										$this->array_add[$key]["lnk_popup"])//.
									/*$this->tbl_row.
									sprintf($this->lnk,"#",$this->array_add[$key]["lnk_delete"], $this->imgDelete)*/;
				break;
		}
		$this->flag=false;
	}
	
	function build_dir($aktdir, $level = 0, $arrfilter = array("*")) {
		global $open, $close, $PHP_SELF; #hier hattest du $PHP_SELF vergessen
		$dhdl = opendir($aktdir);
		if ($dhdl) {
			while ($file = readdir($dhdl)) {
				if (($file != ".") && ($file != "..")) {
					$groupid = $file.$level;
					if (is_dir($aktdir.$file)) {
						$openarr = explode(",", $open);
						$found = false;
						$open = "";
						// Offene Elemente ausmachen und mit berücksichtigen. 
						// Elemente, die gerade per "close" geschlossen werden, werden schon rausgenommen
						foreach ($openarr as $openid) {
							if ($openid == $groupid) {
								$found = true;
							}
							if ($close != $openid) {
								if ($open=="") {
									$open .= $openid;
								}else{
									$open .= ",".$openid;
								}
							}
						}
						// Aktives Element zum Öffnen hinzufügen
						if ($open == "") {
							$openakt = $groupid;
						}else{
							$openakt = $open; 	
							if (!$found) {$openakt.= ",".$groupid;}
						}
						
						if ($found) {
							if ($close != $groupid) {
								// Muss noch überprüft werden, ob Files unter diesem Verzeichnis existieren...
								$opened = "0";
								unset($subtr);
								$subtr = $this->build_dir($aktdir.$file."/", $level+1, $arrfilter);
								if ($subtr == "") {
									$tree[$groupid] = "folder";
								}else{
									$tree[$groupid] = $subtr;
								}
							}else{
								$opened = "1";
								$tree[$groupid] = "folder";
							}
						}else{
							$tree[$groupid] = "folder";
						}
					} else {
						$ftype = explode(".", $file);
						$fty = strtolower($ftype[count($ftype)-1]);
						
						if (in_array($fty, $arrfilter) or in_array("*", $arrfilter)) {
							$tree[$groupid] = $fty;
						}
					}
					// DIR Variable bestimmen
					$dir = right($aktdir, strlen($aktdir) - (strlen($this->filepath)- 1));
					
					//echo "AA".$this->filepath."DD".$dir;
					if ($tree[$groupid] == "folder" or is_array($tree[$groupid])) {
						$this->array_add[$groupid]=array(
								"lnk_edit"=>u($PHP_SELF."?action=media"),//&action2=folder_edit&dir=".$dir."&name=".$file),
								"lnk_popup"=>u($PHP_SELF."?action=media&action2=struct&open=".$openakt."&curr=$groupid&fty=dir&dir=$dir$file&name=$file"),
								/*"lnk_new_file"=>$PHP_SELF."?action=media&action2=file_new&dir=".$dir.$file."/",
								"lnk_delete"=>$PHP_SELF."?action=media&action2=delete_folder&dir=".$dir.$file."/"."&name=".$file,
								"lnk_new_folder"=>$PHP_SELF."?action=media&action2=folder_new&dir=".$dir.$file."/",	*/
								"name"=>$file,
								"opened"=>$opened
								);
					}else{
						if (in_array($fty, $arrfilter) or in_array("*", $arrfilter)) {
							$this->array_add[$groupid]=array(
								"lnk_edit"=>u($PHP_SELF."?action=media&action2=file_edit&dir=".$dir."&file=".$file),
								"lnk_popup"=>u($PHP_SELF."?action=media&action2=struct&open=".$open."&curr=$groupid&fty=$fty&dir=$dir&file=$file&name=$file"),
								/*"lnk_delete"=>$PHP_SELF."?action=media&action2=delete_file&dir=".$dir."&file=".$file,*/
								"name"=>$file,
								"opened"=>"0"
								);
						}
					}//if
				}//if
			}//while
		}//if
		return $tree;
	}
	
	function build_tree($sid) {
		global $_PHPLIB, $db, $curr;
		$this->db = $db;
		$this->startmsg = "Media Datenbank";
	 	$this->sid=$sid;
		$this->filepath = $_PHPLIB["sites_dir"].$sid.$_PHPLIB["dir_media"];
        $this->tree = $this->build_dir($this->filepath,0); // array("jpg", "png", "gif")
		//$this->echotree($this->tree);
		$this->outp.='<META name="Author" content="net GmbH, Sascha-Matthias Kulawik"';
		return $curr;
    }
}
?>
