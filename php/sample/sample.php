<?php
define('API_KEY', '***');				// API key, provided by Feerik
define('API_LOCALE', 'en');				// Locale used for all texts returned by the API

// Includes the extended client-side API and instantiates the object
require_once('../src/EredanExtendedAPI.php');
$E = new EredanExtendedAPI(API_KEY, API_LOCALE);

// Fetches aggregates data from the REST API
$aggregates = $E->getAggregatesData();
?>

<form action='?' method='post' id='form'>

<?php
// Displays one SELECT form element for each available aggregate with fetched data
foreach($aggregates as $type => $entries){
	echo "<select name='".$type."'>";
	echo "<option value=0>".$type."</option>";
	foreach($entries as $datas){
		$selected = '';
		if($datas['id'] == $_POST[$type])
			$selected = 'selected="selected"';
		echo "<option value=".$datas['id']." $selected>".$datas['name']."</option>";
	}
	echo "</select>";
}
?>
<input type='submit'>
</form>

<?php

// Handles form requests
if(!empty($_POST)){
	$filters = array();
	foreach($_POST as $k=>$v){
		if(in_array($k,array_keys($aggregates)) && !empty($v)){
			$filters[$k]['operator'] = "OR";
			$filters[$k]['list'] = array($v);
		}
	}
	$start = 0;
	if(isset($_REQUEST['start']))
		$start = $_REQUEST['start'];
	$nb	   = 4;
	$params = array( 
					 'filters' => $filters,
					 'query_params'=>array('start'=>$start,'count'=>$nb)
					);
					
	$cards 		 = $E->getData('cards',$params);
	$pagination  = $E->getPagination('cards');
	displayCards($cards,$pagination);
}

// Builds HTML to display cards
function displayCards($cards,$pagination){
	echo "<table>";
	foreach($cards as $v){
		echo "<tr><td>".$v['id']."</td><td>".$v['name']."</td><td><img src='".$v['visuals']['small']."'></td></tr>";
	}
	echo "</table>";
	if(isset($pagination['prev'])){
		echo "<input type='button' value='<<' onclick='document.getElementById(\"form\").action=\"?start=".$pagination['prev']."\";document.getElementById(\"form\").submit();'>";
	}
	if(isset($pagination['next'])){
		echo "<input type='button' value='>>' onclick='document.getElementById(\"form\").action=\"?start=".$pagination['next']."\";document.getElementById(\"form\").submit();'>";
	}
}
?>
