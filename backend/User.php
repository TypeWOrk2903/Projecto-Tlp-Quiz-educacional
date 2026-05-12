<?php
require_once __DIR__ . "./backend/suporte/bd_config.php";
require_once "./suporte/utilitarios.php";
require_once "./suporte/Model.php";
if (!empty($_POST["submit"])) {
    $nome = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["senha"];
    if (verify_Bd_email($email)) {
       echo "Email já foi inserido";
    }else{
          if (verify_email($email) && sanitize($email)) {
        $senha_segura = hash_generate($password);
        $nome_san = sanitize($nome);
        $criar = $conectar->prepare("INSERT INTO users(`name`,email,`password`) VALUES(?,?,?) ");
        $criar->bind_param("sss", $nome_san, $email, $senha_segura);
        if ($criar->execute()) {
            echo "Usuario criado com sucesso";
        }else{
            echo "Erro ao inserir tente denovo";
        }
    }else{
        json_encode("email foi verificado e não esta conforme o padrão"); 
    }
    }
}
