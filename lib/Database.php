<?php

class Database {
    var $DB;

    function __construct($C) {
        $this->DB = new mysqli( $C->host, $C->user, $C->password, $C->database );
    }
    function getReg( $stmnt, $keyfield ) {
        $_r = $this->select( $stmnt );
        $r = array();
        foreach ( $_r as $i => $sitm ) {
            $r[ $sitm[$keyfield] ] = $sitm;
        }
        return $r;
    }
    function insert( $table, $entry ) {
        $jn = $this->_values( $table, $entry );
        $stmnt = "insert into $table values ($jn)";
        // print " **** $stmnt **** \n";
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
    }
    function replace( $table, $entry ) {
        $jn = $this->_values( $table, $entry );
        $stmnt = "replace into $table values ($jn)";
        // print " **** $stmnt **** \n";
        $this->DB->query( $stmnt ) or die ( mysqli_error($this->DB ) );
    }
    function delet( $table, $where ) {
        $stmnt = "delete from $table where $where";
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this-DB ) );
    }
    function update( $table, $entry, $where ) {
        $tf = $this->_tablefields($table);
        $starr = array();
        foreach ( $tf as $key ) {
            if ( isset( $entry[$key] ) ) {
                array_push( $starr, "$key=".$this->sqlQuote($entry[$key]) );
            }
        }
        $jn = join( ",", $starr );
        $jn = trim ( $jn ,"," );
        $stmnt = "update $table set $jn where $where";
        // print " **** $stmnt **** \n";
        $this->DB->query( $stmnt ) or die ( mysql_error( $this->DB ) );
    }
    function select( $query ) {
        $stmnt ="select ".$query;
        print "\nselect.stmnt:$stmnt\n";
        $rslt =  $this->DB->query( $stmnt ) or die('###select### '. mysqli_error($this->DB ) );
        $ret = array();
        while ( $row= mysqli_fetch_array( $rslt ) ) {
            array_push( $ret, $row );
        }
        return $ret;
    }
    function selectFirst( $query ) {
        $tmp = $this->select($query);
        return count($tmp)? $tmp[0] : null;
    }
    function _tablefields( $table )  {
        $result = $this->DB->query("describe $table") or die( mysqli_error( $this->DB ) );
        $fields = array();
        while ( $row= mysqli_fetch_array( $result ) ) {
            if ( strpos( $row['Field'], "auto_increment" ) != -1 ) {
                array_push( $fields, $row['Field'] );
            }
        }
        return $fields;
    }
    function sqlQuote( $entry ) {
       if ( preg_match( "/^(NULL|NOW\(\))$/i", $entry ) ) {
           return $entry;
       }
       return '\''.$entry.'\'';
    }
    function _values( $table, $entry ) {
        $tf = $this->_tablefields($table);
        $starr = array();
        foreach ( $tf as $key ) {
            if ( isset( $entry[$key] ) ) {
                array_push( $starr, $this->sqlQuote($entry[$key]) );
            } else {
                array_push( $starr, '\'\'' );
            }
        }
        $jn = join( ",", $starr );
        $jn = trim ( $jn ,"," );
        return $jn;
    }
    function getUploadName( $R, $imgname ) {
        $fname = $_REQUEST['old_'.$imgname];
        if ( $_FILES[$imgname]['name'] > "0" ) {
            $fname = $_FILES[$imgname]['name'];
            if ( is_uploaded_file($_FILES[$imgname]['tmp_name'] ) ) {
                if ( ! file_exists( $R['uploaddir'] ) ) {
                    mkdir( $R['uploaddir'] );
                }
                copy($_FILES[$imgname]['tmp_name'], $R['uploaddir'].'/'.$fname );
            }
        }   
        return $fname;
    }
    function _post( $table, $_id, &$R ) {
        $R['_insert']=0;
        $R['_update']=0;
        $R['_delete']=0;
        $R['_inserted'] = 0;
        $R['_deleted']  = 0;
        $R['_updated']  = 0;
        $imgfields = array( 'cimage', 'splashimage', 'image', 'imagezoom', 'imagethumb', );
        $id = $R[$_id];
        foreach ( $imgfields as $field  ) {
            if ( preg_match( '/delete/', $R['action']) or ( preg_match('/update|insert/', $R['action'] ) && $_FILES[$field]['name']>"0" ) ) { 
                if ( file_exists( $R['uploaddir'].'/'.$R['old_'.$field] ) && strlen($R['old_'.$field])>0 ) {
                     unlink( $R['uploaddir'].'/'.$R['old_'.$field] );
                }
            } 
            $R[$field] = $this->getUploadName($R,$field );
        }
        if ( $R['action']=='update' ) {
             $this->update( $table, $R, "$_id='$id'" );
             $R['report'] = 'post uppdaterad';
             $R['_update'] = 1;
             $R['_delete'] = 1;
             $R['_updated'] = 1;
        } else if ( $R['action'] == 'delete' ) {
             $this->delet( $table, "$_id='$id'" );
             $R['report'] = 'post borttagen';
             $R['_insert'] = 1;
             $R['_deleted'] = 1;
        } else if ( preg_match( '/insert|register/', $R['action'] ) ) {
            $ptr = $this->select("max($_id) from $table");
            $max = $ptr[0]["max($_id)"]+1;
            $R[$_id] = $max;
            $this->insert( $table, $R );
            $R['report'] = 'post inlagd';
            $R['_inserted'] = 1;
            $R['_update'] = 1;
            $R['_delete'] = 1;
        } else {
            if ( $id == '' ) {
                $R['_insert'] = 1;
                return;
            }
            $ptr = $this->select("* from $table where $_id='$id'");
            $ptr = $ptr[0];
            $ptr['image']       = $ptr['image']  == '' ? 'no_image.jpg' : $ptr['image'];
            $ptr['cimage']      = $ptr['cimage'] == '' ? 'no_image.jpg' : $ptr['cimage'];
            $ptr['splashimage'] = $ptr['splashimage'] == '' ? 'no_image.jpg' : $ptr['splashimage'];
            $ptr['imagezoom']   = urlencode( ($ptr['imagezoom']=='') ? 'images/no_image.jpg' : $ptr['imagezoom'] );
            $ptr['imagethumb']  = urlencode( ($ptr['imagethumb']=='') ? 'images/no_image.jpg' : $ptr['imagethumb'] );
            $R = array_merge( $R, $ptr );
            $R['_update'] = 1;
            $R['_delete'] = 1;
        }
    }
}
?>
