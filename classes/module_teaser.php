<?
/*##################### Pagix Content Management System #######################
$Id: module_teaser.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: module_teaser.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:57  skulawik
Updated Versionsinfos

###############################################################################
Module: Teaser
#############################################################################*/
class module_teaser extends modules {
	function module_teaser() {
		$this->modules();
	}

	function parse_module() {
 		return "";
	}


	function parse_page() {
		global $_PHPLIB;
		$pg = new page($this->pid);
		$tid = $pg->tid();
		$tpl = new cmstemplate($tid);
		$t = new Template($_PHPLIB["sites_dir"].$this->site->id.$_PHPLIB["dir_templates"]);
		if ($tpl->filename()!="") {
			$t->set_file("page",$tpl->filename());
			$t->set_block("page", "teaser", "ttl");

			$zeilenarray = $this->getValFromPagesArray();
			// SORTIERUNGEN HERAUSFINDEN ES GIBT NUR ENTWEDER ODER ODER quasi...
			if ($this->getPageVariable("datesort") == "1" OR $this->getPageVariable("datumsort") == "1") {
				usort($zeilenarray, array($this,"cmp_date"));
    		} else{
				$art = $this->getTemplateElementlistArray($tid);
				while (list($k,$v) = each($art)) {
    				if ($this->getPageVariable($v."sort") == "1") {
						$sortvars[] = $v;
        				break;
					}
				}
				$zeilenarray = $this->arfsort($zeilenarray, $sortvars);
			}

			while (list($k,$v) = each($zeilenarray)) {
				while (list($ke,$ve) = each($v)) {
					if ($this->getPageVariable($ke."link") == "1") {
						//echo $v["key"];
						$pg = new page($v["key"]);
						$lnk = $pg->url();
						$t->set_var($ke,'<A HREF="'.$lnk.'">'.$ve.'</A>');
					}else{
						$t->set_var($ke,$ve);
      			}
       		}
				$t->parse("ttl", "teaser", true);
			}

			$t->parse("out", "page");
			return $t->get("out");
   	}else{;
		 	return "";
		}
	}
	// (AR)ray (F)ield Sort.
	// $fl Field list (in order of importance)
	function arfsort($a, $fl) {
		$GLOBALS['__ARFSORT_LIST__'] = $fl;
		usort($a, array($this, 'arfsort_func'));
		return $a;
	}
	// Interne Sortierung
	function arfsort_func($a, $b) {
		foreach($GLOBALS['__ARFSORT_LIST__'] as $f) {
			$strc = strcmp($a[$f], $b[$f]);
			if ( $strc != 0 ) {
				return $strc;
			}
		}
		return 0;
	}

	function cmp_date ($a, $b) {
		if ($a["datum"]!="" OR $b["datum"]!="") {
			$date = "datum";
		}else{
			$date = "date";
		}
		$a_r = $this->rotate_date($a[$date]);
		$b_r = $this->rotate_date($b[$date]);
	//	return strcmp($a_r, $b_r);
		return ($a_r > $b_r) ? -1 : 1;
	}

	function rotate_date($a_date) {
		$a_y = strrchr($a_date, '.');
		$a_y = right($a_y, strlen($a_y) -1 );
		$a_y = right("200".$a_y, 4);
		$a_d = left($a_date, strpos($a_date, '.'));
		$a_d = right("0".$a_d, 2);
		$a_m = left(strstr($a_date, '.'), strlen(strstr($a_date, '.')) - strlen($a_y) -1 );
		$a_m = right($a_m, strlen($a_m) - 1);
		$a_m = right("0".$a_m, 2);
		return $a_y.$a_m.$a_d;
	}

