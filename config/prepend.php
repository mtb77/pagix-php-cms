<?php
$_PHPLIB = array();
/* 	#####################################################################################################
   	v1.0 pmscms
   									PHP Multi Site Content Management System
									
   	by Sascha-Matthias Kulawik in 2001
   	##################################################################################################### */
   
/* 	##################################################################################################### 
   	Konfigurationsvariablen
   	##################################################################################################### */
	$debug = false;
	// Rootverzeichnis des kompletten CMS Baums
	$_PHPLIB["cmsbaseurl"] = "http://cms.helios..net/";
	$_PHPLIB["cmsroot"] = "/var/www/helios/cms/"; 	
	$_PHPLIB["sites_dir"] = "/var/www/helios/cms_upload/";	// Hauptverzeichnis, in dem die Sites angelegt werden
	$_PHPLIB["element_templates_dir"] = $_PHPLIB["cmsroot"]."element_templates/";
														// Element Templates sind Vorlagen für das Erstellen von 
														// Elementen, die bei neu angelegten Sites mitkopiert werden
	
	// Folgende Verzeichnisse werden pro Site im "{sites_dir}/{site_id}/ angelegt:
	$_PHPLIB["dir_templates"] = "/templates/";			// Seitentemplates (werden indiziert, NICHT GEPUBLISHED !)
	$_PHPLIB["dir_elements"] = "/element/";				// Templates für die Elemente (AUCH KEIN PUBLISHING !)
	$_PHPLIB["dir_media"] = "/media/";					// Medienverzeichnis für Medialibrary (wird in $DOCROOT/media gepublished)
	$_PHPLIB["dir_site"] = "/site/";					// Document Root der Site, alle Dateien, die nicht ins CMS
														// gehöhren und gepublished werden sollen (Frameset, Layoutimages etc.)			
	$_PHPLIB["dir_elmedia"] = "/elmedia/";					// Verzeichnis für alle Element-Medias
	// Datenbank Zugriff
	$_PHPLIB["db_host"]			= "localhost";		// CMS Datenbank
	$_PHPLIB["db_database"]		= "cms";
	$_PHPLIB["db_user"]			= "cms";
	$_PHPLIB["db_password"]		= "cms";
	
/* 	##################################################################################################### 
   	Config Programmcode
   	##################################################################################################### */
	$_PHPLIB["libdir"] = $_PHPLIB["cmsroot"]."/inc/";
	require($_PHPLIB["cmsroot"] . "config/functions.php");
	require($_PHPLIB["libdir"] .  "db_mysql.inc");  		/* Change this to match your database. */
	require($_PHPLIB["libdir"] .  "ct_sql.inc");   /* Change this to match your data storage container */
	require($_PHPLIB["libdir"] .  "session.inc");   		/* Required for everything below.      */
	require($_PHPLIB["libdir"] .  "auth.inc");      		/* Disable this, if you are not using authentication. */
	require($_PHPLIB["libdir"] .  "user.inc");      		/* Disable this, if you are not using per-user variables. */
	require($_PHPLIB["libdir"] .  "template.inc");
	require($_PHPLIB["libdir"] .  "page.inc");      		/* Required, contains the page management functions. */
	require($_PHPLIB["libdir"] .  "tree.inc");
	require($_PHPLIB["cmsroot"] . "config/local.inc");
	require($_PHPLIB["cmsroot"] . "classes/class.elements.php"); /*Redeclare because of using this Class in specific Order*/

	// Internal Classes Included here
	$d = dir($_PHPLIB["cmsroot"] . "classes/");
	while($entry=$d->read()) {
		if (substr($entry,0,1)!="." AND substr($entry,0,3)!="CVS") {
			$evall .= 'require_once($_PHPLIB["cmsroot"] . "classes/'.$entry.'");'."\n";
		}
	}
	eval ($evall);
	
	$db = new DB_CMS;
	$err = new error;
/* 	##################################################################################################### */
?>
