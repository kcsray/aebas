<?php

// ----------------------------------------------------------------------------------------

if(isset($_POST['emp_code'])   && $_POST['emp_code']='emp_code' ){
require "config.php";
 
$sql=" SELECT Att_ID,Emp_Name from emp where status=1 order by Emp_Name;";

//$sql=" SELECT e.Att_ID, e.Emp_Name  FROM emp e LEFT JOIN issued i ON e.Att_ID = i.emp_cd WHERE i.emp_cd IS NULL ORDER BY e.Emp_Name;";

if($result = $pdo->query($sql)){
	$data = "<option value='' disable> ----- Select One ------ </option>";
	 
				
	if($result->rowCount() > 0){
    
 
	while($row = $result->fetch()){
		 
		 
		$data .= "<option  value='". $row['Att_ID']."'>". $row['Emp_Name'] . "</option>";
		 
	
	}
	 
																									
	}else{
	   $data = "<option value=''> ----- No Data ------ </option>";
	}
	}
	echo $data;
	}
//------------------------------------------------------------------------------

if(isset($_POST['dev_code'])   && $_POST['dev_code']='dev_code' ){
require "config.php";
 
$sql=" select SLNO , SLNO from device where Isissued=0 order by SLNO ; ";
if($result = $pdo->query($sql)){
	$data = "<option value='' disable> ----- Select One ------ </option>";
	 
				
	if($result->rowCount() > 0){
    
 
	while($row = $result->fetch()){
		 
		 
		$data .= "<option  value='". $row['SLNO']."'>". $row['SLNO'] . "</option>";
	
	}
	 
																									
	}else{
	   $data = "<option value=''> ----- No Data ------ </option>";
	}
	}
	echo $data;
	}
//------------------------------------------------------------------------------
if(isset($_POST['off_code'])   && $_POST['off_code']='off_code' ){
require "config.php";
 
$sql=" SELECT  Location_CD,Office_Location  from office_loc ORDER BY Office_Location;  ";
if($result = $pdo->query($sql)){
	$data = "<option value='' disable> ----- Select One ------ </option>";
	 
				
	if($result->rowCount() > 0){
    
 
	while($row = $result->fetch()){
		 
		 
		$data .= "<option  value='". $row['Location_CD']."'>". $row['Office_Location'] . "</option>";
	
	}
	 
																									
	}else{
	   $data = "<option value=''> ----- No Data ------ </option>";
	}
	}
	echo $data;
	}
//------------------------------------------------------------------------------	
	?>
