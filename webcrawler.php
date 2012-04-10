<?php

/*
 Return an array of schools from the ncaa.org site.
 The array contains the ncaa.org id as the key and the name as the value
 Also outputs id, schoolname as csv.
*/
function get_schools($url)
{
    $schools = array();
    $dom = new DOMDocument('1.0');
    @$dom->loadHTMLFile($url);
    $sels = $dom->getElementsByTagName('select');
    foreach ($sels as $element) {
		$name = $element->getAttribute('name');
		if ($name == 'searchOrg') { //found list of schools
			while($element->hasChildNodes()){
				$opt = $element->removeChild($element->childNodes->item(0));
				//$name = $opt->nodeValue;
				$name = $opt->textContent;
				$id = $opt->getAttribute('value');
				$schools[$id] = $name;
				echo $id,",",$name,PHP_EOL;
			}
		}
	}
	return $schools;
}

/*
 Outputs csv of all players for one team for one year.
 
*/
function get_teamdata($schoolid, $year, $url)
{
	$args = array('sortOn' => '0',
		'doWhat' => 'display',
		'playerId' => '-100',
		'coachId' => '-100',
		'orgId' => $schoolid,
		'academicYear' => $year,
		'division' => '1',
		'sportCode' => 'MBA', //men's baseball
		'idx' => '');
	$args_string = '';
	//url-ify the data for the GET
	foreach($args as $key=>$value) { $args_string .= $key.'='.$value.'&'; }
	$args_string = rtrim($args_string,'&');
	$full_url = $url.'?'.$args_string;


	//open connection
	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL,$url.'?'.$args_string);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	
	//execute first get - need to do this to initiate a session (should really use the same session fot the entire program)
	$result = curl_exec($ch);
	preg_match('/^Set-Cookie: (.*?);/m', $result, $m);
	$cookie = $m[1]; //This is the jsession id. NCAA.org needs this set to retrieve data, so we need ot make another request
	//TODO: Save this id for future requests in othe parts of app.
	//var_dump(parse_url($m[1]));
	
	// Set the jsessionid, and execute the request
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	$result = curl_exec($ch);
	// At this point the $result contains a full page of one team's data for the given year

	//echo $result;
	
	//close connection
	curl_close($ch);
	
	//now that we have the file, get the stuff out of it
	$dom = new DOMDocument('1.0');
	@$dom->loadHTML($result);
	
	//statstable
	$tables = $dom->getElementsByTagName('table');
	foreach ($tables as $table){
		$clazz = $table->getAttribute('class');
		if ($clazz == 'statstable') { 
			$statstable = $table; // There are 2 statstables, choose the second (last)
		}
	}
	// if $statstable is undefined, then this school has no baseball data
	if (!isset($statstable)) {return;}
	
	//Parse the table
	$data_row = 4; //the first row that contains useful data is row 4 so ignore the other rows
	echo "schoolid,name,class,season,position,games,atbats,r,hits,avg,";
	echo "double,triple,homer,tb,slugpct,rbi,sb,sba,bb,so,hbp,sh,sf,";
	echo "app,gs,cg,w,l,sv,ShO,IP,H,R,ER,BB,SO,ERA".PHP_EOL; //csv header
	while($statstable->hasChildNodes()){
		$row = $statstable->removeChild($statstable->childNodes->item(0));
		$data_row -= 1;
		$player = array();
		$player["schoolid"] = $schoolid;
		if ($data_row <=0) { //hard-coding stats
			$player["name"] = "\"".cell_value($row->removeChild($row->childNodes->item(0)))."\""; //double quote for csv
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["class"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["season"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["position"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["games"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["atbats"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["hits"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["avg"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["double"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["triple"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["homer"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["tb"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["slugpct"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["rbi"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["sb"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["sba"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["bb"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["so"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["hbp"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["sh"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["sf"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["app"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["gs"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["cg"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["w"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["l"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["sv"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["ShO"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["IP"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["H"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["R"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["ER"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["BB"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["SO"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node
			$player["ERA"] = cell_value($row->removeChild($row->childNodes->item(0)));
			$row->removeChild($row->childNodes->item(0)); // text node

			foreach ($player as $key => $value) {
				echo $value;
//				if ($key !== "ERA") { //must be last property
					echo ",";
//				}
			}
			echo PHP_EOL;
		}	
	}

}

function cell_value($cell){
	return ltrim(rtrim($cell->nodeValue));
}

get_teamdata('67', '2011', "http://web1.ncaa.org/stats/StatsSrv/careerteam"); //This will print csv for 1 school
//get_schools("http://web1.ncaa.org/stats/StatsSrv/careersearch"); //This will print csv for schools

/*
//This will retrieve all schools and then output all players for all schools, as csv
$schools = get_schools("http://web1.ncaa.org/stats/StatsSrv/careersearch");
foreach ($schools as $key => $value) {
	get_teamdata($key, "2011", "http://web1.ncaa.org/stats/StatsSrv/careerteam");
}
*/