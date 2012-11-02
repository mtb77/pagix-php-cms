<?
/*##################### Pagix Content Management System #######################
$Id: class.picture.php 23 2002-10-26 14:32:40Z skulawik $ 
$Revision: 1.1 $
$Author: skulawik $
$Date: 2002-10-26 16:32:40 +0200 (Sat, 26 Oct 2002) $
###############################################################################
$Log: class.picture.php,v $
Revision 1.1  2002/10/26 14:23:39  skulawik
*** empty log message ***

Revision 2.1  2002/04/12 12:33:55  skulawik
Updated Versionsinfos

###############################################################################
Klasse picture_edit
#############################################################################*/
class picture_edit {
	var $md;
	var $pic;
	var $picfle;

	function picture_edit($typ, $file) {
		$this->md = new media();
		$this->pic = new picture($typ);
		$this->picfle = $this->md->dirparser($file, 1);
		$this->pic->open($this->picfle);
  }

  function resizetomax($w,$h) {
		global $_PHPLIB;
		// Der Begriff ist vielleicht nicht ganz richtig. Wenn ein Bild etwas zu klein ist,
		// wird es natürlich NICHT resized, sondern nur durchgeschleust. Sonst wird versucht,
		// so wenig wie möglich am Bild zurechtzuschnippeln.
   	$wo=$this->pic->width();
		if ($w<$wo) {
			$this->pic->resize($h,$w, 1);
   	}
		$wo=$this->pic->width();
		$ho=$this->pic->height();
		if ($h<$ho) {
			$this->pic->resize($h,$wo, 1);
		}
   }

	function defwidth($w,$h) {
			$wo=$this->pic->width();
			$ho=$this->pic->height();

			$this->pic->resize(9999,$w, 1);
			$nowheight=$this->pic->height();
			if ($nowheight>$h) {
				$diff=($nowheight-$h)/2;
				$this->pic->cut(0,$diff,$w,$h);
			}
   }

	function defheight($w,$h) {
			$wo=$this->pic->width();
			$ho=$this->pic->height();

			$this->pic->resize($h,9999, 1);
			$nowwidth=$this->pic->width();
			if ($nowwidth>$w) {
				$diff=($nowwidth-$w)/2;
				$this->pic->cut($diff,0,$w,$h);
			}
   }

	function cuttomiddle($w,$h) {
			$wo=$this->pic->width();
			$ho=$this->pic->height();
			//ist breite oder höhe prozentual grösser als die zielmenge ?
			$hprozent = $h/$ho;
			$wprozent = $w/$wo;
			if($hprozent<$wprozent) {
				// Nach w resizen
				$this->pic->resize(9999,$w, 1);
				$nowheight=$this->pic->height();
				$diff=($nowheight-$h)/2;
				$this->pic->cut(0,$diff,$w,$h);
			}else{
			  	$this->pic->resize($h,9999, 1);
				$nowwidth=$this->pic->width();
				$diff=($nowwidth-$w)/2;
				$this->pic->cut($diff,0,$w,$h);
			}
   }

	function save($filename) {
		$this->pic->save($filename);
	}
}

class picture
	{
		var $err;
		var $site;
		var $md;
		var $im;
		var $typ;
		var $col_white;
		var $col_black;

		function picture($typ, $im = "") {
			global $auth, $err, $site;//, $md;
			// CONSTRUCTOR
			$this->typ = strtolower($typ);
			if ($im != "") {$this->im = $im;}
			//$this->md = $md;
		}
		
		function height() {
			return ImageSY($this->im);
		}
		
		function width() {
			return ImageSX($this->im);
		}
		
		function set_colors() {
//			$this->col_black = 0;
			$this->col_black = ImageColorAllocate ($this->im, 0, 0, 0);
			$this->col_white = ImageColorAllocate ($this->im, 255, 255, 255);
		}
		
		function paint() {
			Header("content-type image/jpeg");
//			ImagePNG($this->im);
			ImageJPEG($this->im);
			ImageDestroy($this->im);
		}
		
		function destroy() {
			ImageDestroy($this->im);
		}
		
		function save($filename) {
			ImageJPEG($this->im, $filename);
			ImageDestroy($this->im);
		}

		function resize($y, $x, $poly=1) {
			$created = false;
			$imgwidth = ImageSX($this->im);
			$imgheight = ImageSY($this->im);
			if ($poly == 1) {
				$maxHeight = $y;
				$maxWidth = $x;
				// Für prozentuale Berechnung
				/*if ($intPercentalSize > 0) {
	        		$intWidth = ($imgwidth / 100) * $intPercentalSize;
	        		$intHeight = ($imgheight / 100) * $intPercentalSize;
	    		}*/
		    	if ($maxHeight > 0) {
		        	$intWidth = $maxWidth;
		        	$intHeight = ($imgheight * (($maxWidth / $imgwidth) * 100)) / 100;
		        	if ($intHeight > $maxHeight) {
		            	$intHeight = $maxHeight;
		            	$intWidth = ($imgwidth * (($maxHeight / $imgheight) * 100)) / 100;
		    		}
				}
		    	if ((($intWidth != $imgwidth) Or ($intHeight != $imgheight)) 
					And $intWidth > 0 And $intHeight > 0) {
					$created = true;
		        	$im = ImageCreate($intWidth, $intHeight);
					imagecopyresized($im, $this->im, 0, 0, 0, 0, $intWidth, $intHeight, $imgwidth, $imgheight);
	        	}
			}
			if (!$created) {
				$im = ImageCreate($x, $y);
				imagecopyresized($im, $this->im, 0, 0, 0, 0, $x, $y, $imgwidth, $imgheight);
			}
			// imagecopyresized imagecopyresampled
			$this->im = $im;
		}
		
		function border($x, $y, $w, $h) {
			$this->set_colors();
			imagerectangle($this->im, $x, $y, $w, $h, $this->col_black);
			imagerectangle($this->im, $x-1, $y-1, $w+1, $h+1, $this->col_white);
		}
		
		function cut($x, $y, $w, $h) {
			$im = ImageCreate($w, $h);
			ImageCopy($im, $this->im, 0, 0, $x, $y, $w, $h);
			$this->im = $im;
		}

		function open($file) {
			// global $file, $typ
			switch($this->typ) {
				case "jpg":
				case "jpeg":
					$this->im = ImageCreateFromJPEG($file);
					break;
				case "png":
					$this->im = ImageCreateFromPNG($file);
					break;
				case "gif":
					$this->im = ImageCreateFromGIF($file);
					break;
				default: 
					die;
			}
		}
	}
?>