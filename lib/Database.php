<?php
class Database {
    public $DB;

    function __construct( $C ) {
        if ( $C ) {
            $this->DB = new mysqli( $C->host, $C->user, $C->password, $C->database );
        }
    }
    function sqlQuote( $entry ) {
        if ( preg_match( "/^(NULL|NOW\(\))$/i", $entry ) ) {
            return $entry;
        }
        // input containing ' makes system crash
        // https://www.php.net/manual/en/mysqli.real-escape-string.php
        $entry = mysqli_real_escape_string( $this->DB, $entry );
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
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
        return $stmnt;
    }
    function replace( $table, $entry ) {
        [$col, $jn] = $this->_values( $table, $entry, true );
        $stmnt = "replace into $table values ($jn)";
        $this->DB->query( $stmnt ) or die ( mysqli_error($this->DB ) );
    }
    function delete( $table, $where ) {
        $stmnt = "delete from $table where $where";
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
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
        $this->DB->query( $stmnt ) or die ( mysqli_error( $this->DB ) );
    }
    function select( $query ) {
        $stmnt ="select ".$query;
        $rslt =  $this->DB->query( $stmnt ) or die('###select### '. mysqli_error($this->DB ) );
        $ret = [];
        while ( $row= mysqli_fetch_array( $rslt ) ) { // by default key=>val as well
            array_push( $ret, $row );
        }
        return $ret;
    }
    function selectOne( $query ) {
        $res = $this->select($query);
        return count($res)? $res[0] : 0;
    }
    function query( $stmnt ) {
        $rslt =  $this->DB->query( $stmnt ) or die('###query### '. mysqli_error($this->DB ) );
        return $rslt;
    }
    function collection( &$posts, $field, &$coll ) {
        foreach  ( $posts as $p ) {
            if ( !$p[$field] ) continue;
            $coll[ $p[$field] ] =1;
        }
    }
    function implodeSelection( &$posts, $field ) {
        $coll = [];
        $this->collection( $posts, $field, $coll );
        return implode( ',', array_keys($coll) );
    }
    /* ************* utilities not used ***********/
    function getReg( $stmnt, $keyfield ) {
        $_r = $this->select( $stmnt );
        $r = array();
        foreach ( $_r as $i => $sitm ) {
            $r[ $sitm[$keyfield] ] = $sitm;
        }
        return $r;
    }
    
}
?>
