<?php 
  header("Access-Control-Allow-Origin:*");//跨域
  
  $para = $_GET["para"];
  
  /* 用户登录 */
  if($para == "isUserEnter"){
    echo "ganglia000";
  }

  /* 参数无效 */
  else{
    echo "invalid";
  }

?>
