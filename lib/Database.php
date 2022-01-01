<?php
class Database {
    var $DB;

    function __construct( $C ) {
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
    function sqlQuote( $entry ) {
        if ( preg_match( "/^(NULL|NOW\(\))$/i", $entry ) ) {
            return $entry;
        }
        return '\''.$entry.'\'';
    }
    function _tablefields( $table, $forceAll = false )  {
        $result = $this->DB->query("describe $table") or die( mysqli_error( $this->DB ) );
        $fields = array();
        while ( $row= mysqli_fetch_array( $result ) ) {
            if ( $forceAll || stripos( $row['Extra'], "uto_increment" ) == false ) { // auto_increment generates 0!
                array_push( $fields, $row['Field'] );
            }
        }
        return $fields;
    }
    function _values( $table, $entry, $forceAll = false ) {
         $tf = $this->_tablefields($table, $forceAll );
         $tval = array();
         $tcol = array();
         foreach ( $tf as $key ) {
             if ( isset( $entry[$key] ) ) {
                array_push( $tcol, $key );
                array_push( $tval, $this->sqlQuote($entry[$key]) );
             } else {
                // array_push( $tval, '\'\'' );
             }
         }
         $vals = join( ",", $tval );
         $vals = trim( $vals, "," );
         $tcol = join( ",", $tcol );
         $tcol = trim ( $tcol ,"," );
         return [$tcol, $vals];
    }
    function insert( $table, $entry ) {
        [$tcol, $jn] = $this->_values( $table, $entry );
        $stmnt = "insert into $table ($tcol) values ($jn)";
        debug( $stmnt );
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
        return $stmnt;
    }
    function replace( $table, $entry ) {
        [$col, $jn] = $this->_values( $table, $entry, true );
        $stmnt = "replace into $table values ($jn)";
        // print " **** $stmnt **** \n";
        $this->DB->query( $stmnt ) or die ( mysqli_error($this->DB ) );
    }
    function delete( $table, $where ) {
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
        debug( $stmnt );
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
    }
    function select( $query ) {
        $stmnt ="select ".$query;
        $rslt =  $this->DB->query( $stmnt ) or die('###select### '. mysqli_error($this->DB ) );
        $ret = array();
        while ( $row= mysqli_fetch_array( $rslt ) ) {
            array_push( $ret, $row );
        }
        return $ret;
    }
    function query( $stmnt ) {
        $rslt =  $this->DB->query( $stmnt ) or die('###select### '. mysqli_error($this->DB ) );
        return $rslt;
    }
    function selectOne( $query ) {
        $res = $this->select($query);
        return count($res)? $res[0] : 0;
    }
    function implodeSelection( $collection, $field ) {
        $coll = array();
        foreach  ( $collection as $c ) {
            array_push( $coll, $c[$field] );
        }
        return implode( ',', $coll );
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
}
?>
