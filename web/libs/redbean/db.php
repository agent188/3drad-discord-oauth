<?php 
include('rb.php');
R::setup( 'mysql:host=localhost;dbname=3drad','3drad', '123456', true);
if(!R::testConnection()) { 
    http_response_code(500);
    die('No DB connection!');
}
