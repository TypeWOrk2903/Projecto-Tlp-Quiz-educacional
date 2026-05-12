<?php
require_once __DIR__."./backend/suporte/bd_config";
if(!empty($_POST["submit"])){
   $nome=$_POST["name"];
   $email=$_POST["email"];
   $password1=$_POST["senha"];
   $password2=$_POST["senha_confirma"];
}