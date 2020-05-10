<?php

    define("CMS_EXCEL_NONE", 0x00000000);
    define("CMS_EXCEL_CONVERT_FLOATS_ON_IMPORT", 0x00000001);
    define("CMS_EXCEL_CONVERT_SHORTEN_FLOAT_PREC_TO_MILI", 0x00000002);
    define("CMS_EXCEL_CONVERT_DISPLAY_COMMA_IN_NUMBERS", 0x00000004);
    define("CMS_EXCEL_CONVERT_USE_SHEET_NUMBER", 0x00000008);

    class cms_excel2k7 {
        
        public static function a1_to_rc($val, &$r0, &$r1) {
            $r0 = 0;
            $r1 = 0;
            $ind = 1;
            $indd = 1;
            $e = str_split($val);
            $f = count($e)-1;
            for($i = $f; $i>=0; --$i) {
                $ordc = ord($e[$i]);
                if ($ordc<65) { // 0-9
                    $r0 += ($ordc - 48) * $indd;
                    $indd *= 10;
                } else {            // A...Z , A = 1, 
                    $r1 += ($ordc - 64) * $ind;
                    $ind *= 26;
                }
            }
            // transpose 1x1 to 0x0 base address
            $r0--; $r1--;
        }
        
        public static function get_zip_entry_xml($zip, $res) {
            zip_entry_open($zip, $res, "r");
            $entry = zip_entry_read($res, zip_entry_filesize($res));
            zip_entry_close($res);
            $xdoc = new DOMDocument();    
            $xdoc->loadXML($entry, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING);
            return $xdoc;
        }

        public static function read_sheet_data($path, $shtname, $flags) {

            $docf = ($flags && CMS_EXCEL_CONVERT_FLOATS_ON_IMPORT) > 0;
            $mili = ($flags && CMS_EXCEL_CONVERT_SHORTEN_FLOAT_PREC_TO_MILI) > 0;
            $comm = ($flags && CMS_EXCEL_CONVERT_DISPLAY_COMMA_IN_NUMBERS) > 0;
            $uns = ($flags && CMS_EXCEL_CONVERT_USE_SHEET_NUMBER) > 0;

            // open zip
            $zip = @zip_open($path);

            if (!is_resource($zip)) {
                return false;
            }
            // find all entries in zip
            $emap = array();

            do {
                $entry = @zip_read($zip);
                if (is_resource($entry)) {
                    $name = zip_entry_name($entry);
                    if (strlen($name)>0) {
                        $data = cms_excel2k7::get_zip_entry_xml($zip, $entry);
                        $emap[$name] = $data;
                    }
                }
            } while ($entry);

            // close zip
            zip_close($zip);

            // search for mandatory entries
            $t0 = isset($emap['xl/workbook.xml']);
            $t1 = isset($emap['xl/_rels/workbook.xml.rels']);

            if (!$t0 || !$t1) {
                return false;
            }

            // find sheet step 1
            $dat = $emap['xl/workbook.xml'];
            $ele = $dat->getElementsByTagName('sheet'); // this xml data set is relatively small, call is not frequent, bytagname is OK
            $p = $ele->length;
            $af = false;
            for($j=0; $j<$p; $j++) {
                $node = $ele->item($j);
                if (($usn?$j==$shtname:$node->getAttribute('name') == $shtname)) {
                    $af = true;
                    $rel = $node->getAttribute('r:id');
                    break;
                }
            }

            if (!$af)
                return false;

            $ts = '';

            // find sheet step 2
            $dat = $emap['xl/_rels/workbook.xml.rels'];
            $ele = $dat->getElementsByTagName('Relationship'); // this xml data set is relatively small, call is not frequent, bytagname is OK
            $p = $ele->length;
            $af = false;
            for($j=0; $j<$p; $j++) {
                $node = $ele->item($j);
                if ($node->getAttribute('Id') == $rel) {
                    $af = true;
                    $file = $node->getAttribute('Target');
                }
                if ($node->getAttribute('Type') == "http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" ) {
                    $ts = $node->getAttribute('Target');
                }
            } 

            if (!$af)
                return false;            

            // shared strings if there are any
            if (strlen($ts)) {
                $sharedstr = array();
                $dat = $emap['xl/'.$ts];
                $ele = $dat->firstChild;
                for($ce = $ele->firstChild; $ce!=null; $ce = $ce->nextSibling) {
                    if ($ce->nodeName == 'si')
                        $sharedstr[] = $ce->firstChild->firstChild->nodeValue;
                }
            }

            $data = $emap['xl/'.$file];
            $te = $data->firstChild;
            for($te = $te->firstChild; $te != null; $te = $te->nextSibling) {
                if ($te->nodeName == 'dimension') {
                    $dim = $te->getAttribute('ref');
                    continue;
                }
                if ($te->nodeName == 'sheetData') {
                    $xrow = $te->firstChild;
                }
            }

            // initialize array
            $d0 = explode(":",$dim);
            cms_excel2k7::a1_to_rc($d0[1], $row, $col);

            $row++;    
            $outt = array();
            for($i = 0; $i<$row; $i++) {
                $outt[$i] = array();
            }

            $ar = 0;
            for(; $xrow!=null; $xrow = $xrow->nextSibling) {
                if ($xrow->nodeName != 'row')
                    continue;
                $ar++;
                for ($k = $xrow->firstChild; $k!=null; $k=$k->nextSibling) {
                    if ($k->nodeName != 'c')
                        continue;
                    $loc = $k->getAttribute('r');
                    $styl = $k->getAttribute('s');
                    $typ2 = $k->getAttribute('t');
                    cms_excel2k7::a1_to_rc($loc, $row, $col);
                    $valv = ''; $valis = '';
                    for($v01 = $k->firstChild; $v01 != null; $v01 = $v01->nextSibling) {
                        if ($v01->nodeName == 'v') {
                            $valv = (string)$v01->firstChild->nodeValue;
                        }
                        if ($v01->nodeName == 'is') {
                            $valis = (string)$v01->firstChild->nodeValue;
                        }
                    }
                    switch($typ2) {
                        case 's':
                            $outt[$row][$col] =& $sharedstr[$valv];
                            break;
                        case 'inlineStr':
                            $outt[$row][$col] = $valis;
                            break;
                        case 'n':
                            $outt[$row][$col] = $valv;
                            break;
                        default:
                            if ($docf && is_numeric($valv)) {
                                $outt[$row][$col] = (float)$valv;
                                if ($mili) {
                                    $outt[$row][$col] = round($outt[$row][$col],3);
                                }
                                if ($comm) {
                                    $outt[$row][$col] = strtr((string)($outt[$row][$col]),'.',',');
                                }
                            } else {
                                $outt[$row][$col] = $valv;
                            };
                    }
                }
            }    

            return $outt;

        }
    }
    
?>