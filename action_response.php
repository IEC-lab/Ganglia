<?php 
  include_once("sqlite3_db.php");
  header("Access-Control-Allow-Origin:*");//跨域
  header("Content-type:text/html;charset=utf-8");
  
  /* $action为命令：registerCluster、modifyCluster等 */
  $action = $_GET["action"];
  
  /* 注册集群 */
  if($action == "registerCluster"){
    $cluster_name = $_GET["cluster_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from cluster where name='$cluster_name'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    if($isExists){//集群名已存在
      $db->close(); 
      echo "already exists";
    }else{
      $db->close(); 
      exec("$execBasePath/cluster_operation/cluster_build $cluster_name", $res);
      if($res[0] == 101)
        echo "register successfully";
      else if($res[0] == 103)
	echo "SUB_PORT_SET_FAIL";
    }
  }

  /* 运行execBasePath command且不要结果 */
  else if($action == "runCommandWithoutResult"){
    $s_cmd = $_GET["s_cmd"];
    $cmd = $execBasePath . $s_cmd;
    exec($cmd);
  }

  /* 测试 */
  else if($action == "test"){
    $ip = $_GET["ip"];
    exec("./get_top_info $sshUser $ip", $res);
    echo json_encode($res);
  }

  /* 获取top信息 */
  else if($action == "getTopInfo"){
    $ip = $_GET["ip"];
    exec("./get_top_info $sshUser $ip", $res);
    echo json_encode($res);
  }

  /* 获取对应ip的cpu空闲率 */
  else if($action == "getIpsCpuIdle"){
    $rsArr = array();
    $i = 0;
    while(isSet($_GET["ip$i"])){
      $ip = $_GET["ip$i"];
      exec("./get_top_info $sshUser $ip", $res[$ip]);
      $rsArr[] = "$ip " . explode(" ",$res[$ip][2])[10];
      $i++;
    }
    echo json_encode($rsArr);
  }

  /* 修改集群 */
  else if($action == "modifyCluster"){
    $old_name = $_GET["old_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from cluster where name='".$old_name."'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    $db->close();
    if($isExists){
      $new_name = $_GET["new_name"];
      exec("$execBasePath/cluster_operation/cluster_rename $old_name $new_name", $res);
      if($res[0] == 122){
        echo "already exists";
      }else if($res[0] == 121){
        echo "update successfully";
      }else if($res[0] == 112)
        echo "busy";
    }
    else{
      echo "mul-op-error";
    }
  }

  /* 用户登录 */
  else if($action == "userEnter"){
    $userName = $_GET["userName"];
    $userPw = $_GET["userPw"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where name='$userName' and passwd='$userPw'");
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $db->exec("update user set click_num = 1 where name='$userName'");
      echo "ganglia000 ".$row['authority'];
    }
    else{
      echo "123213123";
    }
    $db->close(); 
  }

  /* 用户免密码登录 */
  else if($action == "noPW"){
    $userName = $_GET["userName"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where name='$userName'");
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      if($row['click_num'] == 0){
        echo "not ok";
      }else{
	if($row['click_num'] == 101){
          $new_num = 0;
	}else{
	  $new_num = $row['click_num'] + 1;
	}
        $db->exec("update user set click_num=$new_num  where name='$userName'");
        echo $row['authority']." ".$row['identity'];
      }
    }
    else{
      echo "not ok";
    }
    $db->close(); 
  }

  /* 用户注销 */
  else if($action == "userLogOut"){
    $userName = $_GET["userName"];
    $db = new SQLiteDB();
    $db->exec("update user set click_num=0  where name='$userName'");
    $db->close(); 
  }

  /* 获取当前集群 */
  else if($action == "getCurrentClusters"){
    $currentClusters = array();
    $db = new SQLiteDB();
    $ret = $db->query("select * from clusters");
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $currentClusters[] = $row["name"]." ".$row["node_num"];
    }
    $db->close(); 
    echo json_encode($currentClusters);
  }

  /* 获取主节点信息 */
  else if($action == "getMainNodeInfo"){
    $mainNodeFile = fopen("$mainNodeFileBasePath/main_node", "r");
    if(!$mainNodeFile){
      echo "$$$";
      return;
    }
    header("Content-type:text/html;charset=GB2312");
    while(!feof($mainNodeFile))
      echo fgets($mainNodeFile);
    fclose($mainNodeFile);
  }

  /* 获取节点备份信息 */
  else if($action == "getNodesBackupInfo"){
    $db = new SQLiteDB();
    $ret = $db->query("select * from backup_node");
    $nodesBackupInfo = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $nodesBackupInfo[] = $row["active_node_ip"]." ".$row["backup_node_ip"];
    }
    $db->close(); 
    echo json_encode($nodesBackupInfo);
  }

  /* 通过集群名获取节点信息 */
  else if($action == "getNodesInfoByClusterName"){
    $clusterName = $_GET["cluster_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select node_name from cluster_node where cluster_name='$clusterName'");
    $nodesInfoArr = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $ret1 = $db->query("select * from node where name='{$row["node_name"]}'");
      $row1 = $ret1->fetchArray(SQLITE3_ASSOC);
      $nodesInfoArr[] = "{$row["node_name"]} {$row1["ip"]} {$row1["status"]}";
    }
    $db->close();
    echo json_encode($nodesInfoArr);
  }

  /* 删除集群 */
  else if($action == "deleteCluster"){
    $clusterName = $_GET["cluster_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from cluster where name='".$clusterName."'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    $db->close();
    if($isExists){
      exec("$execBasePath/cluster_operation/cluster_del $clusterName", $res);
      if($res[0] == 112){
        echo "busy";
      }else if($res[0] == 111){
        echo "delete successfully";
      }else if($res[0] == 113){
        echo "have subnodes";
      }
    }else{
      echo "mul-op-error";
    }
  }

  /* 获取一个节点的具体信息 */
  else if($action == "getANodeInfo"){
    $node_name = $_GET["node_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from node where NAME='$node_name'");
    $row = $ret->fetchArray(SQLITE3_ASSOC);
    echo $row['name']." ".$row['ip']." ".$row["status"];
    $db->close();
  }

  /* 获取所有任务信息 */
  else if($action == "getAllTaskInfoForCMT"){
    $db = new SQLiteDB();
    $ret = $db->query("select * from task order by task_name");
    $allTasksInfo = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $occupied_node = "";
      $hostArr = explode(",",$row["occupied_node"]);
      foreach($hostArr as $oneHost){
	$ret0 = $db->query("select name from node where host='$oneHost'");
	$row0 = $ret0->fetchArray(SQLITE3_ASSOC);
	if($occupied_node == ""){
	  $occupied_node = $row0["name"];
	}else{
	  $occupied_node .= ",{$row0["name"]}";
	}
      }
      $allTasksInfo[] = "{$row["task_name"]} {$row["cluster_name"]} $occupied_node {$row["status"]} {$row["task_load"]} {$row["user"]} {$row["start_time"]} {$row["end_time"]}";
    }
    $db->close();
    echo json_encode($allTasksInfo);
  }

  /* 通过集群名和节点名获取任务信息 */
  else if($action == "getTaskInfoByNodeNameAndClusterName"){
    $node_name = $_GET["node_name"];
    $cluster_name = $_GET["cluster_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where cluster_name='$cluster_name'");
    $taskInfoArr = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      ($row["end_time"]!="")?$endTime=$row["end_time"]:$endTime="none";
      $taskInfoArr[] = "{$row["task_name"]} {$row["status"]} {$row["task_load"]} {$row["user"]} {$row["start_time"]} {$row["task_port"]}";
    }
    $db->close();
    echo json_encode($taskInfoArr);
  }

  /* 增加某集群的节点 */
  else if($action == "addClusterNode"){
    $cluster_name = $_GET["cluster_name"];
    $node_name = $_GET["node_name"];
    $node_ip = $_GET["node_ip"];
    exec("$execBasePath/node_operation/node_add $node_ip $node_name $cluster_name", $res);
    echo $res[0];
  }

  /* 删除某集群的节点 */
  else if($action == "deleteClusterNode"){
    $cluster_name = $_GET["cluster_name"];
    $node_name = $_GET["node_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from cluster_node where (cluster_name='$cluster_name' and node_name='$node_name')");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    $db->close();
    if($isExists == 1){
      exec("$execBasePath/node_operation/node_del $cluster_name $node_name", $res);
      echo $res[0];
    }else{
      echo "mul-op-error";
    }
  }

  /* 通过nodeName获取clusterNodeNum */
  else if($action == "getClusterNodeNumByNodeName"){
    $nodeName = $_GET["nodeName"];
    $db = new SQLiteDB();
    $ret = $db->query("select count(*) from cluster_node where node_name='$nodeName'");
    $row = $ret->fetchArray(SQLITE3_ASSOC);
    echo $row["count(*)"];
    $db->close();
  }

  /* 用户注册 */
  else if($action == "registerUser"){
    $name = $_GET["name"];
    $pw0 = $_GET["pw0"];
    $permission = $_GET["permission"];
    $identity = $_GET["identity"];
    $description = $_GET["description"];
    $time = date("Y-m-d H:i:s");
    $db = new SQLiteDB();
    $ret = $db->exec("insert into user values('$name','$pw0','$permission','$identity','$description','$time',0)");
    if(!$ret){
      echo "insert failed";
    }else{
      echo "insert successfully";
    }
    $db->close(); 
  }

  /* 用户信息管理身份确认 */
  else if($action == "userManageConfirm"){
    $userName = $_GET["userName"];
    $userAuthority = $_GET["userAuthority"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where NAME='".$userName."' and authority='".$userAuthority."'");
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      echo "ok";
    }else{
      echo "not ok";
    }
    $db->close();
  }

  /* 修改用户名 */
  else if($action == "modifyUserName"){
    $oldName = $_GET["oldName"];
    $newName = $_GET["newName"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where NAME='".$newName."'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    if($isExists){
      echo "new name exists";
    }else{
      $ret = $db->query("select * from user where NAME='".$oldName."'");
      $isExists = 0;
      if($row = $ret->fetchArray(SQLITE3_ASSOC)){
        $isExists = 1;
      }
      if($isExists){
        $ret = $db->exec("update user set NAME='$newName' where NAME='$oldName'");
        if(!$ret){
          echo "update failed";
        }else{
          echo "update successfully";
        }
      }
      else{
        echo "mul-op-error";
      }
    }
    $db->close();
  }

  /* 修改用户密码 */
  else if($action == "modifyUserPw"){
    $userName = $_GET["userName"];
    $oldPw = $_GET["oldPw"];
    $newPw = $_GET["newPw"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where name='$userName' and passwd='$oldPw'");
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){//原密码正确
      $ret = $db->exec("update user set passwd='".$newPw."' where name='".$userName."'");
      if(!$ret){
        echo "update failed";
      }else{
        echo "update successfully";
      }
    }
    else{//原密码错误
      echo "old pw error";
    }
    $db->close(); 
  }

  /* 判断用户名与邮箱是否匹配（邮箱密码找回） */
  else if($action == "userForgetPw"){
    $userName = $_GET["userName"];
    $userEmail = $_GET["userEmail"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where name='$userName' and email='$userEmail'");
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){//用户名与邮箱匹配
      $ret = $db->query("select * from user_retrieve where user_name='$userName'");
      if($row = $ret->fetchArray(SQLITE3_ASSOC)){//key已存在
        echo $row["key"];
      }else{//key不存在
	$key = rand(100,999);
        $db->exec("insert into user_retrieve values('$userName','$key')");
	echo $key;
      }
    }else{//用户名与邮箱不匹配
      echo "email does not match name";
    }
    $db->close();
  }

  /* 修改密码（邮箱密码找回） */
  else if($action == "emailModifyPw"){
    $pw = $_GET['pw'];
    $name = $_GET['name'];
    $db = new SQLiteDB();
    $ret = $db->exec("update user set passwd='$pw' where name='$name'");
    if($ret){
      $ret = $db->exec("delete from user_retrieve where user_name='$name'");
      if($ret){
        echo "update successfully";
      }else{
        echo "update failed";
      }
    }else{
      echo "update failed";
    }
    $db->close();
  }

  /* 获取common用户信息 */
  else if($action == "getSubUserInfo"){
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where authority<>'admin'");
    $subUserInfo = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $subUserInfo[] = $row["name"]." ".$row["authority"]." ".$row["identity"]." ".$row["description"]." ".$row["create_time"];
    }
    $db->close();
    echo json_encode($subUserInfo);
  }

  /* 通过用户名删除用户 */
  else if($action == "deleteUserByName"){
    $user_name = $_GET["user_name"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from user where NAME='".$user_name."'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    if($isExists){
      $db->exec("delete from user where name='$user_name'");
      if($db->changes()){
        echo "delete successfully";
      }else{
        echo "delete failed";
      }
    }else{
      echo "mul-op-error";
    }
    $db->close(); 
  }

  /* 获取系统节点 */
  else if($action == "getSystemNodes"){
    $systemNodes = array();
    $db = new SQLiteDB();
    $ret = $db->query("select * from node");
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $systemNodes[] = $row["name"]." ".$row["ip"]." ".$row["status"];
    }
    $db->close(); 
    echo json_encode($systemNodes);
  }

  /* 获取集群节点对应信息 */
  else if($action == "getClusterNodeInfo"){
    $clusterNodeArr = array();
    $db = new SQLiteDB();
    $ret = $db->query("select * from cluster_node order by cluster_name");
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $ret1 = $db->query("select IP,STATUS from Node where NAME='${row["node_name"]}'");
      $row1 = $ret1->fetchArray(SQLITE3_ASSOC);
      $clusterNodeArr[] = $row["cluster_name"] . " " . $row["node_name"] . " " . $row1["ip"] . " " . $row1["status"];
    }
    $db->close(); 
    echo json_encode($clusterNodeArr);
  }

  /* 根据集群名获取节点信息 */
  else if($action == "getNodesNameByClusterName"){
    $cluster_name = $_GET["cluster_name"];
    $clusterNodeArr = array();
    $db = new SQLiteDB();
    $ret = $db->query("select node_name from cluster_node where cluster_name='$cluster_name'");
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $clusterNodeArr[] = $row["node_name"];
    }
    $db->close();
    echo json_encode($clusterNodeArr);
  }

  /* 挂起所有热机任务 */
  else if($action == "hangUpAllHotTasks"){
    exec("$execBasePath/task_operation/task_client 4",$res);
    echo $res[0];
  }

  /* 激活所有热机任务 */
  else if($action == "activateAllHotTasks"){
    exec("$execBasePath/task_operation/task_client 5",$res);
    echo $res[0];
  }

  /* 重启集群心跳服务 */
  else if($action == "restartClusterHeartBeats"){
    exec("$execBasePath/node_detection/re_heart_detect > /dev/null 2>&1 &");
    echo "801";
  }

  /* 重启节点心跳服务 */
  else if($action == "restartNodeHeartBeats"){
    exec("$execBasePath/node_detection/re_node {$_GET["nodeIp"]} > /dev/null 2>&1 &");
    echo "811";
  }

  /* 提交任务 */
  else if($action == "submitTask"){
    $opCode = $_GET["op_code"];
    $clusterName = $_GET["cluster_name"];
    $jobId = $_GET["job_id"];
    $taskNum = $_GET["task_num"];
    $user = $_GET["user"];
    $paramStr = "$opCode $clusterName $user $jobId $taskNum";
    $i = 0;
    $db = new SQLiteDB();
    while(isSet($_GET["id_".$i])){
      $ret = $db->query("select * from task where task_id={$_GET["id_".$i]}");//检测任务号是否已存在
      $row = $ret->fetchArray(SQLITE3_ASSOC);
      if($row){
        $db->close();
        echo "task id exists {$_GET["id_".$i]}";
	return;
      }
      $paramStr .= " " . $_GET["id_".$i];
      $paramStr .= " " . $_GET["type_".$i];
      $i++;
    }
    $db->close();
    if($opCode == 1){
      exec("$execBasePath/task_operation/task_client $paramStr",$res);
    }
    else if($opCode == 6){
      $nodeName = $_GET["node_name"];
      $db = new SQLiteDB();
      $ret = $db->query("select ip from node where name='$nodeName'");
      $row = $ret->fetchArray(SQLITE3_ASSOC);
      $ip = $row["ip"];
      $db->close();
      $paramStr .= " " . $ip;
      exec("$execBasePath/task_operation/task_client $paramStr",$res);
    }
    echo $res[0];
  }

  /* 通过clusterName获取任务 */
  else if($action == "getTasksByClusterName"){
    $clusterName = $_GET["clusterName"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where (cluster_name='$clusterName' and status='running')");
    $tasksArr = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $tasksArr[] = "{$row["task_id"]} {$row["job_id"]} {$row["node_name"]} {$row["status"]} {$row["user"]} {$row["start_time"]} {$row["task_port"]} {$row["task_type"]}";
    }
    echo json_encode($tasksArr);
    $db->close();
  }

  /* 通过nodeName获取任务 */
  else if($action == "getTasksByClusterNodeName"){
    $nodeName = $_GET["nodeName"];
    $clusterName = $_GET["clusterName"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where (node_name='$nodeName' and cluster_name='$clusterName' and status='running')");
    $tasksArr = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $tasksArr[] = "{$row["task_id"]} {$row["job_id"]} {$row["node_name"]} {$row["status"]} {$row["user"]} {$row["start_time"]} {$row["task_port"]} {$row["task_type"]}";
    }
    echo json_encode($tasksArr);
    $db->close();
  }

  /* 获取所有的任务信息 */
  else if($action == "getAllTasksInfo"){
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where status='running' order by cluster_name");
    $allTasksInfo = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $occupied_node = "";
      $hostArr = explode(",",$row["occupied_node"]);
      foreach($hostArr as $oneHost){
	$ret0 = $db->query("select name from node where host='$oneHost'");
	$row0 = $ret0->fetchArray(SQLITE3_ASSOC);
	if($occupied_node == ""){
	  $occupied_node = $row0["name"];
	}else{
	  $occupied_node .= ",{$row0["name"]}";
	}
      }
      $allTasksInfo[] = $row["cluster_name"]." ".$row["task_name"]." ".$row["user"]." ".$row["start_time"]." ".$row["proc_name"]." ".$row["task_load"]." $occupied_node";
    }
    $db->close();
    echo json_encode($allTasksInfo);
  }

  /* 获取所有任务的pid,task_id,job_id */
  else if($action == "getTaskId3"){
    $db = new SQLiteDB();
    $ret = $db->query("select pid,job_id,task_id from task where status='running' order by cluster_name");
    $taskId3Arr = array();
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $taskId3Arr[] = "{$row["pid"]} {$row["job_id"]} {$row["task_id"]}";
    }
    $db->close();
    echo json_encode($taskId3Arr);
  }

  /* 任务杀死 */
  else if($action == "killTask"){
    $opCode=$_GET["op_code"];
    $clusterName=$_GET["cluster_name"];
    $groupId=$_GET["group_id"];
    $id=$_GET["id"];
    $user=$_GET["user"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where task_id='$id'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    $db->close();
    if($isExists == 1){
      exec("$execBasePath/task_operation/task_client $opCode $clusterName $user $groupId $id",$res);
      echo $res[0];
    }else{
      echo "mul-op-error";
    }
  }

  /* 获取任务日志内容 */
  else if($action == "getTaskLog"){
    $logFile = fopen("$logFileBasePath/log", "r");
    if(!$logFile){
      echo "$$$";
      return;
    }
    header("Content-type:text/html;charset=GB2312");
    while(!feof($logFile))
      echo fgets($logFile)."$";
    fclose($logFile);
  }

  /* 通过sqlite db获取任务日志内容 */
  else if($action == "getTaskLogBySqliteDb"){
    $size = $_GET["size"];
    $sql = "select * from task_log";
    if(isSet($_GET["queryType"]) && $_GET["queryType"] == "byTime"){//按时间查询
      $startTime = $_GET["startTime"];
      $startTimeSplit = split(" ",$startTime);
      $startDMY = split("/",$startTimeSplit[0]);
      $startHM = split(":",$startTimeSplit[1]);
      $qStartTime = "{$startDMY[2]}-{$startDMY[0]}-{$startDMY[1]} {$startHM[0]}:{$startHM[1]}";
      $endTime = $_GET["endTime"];
      $endTimeSplit = split(" ",$endTime);
      $endDMY = split("/",$endTimeSplit[0]);
      $endHM = split(":",$endTimeSplit[1]);
      $qEndTime = "{$endDMY[2]}-{$endDMY[0]}-{$endDMY[1]} {$endHM[0]}:{$endHM[1]}";
      $sql .= " where (time >= '$qStartTime' and time <= '$qEndTime')";
    }
    $sql .= " order by time desc";
    if($size != "all") $sql .= " limit 0,$size";
    $db = new LogSQLiteDB();
    $ret = $db->query($sql);
    while($row = $ret->fetchArray(SQLITE3_ASSOC)){
      echo "{$row["task_op_type"]} {$row["user"]} {$row["time"]}";
      if($row["task_op_type"] == 0){
        echo " {$row["task_id"]} {$row["job_id"]} {$row["cluster_name"]}";
      }else if($row["task_op_type"] == 1){
        echo " {$row["task_id"]} {$row["job_id"]} {$row["cluster_name"]}";
      }else if($row["task_op_type"] == 2){
        echo " {$row["task_id"]} {$row["job_id"]} {$row["cluster_name"]} {$row["source_node_name"]} {$row["target_node_name"]}";
      }
      echo "$";
    }
    $db->close();
  }

  /* 清空任务日志 
  else if($action == "clearTaskLog"){
    $logFile = fopen("$logFileBasePath/log", "w");
    if(!$logFile){
      echo "$$$";
      return;
    }
    fwrite($logFile, "");
    fclose($logFile);
    echo "ok";
  }*/

  /* 任务迁移 */
  else if($action == "taskTransfer"){
    $opCode = $_GET["op_code"];
    $clusterName = $_GET["cluster_name"];
    $groupId = $_GET["group_id"];
    $id = $_GET["id"];
    $user = $_GET["user"];
    $nodeS = $_GET["node_s"];
    $nodeO = $_GET["node_o"];
    $db = new SQLiteDB();
    $ret = $db->query("select * from task where task_id='".$id."'");
    $isExists = 0;
    if($row = $ret->fetchArray(SQLITE3_ASSOC)){
      $isExists = 1;
    }
    if($isExists == 1 && $row["node_name"] == $nodeO){
      $ret = $db->query("select ip from node where name='$nodeS'");
      $row = $ret->fetchArray(SQLITE3_ASSOC);
      $ip = $row["ip"];
      $db->close();
      exec("$execBasePath/task_operation/task_client $opCode $clusterName $user $groupId $id $ip",$res);
      echo $res[0];
    }else{
      $db->close();
      echo "mul-op-error";
    }
  }
  
  /* 命令无效 */
  else{
    echo "action failed";
  }

?>
