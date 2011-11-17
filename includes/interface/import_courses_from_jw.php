﻿<?php/** * the interface to import jw course list to database *  * @author Qiang Runwei <qiangrw@gmail.com> * @version 1.0 * @copyright LocalsNake Net League */	session_start();	require_once 'sns_fns.php';		$user_id = $_SESSION['user_id'];		$cookie_jar_index = 'cookie.txt';	$username = trim($_POST['username']);	$password = trim($_POST['password']);	if(!filled_out($_POST)){		echo '请授权用户名密码';		exit;	}		$login_url = "http://jwas3.nju.edu.cn:8080/jiaowu/login.do";	$params = 'userName='.urlencode($username).'&password='.urlencode($password);	$ch = curl_init();	curl_setopt($ch, CURLOPT_URL, $login_url);	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar_index);	curl_setopt($ch, CURLOPT_POST, 1);	curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 	ob_start();	curl_exec($ch);	curl_close($ch);	ob_clean();	 	$url = "http://jwas3.nju.edu.cn:8080/jiaowu/student/teachinginfo/courseList.do?method=currentTermCourse";	$ch2 = curl_init();	curl_setopt($ch2, CURLOPT_URL, $url);	curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookie_jar_index);	ob_start();	curl_exec($ch2);	curl_close($ch2);	$rs = ob_get_contents();	ob_clean();		/*# write the file 	#$fp = fopen('D:/test.txt','w');	#fwrite($fp,$rs);	#fclose($fp);*/		#$content = file_get_contents('D:/test.txt');			#read the html file		$matches = array();	$course_info = array();	$col_num = 6;	$content = preg_replace('/<br\s*\/>/',':',$content);	#print $content;	if(preg_match_all('/<td valign="middle">\s*([^<]+)\s*<\/td>/',$content,$matches,PREG_SET_ORDER) ) {		$i = 0;		foreach ($matches as $val) {			#echo "matched: " . $val[0] . "\n";			#echo "content: " . $val[1] . "\n";			$value = trim($val[1]);			#echo "content: " . $value . "\n";			if($i % $col_num == 0) {	$course_info[($i/$col_num)]['course_id'] = $value;}			if($i % $col_num == 1) {	$course_info[($i/$col_num)]['course_name'] = $value;}			if($i % $col_num == 2) {	$course_info[($i/$col_num)]['course_place'] = $value;}			if($i % $col_num == 3) {	$course_info[($i/$col_num)]['course_teacher'] = $value;}			if($i % $col_num == 4) {					//周四 第3-4节 1-17周  逸B-104				$times_places = preg_split('/:/',$value);				$course_info[($i/$col_num)]['course_time'] = '';				$flag = false;				$cur_place = "";				foreach ($times_places as $time_place) {					$elements = preg_split('/\s+/',$time_place);					$week = $elements[0];					$count = $elements[1];					$double = $elements[2];					$place = $elements[3];					if($flag) {							$course_info[($i/$col_num)]['course_time'] .= ":";						if(strcmp($double,"双周")) {							$course_info[($i/$col_num)]['course_name'] .= "(双周)";						}					}					$flag = true;					if(strcmp($place,$cur_place) != 0) {						$cur_place = $place;						$course_info[($i/$col_num)]['course_place'] .= "$cur_place  ";					}					switch($week){						case '周一': 							$course_info[($i/$col_num)]['course_time'] .= '1-';							break;						case '周二': 							$course_info[($i/$col_num)]['course_time'] .= '2-';							break;						case '周三': 							$course_info[($i/$col_num)]['course_time'] .= '3-';							break;						case '周四': 							$course_info[($i/$col_num)]['course_time'] .= '4-';							break;						case '周五': 							$course_info[($i/$col_num)]['course_time'] .= '5-';							break;						case '周六': 							$course_info[($i/$col_num)]['course_time'] .= '6-';							break;						case '周日':							$course_info[($i/$col_num)]['course_time'] .= '7-';							break;					}					if( preg_match('/第(\d+-\d+)/',$count,$count_matches) ){						$course_info[($i/$col_num)]['course_time'] .= $count_matches[1];					}				}			}			if($i % $col_num == 5) {	$course_info[($i/$col_num)]['course_type'] = $value;}			$i ++;		}	}	#print_r($course_info);	#save the info to database	$conn = db_connect();	$conn->autocommit(FALSE);	$conn->query("DELETE FROM sns_jw_course_info WHERE user_id = $user_id");	for($i=0;$i<count($course_info);$i++) {		$course_id = $course_info[$i]['course_id'];		$course_name = $course_info[$i]['course_name'];        $course_place = $course_info[$i]['course_place'];        $course_time = trim($course_info[$i]['course_time']);        $course_type = $course_info[$i]['course_type'];		$course_teacher = $course_info[$i]['course_teacher'];				$query = "INSERT INTO sns_jw_course_info (course_id,course_name,course_time,course_place,course_type,user_id,course_teacher)			VALUES		 ('$course_id','$course_name','$course_time','$course_place','$course_type',$user_id,'$course_teacher')";		$conn->query($query);		if($conn->affected_rows != 1) {			echo '导入失败:',"$query";			$conn->rollback();			exit;		}	}	$conn->commit();	echo 1;	#echo json_encode($course_info);?>