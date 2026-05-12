<?php 
require "/suport";
requireAuth();
requireAdmin();


if (!empty($_POST["submit"])) {
    $category_id=(int)$_POST["category_id"]??'';
    $dificuldade=(int)$_POST["diffcult_id"]??'facil';
    $descricao=(string)$_POST["content"]??'';
    $resposta=(string)$_POST["explain"];
    $completo=dificulty()[$dificuldade];
   if (verify_Bd_registro("questions","explain",$resposta)) {
     echo "esta questão já foi adicionada";
   }else {
    $sql = "INSERT INTO questions (category_id, diffcult_id, content, explain) VALUES (?, ?, ?, ?)";
    $stmt = $conectar->prepare($sql);
    $stmt->bind_param("iiss", $category_id,$completo , $descricao, $resposta);
    
    if ($stmt->execute()) {
      echo "Questão criada com sucesso";
    } else {
      echo "Erro ao criar questão: " . $stmt->error;
    }
    $stmt->close();
   }
}
