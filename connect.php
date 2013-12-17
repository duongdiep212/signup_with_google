<?php
$con = mysqli_connect("mysql://$OPENSHIFT_MYSQL_DB_HOST:$OPENSHIFT_MYSQL_DB_PORT/","adminDIjKmD7","NkZvCtV-GiBk") or die (mysql_error());

mysqli_select_db($con,"signupgoogle"); 
?>