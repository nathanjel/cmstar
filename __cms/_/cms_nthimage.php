<?php

    class cms_nthimage { 

        private $image;

        private $image_type;
        private $image_info;

        public function local_newimage($nWidth, $nHeight) {
            $newImg = imagecreatetruecolor($nWidth, $nHeight);
            $imgInfo = $this->image_info;
            if(($imgInfo[2] == 1) OR ($imgInfo[2] == 3)){
                imagealphablending($newImg, false);
                imagesavealpha($newImg,true);
                $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
            }
            return $newImg;
        }

        public function load($filename) {
            $image_info = getimagesize($filename);
            $this->image_type = $image_info[2];
            $this->image_info = $image_info;
            if( $this->image_type == IMAGETYPE_JPEG ) {
                $this->image = imagecreatefromjpeg($filename);
            } elseif( $this->image_type == IMAGETYPE_GIF ) {
                $this->image = imagecreatefromgif($filename);
            } elseif( $this->image_type == IMAGETYPE_PNG ) {
                $this->image = imagecreatefrompng($filename);
            } else {
                return false;
            }
            return true;
        }

        public function save($filename, $image_type=-128, $compression=-1, $permissions=null) {            
            if( $image_type == -128) {
                $image_type = $this->image_type;
            }
            if( $image_type == IMAGETYPE_JPEG ) {
                if ($compression == -1)
                    $compression = cms_config::$cc_default_jpg_quality;
                imagejpeg($this->image,$filename,$compression);
            } elseif( $image_type == IMAGETYPE_GIF ) {
                imagegif($this->image,$filename);         
            } elseif( $image_type == IMAGETYPE_PNG ) {
                imagepng($this->image,$filename);
            }   

            if( $permissions != null) {
                chmod($filename,$permissions);
            }
        }

        public function output($image_type=IMAGETYPE_JPEG, $compression=-1) {
            if( $image_type == IMAGETYPE_JPEG ) {
                if ($compression == -1)
                    $compression = cms_config::$cc_default_jpg_quality;
                imagejpeg($this->image, NULL, $compression);
            } elseif( $image_type == IMAGETYPE_GIF ) {
                imagegif($this->image);         
            } elseif( $image_type == IMAGETYPE_PNG ) {
                imagepng($this->image);
            }   
        }

        public function getWidth() {
            return imagesx($this->image);
        }

        public function getHeight() {
            return imagesy($this->image);
        }

        public function scale($scale) {
            $width = round($this->getWidth() * $scale/100);
            $height = round($this->getHeight() * $scale/100); 
            $this->resize($width,$height);
        }

        public function resize($width,$height) {
            $new_image = $this->local_newimage($width, $height);
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
            $this->image = $new_image;   
        }

        public function resizeProportionalToFit($width, $height, $reversescale = false) {
            $w = $this->getWidth();
            $h = $this->getHeight();
            // try w
            $ratio = $width / $w;
            $newh = $h * $ratio;
            if (($newh < $height) xor ($reversescale)) {
                $this->scale($ratio*100);
            } else {
                $this->scale(($height / $h) * 100);
            }
        }

        public function resizeProportionalAndClip($nwidth, $nheight) {
            $this->resizeProportionalToFit($nwidth, $nheight, true);
            $rw = $this->getWidth();
            $rh = $this->getHeight();
            $new_image = $this->local_newimage($nwidth, $nheight);
            if ($rw > $nwidth) {
                imagecopy($new_image, $this->image, 0, 0, (($rw - $nwidth)/2), 0, $nwidth, $nheight);
            } elseif ($rh > $nheight) {
                imagecopy($new_image, $this->image, 0, 0, 0, (($rh - $nheight)/2), $nwidth, $nheight);
            } else {
                // wszystko gra ? :-)
                imagecopy($new_image, $this->image, 0, 0, 0, 0, $nwidth, $nheight);
            }
            $this->image = $new_image; 
        }

        public function filter($filters) {
            // filters apply in order given
            $filters = str_split($filters);
            foreach($filters as $filter) {
                switch($filter) {
                    case 'N': imagefilter($this->image, IMG_FILTER_NEGATE); break;
                    case 'c': imagefilter($this->image, IMG_FILTER_CONTRAST, 10); break;
                    case 'C': imagefilter($this->image, IMG_FILTER_CONTRAST, -10); break;
                    case 'b': imagefilter($this->image, IMG_FILTER_BRIGHTNESS, -10); break;
                    case 'B': imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 10); break;
                    case 'G': imagefilter($this->image, IMG_FILTER_GRAYSCALE); break;
                }
            }
        }
        
        public function watermark($nthimage, $opacity = 100, $location = "RB", $pxmargin = 10) {
            if (!($nthimage instanceof cms_nthimage))
                throw new InvalidArgumentException("invalid image provided to watermark function, non an nthimage");
            $sw = imagesx($nthimage->image);
            $sh = imagesy($nthimage->image);
            $iw = imagesx($this->image);
            $ih = imagesy($this->image);
            $lds = str_split($location);
            if ($opacity<0 || $opacity>100 || !is_numeric($opacity)) {
                $opacity = 100;
            }
            if (!is_numeric($pxmargin))
                $pxmargin = 0;
            switch($lds[0]) {
                case "L":
                    $dx = $pxmargin;
                    break;
                case "M":
                    $dx = round(($iw-$sw)/2);
                    break;
                case "R":
                default:
                    $dx = $iw-$sw-$pxmargin;
            }
            switch($lds[1]) {
                case "T":
                    $dy = $pxmargin;
                    break;
                case "M":
                    $dy = round(($ih-$sh)/2);
                    break;
                case "B":
                default:
                    $dy = $ih-$sh-$pxmargin;
            }
            imagecopymerge($this->image, $nthimage->image, $dx, $dy, 0, 0, $sw, $sh, $opacity);
        }
        
        function __destruct() {
            @imagedestroy($this->image);
        }
        
    }

?>