<?php
defined('is_running') or die('Not an entry point...');

class RoomStatus {
  var $Year;
  var $Month;
  var $NumberOfRooms=3;
  var $data;
  var $addonPathData;
  
  static $months=array("J&auml;nner","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember");
  static $rooms=array("Zimmer 1","Zimmer 2","Ferienwohnung");
  
  function RoomStatus(){
    global $addonPathData;
    $this->addonPathData=$addonPathData;
    
    $this->Month=date('m');
    $this->Year=date('Y');
    $this->data=array();
    
    if(!is_dir($this->addonPathData)){
      mkdir($this->addonPathData,0755,true);
    }

    $this->LoadYearMonth();
    $this->LoadStatus();
    
    #Bei Bedarf Änderung durchführen
    if(common::LoggedIn()){
      if(isset($_GET['croom']) && isset($_GET['cyear']) && isset($_GET['cmonth']) && isset($_GET['cday'])){
	$this->ToogleStatus($_GET['croom'],$_GET['cyear'],$_GET['cmonth'],$_GET['cday']);
      }
    }
    
    $this->SaveStatus();
    $this->GenerateTable();
  }
  
  function LoadYearMonth(){
    if(isset($_GET['year'])){
      if(is_numeric($_GET['year'])){      
	if($_GET['year']>=(date('Y')-1) && $_GET['year']<=(date('Y')+1)){
	  $this->Year=$_GET['year'];
	}
      }
    }

    if(isset($_GET['month'])){
      if(is_numeric($_GET['month'])){
	if($_GET['month']>=1 && $_GET['month']<13){
	  $this->Month=$_GET['month'];
	}
      }
    }
  }

  function GenerateTable(){
    $DaysInMonth=date('t',mktime(0,0,0,$this->Month,1,$this->Year));

    #Ausgabe generieren
    $table='<h2 class="RoomTitle">Room Bookings</h2><p class="RoomTime">'.self::$months[$this->Month-1].' '.$this->Year.'</p>';
    $table=$table.$this->GenerateLinks().'<table id="RoomTable"><tr>';

    #Zimmerbelegung erzeugen
    for($j=0;$j<=$this->NumberOfRooms;$j++){
	$table=$table.'<tr>';
	
	#Alle Tage durchgehen
	for($i=0;$i<=$DaysInMonth;$i++){
	  if($i==0){ 
	    if($j!=0){
	      #Zimmer Überschrift erzeugen
	      $table=$table.'<td><b>'.self::$rooms[$j-1].'</b>&nbsp;&nbsp;</td>';
	    } else {
	      $table=$table.'<td></td>';
	    }
	  } else {
	    if($j==0){
	      #Tabellen Überschrift erzeugen
	      $table=$table.'<td  class="RoomHeader"> '.$i.'</td>';
	    } else {
	      $class="RoomStateFree";
	      
	      if($this->CheckRoom($j,$i)>=0){
		$class="RoomStateTaken";
	      }
	      
	      if(common::LoggedIn()){
		$table=$table.'<td><a href="?croom='.$j.'&cyear='.$this->Year.'&cmonth='.$this->Month.'&cday='.$i.'&year='.$this->Year.'&month='.$this->Month.'"><div class="RoomState '.$class.'"></div></a></td>';
	      } else {
		$table=$table.'<td><div class="RoomState '.$class.'"></div></td>';
	      }
	    }
	  }
	}
	
	$table=$table."</tr>";
    }

    $table=$table."</table>";

    echo $table.$this->GenerateLabel();
  }
  
  function GenerateLabel(){
    return '<table class="RoomLabel"><tr><td><div class="RoomState RoomStateFree"></div></td><td><b>frei</b></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><div class="RoomState RoomStateTaken"></div></td><td><b>belegt</b></td><tr></table>';
  }
  
  function GenerateLinks(){
    $output='<div class="RoomNavigation">';
    $m=1;
    
    foreach(self::$months as $month){
      $year=date('Y');
      
      if($m<date('n')){
	$year++;
      }

      if($m>1){
	$output=$output." - ";
      }
      
      $output=$output.'<a href="?year='.$year.'&month='.$m.'">'.$month."</a>";

      $m++;
    }
  
    return $output."</div>";
  }

  function CheckRoom($room,$day){
    $LineNumber=0;

    foreach($this->data as $line){
	if($room==$line[0] && $this->Year==$line[1] && $this->Month==$line[2] && $day==$line[3]){
	  return $LineNumber;
	}
	
	$LineNumber++;
    }

  return -1;
  }

  function ToogleStatus($room,$year,$month,$day){
    if(is_numeric($room) && is_numeric($year) && is_numeric($month) && is_numeric($day)){
      if($room>=1 && $room<=$this->NumberOfRooms){
	if($year>=(date('Y')-1) && $year<=(date('Y')+1)){
	  if(checkdate($month,$day,$year)){
	      //Prüfen, ob bereits vorhanden, wenn ja löschen
	      $LineNr=$this->CheckRoom($room,$day);
	      
	      if($LineNr>=0){
		unset( $this->data[$LineNr]); #Löschen
	      } else {
		 $this->data[]=array($room,$year,$month,$day); #Eintragen
	      }
	  }
	}
      }
    }
  }

  function LoadStatus(){
    $data=array();
  
    if (($handle = fopen($this->addonPathData."/data_".$this->Year."_".$this->Month.".csv", "r")) !== FALSE) {
	while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
	  $data[]=$line;
	}
	fclose($handle);
    }
    
    $this->data=$data;
  }

  function SaveStatus(){
    if (($handle = fopen($this->addonPathData."/data_".$this->Year."_".$this->Month.".csv", "w+")) !== FALSE) {
      foreach($this->data as $line){
	fputcsv($handle,$line);
      }
      fclose($handle);
    }
  }
}

?>
