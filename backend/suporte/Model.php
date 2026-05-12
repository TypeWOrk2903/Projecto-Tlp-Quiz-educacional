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

/**
 * verify_Bd_registro
 *
 * @param  mixed $table
 * @param  mixed $column
 * @param  mixed $value
 * @return bool
 */
function verify_Bd_registro(string $table,string $column, string $value): bool
{
    global $bv;
    $check_registro=$bv->prepare("SELECT * FROM $table WHERE $column=?");
    $check_registro->bind_param("s", $value);
    $check_registro->execute();
    $check_registro->store_result();
    return $check_registro->num_rows > 0;
}
