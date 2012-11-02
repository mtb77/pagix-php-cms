<?
/*##################### Pagix Content Management System #######################
$Id: pic.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: pic.php,v $
Revision 1.1  2002/10/26 14:21:53  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:48:32  skulawik
Versionsinfos eingetragen

###############################################################################
PIC Anzeigen
#############################################################################*/
	require("config/prepend.php");
	page_open(array("sess" => "CMS_Session",
					"auth" => "CMS_Auth"));
	$auth->login_if(isset($force_relogin));

	$err = new error($debug);	// Errorobject öffnen, Übergabe ist Debuginfo.
	$site = new site($auth->auth["sid"]);

	$pe = new picture_edit($_REQUEST["typ"], $_REQUEST["file"]);

	switch($action) {
		case "resizetomax":
			$pe->resizetomax($_REQUEST["width"],$_REQUEST["height"]);
			$picfle = $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_elmedia"].$_REQUEST["newfilename"];
			break;
		case "defwidth":
			$pe->defwidth($_REQUEST["width"],$_REQUEST["height"]);
			$picfle = $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_elmedia"].$_REQUEST["newfilename"];
			break;
   	case "defheight":
			$pe->defheight($_REQUEST["width"],$_REQUEST["height"]);
			$picfle = $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_elmedia"].$_REQUEST["newfilename"];
			break;
   	case "cuttomiddle":
			$pe->cuttomiddle($_REQUEST["width"],$_REQUEST["height"]);
			$picfle = $_PHPLIB["sites_dir"].$auth->auth["sid"].$_PHPLIB["dir_elmedia"].$_REQUEST["newfilename"];
			break;
		case "resize":
			$pe->pic->resize(240,320, 1);
			break;
		case "border":
			$pict = new picture($typ);
			$picfle = $pe->md->dirparser($_REQUEST["file"], 1);
			$pict->open($picfle);
			$wd=$pict->width();
			$hd=$pict->height();
			$pict->destroy();

			$pe->pic->resize(240,320, 1);
			$pwd=$pe->pic->width();
			$phd=$pe->pic->height();

			//Auf die Originalgrösse den Ausschnitt berechnen
			$exs=($xs/$wd)*$pwd;
			$eys=($ys/$hd)*$phd;
			$exe=($xe/$wd)*$pwd;
			$eye=($ye/$hd)*$phd;
			$pe->pic->border($exs,$eys,$exe,$eye);
			//$pe->pic->border($xs,$ys,$xe,$ye);
			break;
		case "show":
			//RESIZEGRÖSSE RAUSFINDEN !!!!
			//$pict = new picture($typ);
			$picfle = $pe->md->dirparser($_REQUEST["file"], 1);
			//$pict->open($picfle);
			//$pict->resize(240,320, 1);
			//$pwd=$pict->width();
			//$phd=$pict->height();
			//$pict->destroy();
			//ORIGINALHÖHE UND BREITE !!!!!!!!!!!!!!!!!!!!!
			//$wd=$pe->pic->width();
			//$hd=$pe->pic->height();
			//Auf die Originalgrösse den Ausschnitt berechnen
			//$exs=($xs/$pwd)*$wd;
			//$eys=($ys/$phd)*$hd;
			//$exe=(($xe-$xs)/$pwd)*$wd;
			//$eye=(($ye-$ys)/$phd)*$hd;
			//...Zurechtschneiden
			//$pe->pic->cut($exs,$eys,$exe,$eye);
			$pe->pic->cut($xs,$ys,$xe-$xs,$ye-$ys);
			//...und resizen !
			$pe->pic->resize($height,$width, 1);
			break;
		default:
			// Original size !
			break;
	}

	if ($action2=="save") {
		$pe->pic->save($picfle);
		echo "Datei gespeichert.";
	}else{
		$pe->pic->paint();
	}

	page_close();
?>
