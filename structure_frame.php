<?
require("config/prepend.php");
page_open(array("sess" => "CMS_Session",
				"auth" => "CMS_Auth"));
//if (($HTTP_REFERER=="") or (!isset($HTTP_REFERER))) {$force_relogin ="yes";}
$auth->login_if(isset($force_relogin));
?>
<html>
<head>
<title>Structure</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<frameset cols="250,*" frameborder="NO" border="0" framespacing="0" rows="*"> 
 	
  <frame name="tree"  noresize scrolling="YES" src="<?=u("admin.php?action2=struct");?>">
	
  <frame name="main" src="<?=u("admin.php");?>" noresize scrolling="AUTO">
</frameset>
<noframes>
	<body bgcolor="#FFFFFF" text="#000000">
		noframes?
	</body>
</noframes>
</html>
<?
page_close();
?>
