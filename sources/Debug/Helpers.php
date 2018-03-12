<?php

function _print(){
    $args = func_get_args();

    if( is_array( $args ) and count( $args ) ){
        $backtrace = debug_backtrace( 0 );
        echo "<pre>";
        echo "({$backtrace[0]['file']}::{$backtrace[0]['line']})";
        foreach( $args as $a ){
            echo "<br>";
            print_r($a);
        }
        
        echo "<br><br>".var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true );
    
        exit;
    }
}

function _dump(){
    $args = func_get_args();
    if( is_array( $args ) and count( $args ) ){
        $backtrace = debug_backtrace( 0 );
        echo "<pre>";
        echo "({$backtrace[0]['file']}::{$backtrace[0]['line']})";
        foreach( $args as $a ){
            echo "<br>";
            var_dump($a);
        }
    
        echo "<br><br>".var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true );
    
        exit;
    }
}

function _export(){
    $args = func_get_args();
    if( is_array( $args ) and count( $args ) ){
        $backtrace = debug_backtrace( 0 );
        echo "<pre>";
        echo "({$backtrace[0]['file']}::{$backtrace[0]['line']})";
        foreach( $args as $a ){
            echo "<br>";
            var_export($a);
        }
    
        echo "<br><br>".var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true );
    
        exit;
    }
}