	function getValFromPagesArray() {
	 	$pg = new page($this->pid);
		$parentid = $pg->parentid();
		$tid = $pg->tid();
		$dbb = new DB_CMS;
		$tplarr = $this->getTemplateElementlistArray($tid);
		$dbb->query(sprintf("SELECT id, tid FROM page WHERE parentid = %s AND id != %s", $parentid, $this->pid));

		$zeile = 0;
		while ($dbb->next_record()) {
			//$pg = new page($dbb->f("id"));
			$tpl = new cmstemplate($dbb->f("tid"));
			$db = $tpl->get_all_element_lists();

			$subarr = "";
			while ($db->next_record()) {
				$el = new elements_list($db->f("id"), $dbb->f("tid"));
				// GIBT ES DIESE ELEMENTLISTE IN DEM TEASERTEMPLATE ?
				if ($tplarr[$el->elname()]!="") {
					//eval('$'.$el->elname().'[] = "'.$el->paint($dbb->f("id"), true).'";');
					$subarr[$el->elname()] = $el->paint($dbb->f("id"), true);
     			}
			}
			$subarr["key"] = $dbb->f("id");
			$zeilenarray[$zeile] = $subarr;
			$zeile = $zeile + 1;
		}
		//echo $parentid;die;
		return $zeilenarray;
	}

	function getTemplateElementlistArray($tid) {
		$this->db->query(sprintf("SELECT * FROM template_elements_list WHERE tid = %s", $tid));
		while ($this->db->next_record()) {
			$arr[$this->db->f("elplaceholder")] = $this->db->f("elplaceholder");
   	}
		return $arr;
	}

 	function admin_panel_page($id, $parentid) {
		//echo "id:$id, parentid:$parentid<br>";
		$pg = new page($id);
		if ($_REQUEST["Submit"]=="Submit") {
			$this->setPageName($_GET["pname"]);
			$this->setPageURL($_GET["purl"]);
			$pg->tid($_GET["tid"]);
   	}

		$t = new Template("templates/");
		$t->set_file("page","admin_module_teaser_page_change.html");
		$t->set_var(array("action"=>"page",
								"pid"=>$id,
								"parentid"=>$parentid,
								"post"=>$PHP_SELF,
								"sessid"=>fu(),
								"pname"=>$this->getPageName(),
								"purl"=>$this->getPageURL(),
								"lnk_preview"=>u("publish.php?action=preview&pid=".$this->pid)
								));

		$t->set_block("page", "list", "tlist");
		$this->db->query(sprintf("SELECT id, tname FROM template WHERE sid = %s", $this->site->id));
		$tid = $pg->tid();
		// TEMPLATES AUFZÄHLEN
		while ($this->db->next_record()){
			if ($tid == $this->db->f("id")) {
				$sel = "SELECTED";
			}else{
				$sel = "";
			}
			$t->set_var(array("tname"=>$this->db->f("tname"),
									"id"=>$this->db->f("id"),
									"selected"=>$sel));
			$t->parse("tlist", "list", true);
		}

		// Elementlisten aufzählen
		$t->set_block("page", "teaser", "ttl");
		$arr = $this->getTemplateElementlistArray($tid);
  		while (list($k,$v) = each($arr)) {
			if ($_REQUEST["Submit"]=="Submit") {
   			// Setzen der Variablen
				$this->setPageVariable($v."link", $_REQUEST[$v."link"]);
				$this->setPageVariable($v."sort", $_REQUEST[$v."sort"]);
			}
			if ($this->getPageVariable($v."sort") == "1") {$sort="CHECKED";}else{$sort="";}
			if ($this->getPageVariable($v."link") == "1") {$link="CHECKED";}else{$link="";}

			$t->set_var(array("elementname"=>$v,
									"elementsort"=>$v."sort",
									"elementlink"=>$v."link",
									"elementsort_value"=>$sort,
									"elementlink_value"=>$link
								));
			$t->parse("ttl", "teaser", true);
		}

		$t->parse("out", "page");
		$t->p("out");
	}

	function admin_panel_module() {
		echo "Message from: <b>Module-Administration: <i>admin_panel_module</b></i><br><br>";
  		echo "This Module is currently wrong configured or still in development.<br><br>";
		echo "For questions send a mail to: <a href='mailto:sascha@kulawik.de'>sascha@kulawik.de</a>";
	}


}
?>