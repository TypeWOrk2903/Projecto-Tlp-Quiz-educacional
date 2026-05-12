<?php
 require "bd_config.php";
 $bv=$conectar;
 function verify_Bd_email(string $email): string
{
    global $bv;
    $check_email=$bv->prepare("SELECT * FROM users WHERE email=?");
    $check_email->bind_param("s",$email);
    $check_email->execute();

    $check_email->store_result();
    return $check_email->number_rows>0;
}
