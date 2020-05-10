<?php
    // xml basec funcs

    class cms_xml {

        public static function find_freeid(&$parentnode) {
            $maxid=0;
            for($cnode = $parentnode->firstChild; $cnode != NULL; $cnode = $cnode->nextSibling) {
                if ($cnode->nodeName == cms_entry_xml::$ENTRY_TAG) {
                    $cid = $cnode->getAttribute(cms_entry_xml::$ID_ATTR);
                    if ($cid>$maxid) {
                        $maxid = $cid;
                    }
                }
            }
            return 1+$maxid;
        }
        
        public static function find_subnode_rlang($node, $filter = array(), $nodename = false) {
            for($pnode = $node->firstChild; $pnode != null; $pnode = $pnode->nextSibling) {
                if (($pnode->nodeType == XML_ELEMENT_NODE) && ($nodename === false?true:$pnode->nodeName == $nodename)) {
                    $res = true;
                    foreach ($filter as $k=>$v) {
                    	if ($k == cms_entry_xml::$LANG_ATTR) {
                    		if ($v != '') {
                    			$res = $res && (in_array($v, cms_entry_xml::langs_from_xml_attribute($pnode->getAttribute($k))));
                    		}	
                    	} else {
                    		$res = $res && ($pnode->getAttribute($k) == $v);
                    	}                        
                    }
                    if ($res)
                        return $pnode;
                }
            }
            return null;
        } 

        public static function find_subnode($node, $filter = array(), $nodename = false) {
            for($pnode = $node->firstChild; $pnode != null; $pnode = $pnode->nextSibling) {
                if (($pnode->nodeType == XML_ELEMENT_NODE) && ($nodename === false?true:$pnode->nodeName == $nodename)) {
                    $res = true;
                    foreach ($filter as $k=>$v) {
                  		$res = $res && ($pnode->getAttribute($k) == $v);                        
                    }
                    if ($res)
                        return $pnode;
                }
            }
            return null;
        }       

    }

?>