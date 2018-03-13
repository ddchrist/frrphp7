<?php
// only open session if not already open
//if(!isset($_SESSION['username'])) include("login_headerscript.php");

define('_DEBUG', False);  // Set False for NO debug info
//define('_DEBUG', True);  // Set True for debug info
//define('HTML_INPUT', 1);  // show input box in html

// Remove entry if requested
if (isset($_GET["DelKey"])) {
   $obj=new TableHandling($_GET["DocVal"],$_GET["DocKey"],$_GET["col"],$_GET["db"]);
if(_DEBUG) {
   Debug("Del_Collection",$_GET["col"]);
   Debug("Del_DataBase",$_GET["db"]);
}
   $obj->Name=$_GET["name"];
   $obj->delete_entry($_GET["DelKey"], $_GET["DelKeyVal"]);
   $ReturnScript=$_GET["rs"];
   header("location:$ReturnScript");
   exit;
}

class DB_GetCollection {
   public $DataBase="Projects";  // Deafult database
   public $CollectionName="COOPANS"; // Deafult collection name
   public $collection; // returned mongoDB collection.
   public $db;  // Database object
   
   
   public function get_collection() {
if(_DEBUG) {
   Debug("DB_GetCollection_Collection",$this->collection);
   Debug("DB_GetCollection_DataBase",$this->DataBase);
}
//      $m = new MongoClient();
//      $this->db = $m->selectDB($this->DataBase);
//      return $this->collection = new MongoCollection($this->db, $this->CollectionName);
   $m = new MongoDB\Client();
   $this->db = $m->selectDatabase($this->DataBase);
//   var_dump($this->db);
   return $this->collection = $m->selectCollection($this->DataBase, $this->CollectionName);
	  }
}
class db_handling extends DB_GetCollection {
// Given the key, the value in the document is returned
// The document to be searched must be initiated in the constructor e.g.
// $query = array('WPNum' => "06.10", "Backup" => array('$exists' => false))
// will look for document "06.10" where the key "backup" does not exist.   
// Use $obj=new db_handling($MyQuery,""); to avoid preconfigured query
   public $query;
   public $cursor;
   public function __construct($query,$NoKey) {
      $this->CollectionName=$_SESSION["ProjectName"];
      if($NoKey=="") $this->query = $query;
      else $this->query = array('$and' => array($query,array($NoKey => array('$exists' => false))));
//print_r($query);
//        echo 'The class "', __CLASS__, '" was initiated!<br />';   
   }
   
   public function GetLastError() {
      $Error=$this->db->lastError();
      return $Error["err"];
   }

   public function insert($cursor) {
      $this->cursor=$cursor;
      $collection = $this->get_collection(); 
      $collection->insert($this->cursor);
      return;
   }
   public function remove() {
      $collection = $this->get_collection(); 
      $collection->remove($this->query);
      return;
   }
   public function drop_collection() {
      $collection = $this->get_collection(); 
      $collection->drop();
      return;
   }

   public function get_cursor_find() {
      $collection = $this->get_collection(); 
      $this->cursor = $collection->find($this->query);
      return;
   }
   public function get_cursor_find_sort($order=1,$key="WPNum",$limit=false) {
      #if order is -1 then sorting will be reverse
      $collection = $this->get_collection();
	  //$options = sort(array($key => $order));
      if($limit) $this->cursor = $collection->find($this->query)->sort(array($key => $order))->limit($limit);
      else $this->cursor = $collection->find($this->query, array($key => $order));//sort solved
      return;
   }
   public function get_cursor_find_sort_array($arr,$limit=false) {
      // E.g. $arr=array("StartDate"=>1,"WPNum"=>1) - sort Date then WPNum
      #if order is -1 then sorting will be reverse
      $collection = $this->get_collection();
      if($limit) $this->cursor = $collection->find($this->query)->sort($arr)->limit($limit);
      else $this->cursor = $collection->find($this->query)->sort($arr); 
      return;
   }
   public function get_cursor_findone() {
      $collection = $this->get_collection(); 
      if(_DEBUG) Debug("get_cursor_findone_Collection",$this->query);
      $this->cursor = $collection->findone($this->query);
      if(_DEBUG) Debug("get_cursor_findone_DataBase",$this->cursor);
      return;
   }
   public function get_value($key) {  // returns "" if key does not exist otherwise the value
      $this->get_cursor_findone();
      if(isset($this->cursor[$key])) return $this->cursor[$key];
      else return "";
   }
   public function delete_key($key) {
      $this->get_cursor_findone();
      unset($this->cursor[$key]);
      $this->save_collection(); 
      return;
   }
   public function save_collection($cursor='') {  // makes the parameter optional
      if($cursor!='') $this->cursor=$cursor;
      $coll=$this->collection;
      $coll->save($this->cursor); // options ?
      return;
   }
   public function sum_of_keys($key) {  
// From the given heading level $WPNum, it will find all documents below that level
// and add up indicated $Key. Also the value if $WPNum is added. The value is returned.
// Eg. $WPNum="05.10" and the following exists below "05.10.10", "05.10.20". $Key="Cost"
// Return = "Cost" of "05.10" + "Cost" of "05.10.10" + "Cost" of "05.10.20".
// $query must select all Nums to be added e.g.:
//    $obj=new db_handling(array('WPNum' => new MongoRegex("/^$DelWP/")),"Backup");
   $this->get_cursor_find();
   $Sum=0;
   foreach($this->cursor as $obj) {
      if(isset($obj[$key])) $Sum+=$obj[$key];
   }
      return $Sum;
   }
}

// *****************************************************'
class TableHandling {
   public $DocKey="WPNum";  // Key of documents of where to store table
   public $DocVal;  // Value of key e.g. WP '02.10' where table is to be stored
   public $Name="Table";    // Name of table in document. Each Entry in document will count this name e.g. Table000, Table001 etc.. to max 999.
   // E.g. $arrListDefinitions = {input=>22, text=>LBN, check=>set}
   public $obj;   // object of document where table is contained
   public $EntryPointer=0;  // points to current entry in table
   public $ReturnScript="test.php";  // script to return to after an entry is deleted
   public $arrHeaders;  // Headers for the table e.g. array("Name"=>"left", "Amount"=>"right", "Address"=>"center". So html alignment must be given.
   public $DataBase="Projects";
   public $CollectionName="COOPANS";
   public $ShowRemoveEntry=True; // If False, it will not be possible to delete item i.e. 'X' does not show in table.
   public $AddCheckBox=False;  // If True a checkbox will be added to the end of the entry
   public $arrHTMLfunctions=array();  // used to describe how listings should be presented as "input" or "text"
   public $SizeOfStrings=array();  // array to hold size of column strings in order to make table look nice / alignet
   public $FixDaysHours=True;  // Used when html inputs is inserted into to table and manhours and mandays must be corrected in relation to eachother. Set False if this convertion is not wanted.
   public $KeyToSort="";  // When a table is listed, this is the key (within the table) the table is sorted after e.g. 'Role'. If nothing is indicated, the first key of first array entry is used.

   public function __construct($DocVal,$DocKey="",$CollectionName="",$DataBase="") {
      if($DocKey!="") $this->DocKey=$DocKey;
      $this->obj=new db_handling(array($this->DocKey => $DocVal),"Backup");
      $this->DocVal=$DocVal;
      if($CollectionName!="") {
         $this->obj->CollectionName=$CollectionName;
         $this->CollectionName=$CollectionName;
      }
      if($DataBase!="") {
         $this->obj->DataBase=$DataBase;
         $this->DataBase=$DataBase;
      }
      $this->obj->get_cursor_findone();
      if(_DEBUG) Debug("TableHandling__construct",$this->obj->cursor);
      // if findone() does not return anything, create a new cursor
      if($this->obj->cursor == Null) {
         $this->obj->cursor[$DocKey]=$DocVal;
      }
   }  
   public function get_entry() {  // Gets current table entry
      // Given correct EntryPointer, the array of that entry is
      // returned. Otherwise an empty string is returned
      $TableName=$this->Name;
      if(isset($this->obj->cursor[$TableName])) {
         $arrTable=$this->obj->cursor[$TableName];
    
         // If key to sort table after is not indictaed then get key as
         // first key used in multi array of the first array entry
         if($this->KeyToSort=="") {
            $FirstEntry=$arrTable[0];
            foreach($FirstEntry as $key=>$val) {
               break;
            }
            $this->KeyToSort=$key;
         }
         usort($arrTable, build_sorter($this->KeyToSort)); // calls the function pricesort
      }
      else return "";
      if(isset($arrTable[$this->EntryPointer])) {
         $Entry=$arrTable[$this->EntryPointer];
      }
      else $Entry=""; // returns empty string if entry does not exist.
      return $Entry;   
   }
   public function get_next_entry() {
      $this->EntryPointer++;
      return($this->get_entry());
   }
   public function get_header_line() {
      if(isset($this->arrHeaders)) {
         $html="<tr>";
         foreach($this->arrHeaders as $k=>$v) {
            $html.='<th align="'.$v.'">';
            $html.=$k;
            $html.="</th>";
         }
         $html.="</tr>";
         return $html;
      }
      else return;
   }
   public function get_table_as_html() {
      $this->GetMaxSizesOfEntry();  // get information to make the table look nice
      $this->EntryPointer=0;

      $Entry=$this->get_entry();  // e.g. Array ( [Role] => ACC01_EAST [ReqRes] => [ResOwn] => LBN [Ch_Roles] => F )
      // If no table/entries is/are defined return empty
      if($Entry=="") return "";
      $html="<table>";
      $row=0;
      $html.=$this->get_header_line();
      // Get primary key of entry and value (used to delete entry)
      foreach($Entry as $DelKey=>$DelVal) {
         break; // first key in multiarray MUST be primary key
      }
      do {
         $html.="<tr>";
         $col=0;
         foreach($Entry as $k=>$v) {
            if($k==$DelKey) $DelVal=$v;   // Delval point to last stored value of main key. Used to delete table entries             
            if(is_numeric($v)) $html.='<td align="right">';
            else $html.="<td>";
            $TagName=str_pad(strval($row),3,"0",STR_PAD_LEFT)."_".str_pad(strval($col),3,"0",STR_PAD_LEFT);
            if(isset($this->arrHTMLfunctions[$col])) {
               if($this->arrHTMLfunctions[$col]==HTML_INPUT) {
                  $html.='<input type="text" name="'."in_".$TagName.'" value="'.$v.'" size="'.$this->SizeOfStrings[$col].'">';
               }
               // http://www.wastedpotential.com/html-multi-checkbox-set-the-correct-way-to-group-checkboxes/
               elseif($this->arrHTMLfunctions[$col]==HTML_CHECK) {
                  // Get information on checkbox to look up check status
                  $tt=0;
                  foreach($this->arrHeaders as $ke=>$va) {
                     if($col==$tt++) break;
                  }
                  if($k=="Ch_".$ke) {
                     if($v=="T") $check='checked="checked"';
                     else $check="";
                  }                  
                  $html.='<input type="checkbox" name="chk_group[]" value="'.'ch_'.$TagName.'"'.$check.'/>';
               }
               elseif($this->arrHTMLfunctions[$col]==HTML_TEXT) {
                  // List table content as text       
                  $html.=$v;
               }
            }
            else if(substr($k,0,8)=="checkbox") $html.='<input type="checkbox" name="chk_group[]" value="'."ch_".$TagName.'"/>';
            else if(substr($k,0,4)=="text") {
               $html.=$v;
            }
            else {
               //echo "class_lib.php - Table property not known: $k";
               //exit;
               if(is_numeric($v)) $v=number_format($v, 1, '.', '');
               $html.=$v;
            }
            $html.="</td>";
            $col++;
         }
         if($this->AddCheckBox) {
            $html.="<td>";
            $html.='<input type="checkbox" name="check[]" value="'."ch".str_pad(strval($row),3,"0",STR_PAD_LEFT).'">';
            $html.="</td>";
         }

         if($this->ShowRemoveEntry) {  // only make deletion of entry possible if requested
            $html.="<td>";
            $html.='<a href="class_lib.php?DelKey='.$DelKey.'&DelKeyVal='.$DelVal.'&DocKey='.$this->DocKey.'&DocVal='.$this->DocVal.'&db='.$this->DataBase.'&col='.$this->CollectionName.'&rs='.$this->ReturnScript.'&name='.$this->Name.'">X</a>';
            $html.="</td>";
         }
         $html.="</tr>";
         $Entry=$this->get_next_entry();
         $row++;
      }
      while($Entry!="");
      $html.="</table>";
      return $html;
   }

   public function GetMaxSizesOfEntry() {
      // Creates $this->SizeOfStrings[] with max sizes of each table column.
      $Entry=$this->get_entry();
      // If no table/entries is/are defined return empty
      if($Entry=="") return "";
      do {
         $col=0;
         foreach($Entry as $k=>$v) {
            if(!isset($this->SizeOfStrings[$col])) {
               $this->SizeOfStrings[$col]=0;
            }
            if($this->GetSizeOfString($v) > $this->SizeOfStrings[$col]) {
               $this->SizeOfStrings[$col]=$this->GetSizeOfString($v);
            }
            $col++;
         }
         $Entry=$this->get_next_entry();
      }
      while($Entry!="");
   }

   public function GetSizeOfString($v) {
      if(strlen($v)<5) return 5;
      return strlen($v)+2;

   }
   public function insert_input_to_table() {
      // will insert table initiated html inputs from get_table_as_html()
      // into the table 'Name'
      /* Organisation of table Ressources (example)
      
      [Ressources] => Array (
    [0] => Array (
        [Roles] => ACC_COOR
        [ReqRes] => HMU
        [ResOwn] => TAG
        [ManHours] => 7.4
        [ManDays] => 1
        [AllRes] => HMO
    )
    [1] => Array (
        [Roles] => ACC_EAST01
        [ReqRes] => 
        [ResOwn] => JHR
        [ManHours] => 7.4
        [ManDays] => 1
        [AllRes] => SLN
    )
    [2] => Array (
        [Roles] => ACC_EXP_EAST
        [ReqRes] => BIH
        [ResOwn] => TSS
        [ManHours] => 7.4
        [ManDays] => 1
        [AllRes] => LER
    )
      
      */
      $this->GetMaxSizesOfEntry();  // get information to make the table look nice
      $this->EntryPointer=0;
      $Entry=$this->get_entry();  // e.g. Array ( [Role] => ACC01_EAST [ReqRes] => [ResOwn] => LBN [Ch_Roles] => F )
      // If no table/entries is/are defined return empty
      if($Entry=="") return "";

      $row=0;
      $arrMainKey=array(); // this is e.g. Ressources and add up the whole array to be stored into the DB
      do {
         $col=0;
         $strRow=str_pad(strval($row),3,"0",STR_PAD_LEFT);
         foreach($Entry as $k=>$v) {
            $strCol=str_pad(strval($col),3,"0",STR_PAD_LEFT);
            // input
            if(isset($_POST["in_".$strRow."_".$strCol])) {
               $Entry[$k]=strtoupper($_POST["in_".$strRow."_".$strCol]);
            }
            // checkbox
            elseif(isset($this->arrHTMLfunctions[$col])) {
               if($this->arrHTMLfunctions[$col]==HTML_CHECK) {
                  $Entry["$k"]="F";
                  if(isset($_POST['chk_group'])) {
                     $optionArray = $_POST['chk_group'];
                     $Entry["$k"]="F";
                     foreach($optionArray as $opt) {
                        if($opt=="ch_".$strRow."_".$strCol) {
                           $Entry["$k"]="T"; 
                        }
                     }
                  }
               }
            }
            $col++;
         }
         
         // FixDaysHours should only be activated when handling ressources
         if($this->FixDaysHours) $this->fix_mandays_manhours($Entry);
         $arrMainKey[]=$Entry;

         $Entry=$this->get_next_entry();
         $row++;
      }
      while($Entry!="");
      
      $TableName=$this->Name;
      $this->obj->cursor[$TableName]=$arrMainKey;
//print_r($TableName);
//print_r($arrMainKey);
//exit;
      $this->obj->save_collection();
   }

   private function fix_mandays_manhours(&$arr) {
      // Set $ManHours based on either input in ManHours or Mandays.
      // also correct "," to "." if typed incorrectly
      $arr["ManHours"]=str_replace(",",".",$arr["ManHours"]);
      $arr["ManDays"]=str_replace(",",".",$arr["ManDays"]); 
      if($arr["ManDays"]!="") $ManHours=floatval($arr["ManDays"])*7.4;
      else if($arr["ManHours"]!="") $ManHours=floatval($arr["ManHours"]);
      else $ManHours=0.0;
      $arr["ManDays"]=$ManHours/7.4;
      $arr["ManHours"]=$ManHours;
   }

   public function get_number_of_rows() {
      // will return number of rows in table 'Ressources'
      return sizeof($this->obj->cursor[$this->Name]);
   }
   public function get_number_of_columns() {
      // will return number of columns in table 'Ressources'
      // The number is calculated by taking the first record
      // in the table (index 0) and then count number of items in the array.
      $arr=$this->obj->cursor["Ressources"];
      $arr=$arr[0];
      return sizeof($arr);
   }
   public function get_total_from_table($HeaderName) {
   // e.g: if $HeaderName="ManDays" it returns the sum of mandays of the table
      $Total=0.0;
      foreach($this->obj->cursor[$this->Name] as $obj) {
         if(isset($obj[$HeaderName])) $Total+=floatval($obj[$HeaderName]);
      }
      return ($Total);
   }

   public function delete_entry($Key, $Val) {
      // Will search for the $Key $Val indicated and delete the whole entry
      $arrTable=$this->obj->cursor[$this->Name];
      foreach($arrTable as $arrK=>$multiarr) {
         if($Val == $multiarr[$Key]) {
            unset($arrTable[$arrK]);
            $arrTable=array_values($arrTable);
            $this->obj->cursor[$this->Name]=$arrTable;
            $this->obj->save_collection();
            return;
         }
      }
      // $Entry is a number between 0 and 999 pointing to index in array.
//      $arrTable=$this->obj->cursor[$this->Name];
      // subtrack manhours of entry from total manhours
//      if(isset($arrTable[$Entry]["ManHours"])) {
//         $this->obj->cursor["ManHours"]-=$arrTable[$Entry]["ManHours"];
//      }
      // delete entry from collection
//      unset($arrTable[$Entry]);
      // When an array is unset, the index order of the array stays static
      // so e.g. index 0 disappear. array_values($arrTable) will renumber
      // the array to normal index values i.e. 0,1,2,...
//      $arrTable=array_values($arrTable);
//      $this->obj->cursor[$this->Name]=$arrTable;
//      $this->obj->save_collection();
   }
   public function insert($arrContent) {
      $key=$this->Name;   // Name of table to store entry
      if(isset($this->obj->cursor[$key])) $arrTable=$this->obj->cursor[$key];
      else {
         $this->obj->cursor[$key]=array();
         $arrTable=array();
      }
      $arrTable[]=$arrContent;
      $this->obj->cursor[$key]=$arrTable;
      $this->obj->save_collection();
      return;
   }
   public function entry_in_table($arrEntry) {
   // Return True if given entry exist in the object table.
      if(!isset($this->obj->cursor[$this->Name])) return False;
      $arrDB=$this->obj->cursor[$this->Name];   // Get table from cursor
      foreach($arrDB as $i) {
         if(array_diff($i,$arrEntry)==array()) return True;
      }
      return False;
   }
}

class CalenderHandling {
   public $NumOfWPs;  // Number of WPs to print on screen
   public $StartDate="9999-99-99"; // lowest start date of WPs
   public $EndDate="0000-00-00";   // highest end date of WPs
   public $NumToShow=60;  // the number of dates to show (year/week/day) - columns created
   public $ChrDate="-";  // character shown when WPs is within period
   public $ChrNoDate="&nbsp;";  // character shown when WPs is outside period
   public $CalenderView="month";  // The calender type showed - month, week, day
   private $obj; // object of WPs

   public function __construct(&$objList) {
      $this->obj=$objList;
      if(CheckDateFormat(GetAdminOnUser("CalenderStartDate"))) $this->StartDate=GetAdminOnUser("CalenderStartDate");
      else $this->StartDate=date("Y-m-d");
//echo "##".$this->StartDate;
//exit;
      if(GetAdminOnUser("CalenderColumns")!="") $this->NumToShow=GetAdminOnUser("CalenderColumns");
      if(GetAdminOnUser("CalenderView")!="") $this->CalenderView=GetAdminOnUser("CalenderView");

      // get number of WPs in collection to print
      $this->NumOfWPs=$this->obj->cursor->count();

      // Find lowest StartDate and highest EndDate of WPs to show
      foreach($this->obj->cursor as $o) {
         if(isset($o['StartDate']))
            if(CheckDateFormat($o['StartDate']))
               if($o['StartDate']<$this->StartDate) $this->StartDate=$o['StartDate'];
         if(isset($o['EndDate']))
            if(CheckDateFormat($o['EndDate']))
               if($o['EndDate']>$this->EndDate) $this->EndDate=$o['EndDate'];
      }
   }
   public function GetHtmlOfCalender(&$o) {
      // method can be 'month', 'week' or 'day'
//echo "StartDate=".$this->StartDate."<br>";
      $StartYear=substr($this->StartDate,0,4);  // 2012-11-12
      $StartMonth=substr($this->StartDate,5,2);
      $StartDay=substr($this->StartDate,8,2);
      $html="";
      for($t=0;$t<$this->NumToShow;$t++) {
         $html.='<td>';
         if(isset($o['StartDate']) && $o['EndDate']) {
            if($this->CalenderView=="month") {
               $strDate=date("Y-m", mktime(0, 0, 0, $t+1, 1, intval($StartYear)));  // e.g. 2012-11
               $start=substr($o['StartDate'],0,7); // e.g. 2012-11
               $end=substr($o['EndDate'],0,7);
//echo "strDate=$strDate  start=$start  end=$end <br>";
               if($strDate >= $start && $strDate <= $end) $html.=$this->ChrDate;
               else $html.=$this->ChrNoDate;
            }
            // $timestamp = gmmktime (0, 0 , 0 , 1, 4 + 7*($week - 1), $year);
            elseif($this->CalenderView=="week") {
                 $timeStart=strtotime($o['StartDate']." 00:00:00");
                 $timeEnd=strtotime($o['EndDate']." 00:00:00");
                 // php calculate sunday as first day of week. To overcome this the following line is used
                 // and combined with a translation of e.g. strtotime("2013W19"). Only a problem with beginning of week
                 // i.e. monday
                 $weeknum=date('W',strtotime($o['StartDate']." 00:00:00")); // find the correct European week number
                 $FirstDateOfWeekStart=date('Y-m-d',strtotime(substr($o['StartDate'],0,4)."W".$weeknum)); // convert date to first day of week
                 $FirstDateOfWeekEnd=date('Y-m-d',strtotime("sunday",$timeEnd));  // convert date to last day of week
//echo $timeStart." ".$timeEnd." ".$FirstDateOfWeekStart." ".$FirstDateOfWeekEnd." ".$weeknum."<br>";
                 $strDate=gmmktime (0, 0 , 0 , $StartMonth, 4 +7*($t - 1), $StartYear);
                 $start=gmmktime (0, 0 , 0 , substr($FirstDateOfWeekStart,5,2), substr($FirstDateOfWeekStart,8,2), substr($FirstDateOfWeekStart,0,4));
                 $end=gmmktime (0, 0 , 0 , substr($FirstDateOfWeekEnd,5,2), substr($FirstDateOfWeekEnd,8,2), substr($FirstDateOfWeekEnd,0,4));   // 
//echo $strDate." ".$start." ".$end." ".$o['EndDate']."<br>";
   
               if($strDate >= $start && $strDate <= $end) $html.=$this->ChrDate;
               else $html.=$this->ChrNoDate;
            }
            elseif($this->CalenderView=="day") {
               $strDate=date("Y-m-d", mktime(0, 0, 0, $StartMonth, $t, $StartYear));
               $start=$o['StartDate'];
               $end=$o['EndDate'];
               if($strDate >= $start && $strDate <= $end) $html.=$this->ChrDate;
               else $html.=$this->ChrNoDate;
           }
         }       
         $html.="</td>";
      }
//echo "<br>"; 
      return $html;
   }
}
function CheckDateFormat($d) {
   // make sure that date format is "yyyy-mm-dd"
   // Returns the format if correct otherwise an error text is returned contunated with the data format
   // If no format is given then return ""
   $d=trim($d);
   if($d=="") return "";
   // Convert e.g. 20140920 to 2014-09-20
   if(strlen($d)==8) $d=substr($d,0,4)."-".substr($d,4,2)."-".substr($d,6,2);

   if( substr($d,4,1)=="-" && substr($d,7,1)=="-" && strlen($d) <=10 && 
        checkdate(substr($d,5,2), substr($d,8,2), substr($d,0,4)) ) return $d;
   else return "**Incorrect:$d";
}
function ValidDateFormat($d) {
   // make sure that date format is "yyyy-mm-dd"
   // Returns the format if correct otherwise an error text is returned contunated with the data format
   // If no format is given then return ""
   $d=trim($d);
   if($d=="") return False;
   if( substr($d,4,1)=="-" && substr($d,7,1)=="-" && strlen($d) <=10 && 
        checkdate(substr($d,5,2), substr($d,8,2), substr($d,0,4)) ) return True;
   else return False;
}

function UserRightsChecks($rights,$DataBase="Projects") {
   // Return true if $right e.g. "Roles" is set as allowed for the user 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin",$DataBase);
   $obj->Name="Rights";
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $arrDB=$obj->obj->cursor[$obj->Name];
   foreach($arrDB as $i) {
      if(strtoupper($i["User"])==strtoupper($_SESSION['username'])) break;
   }
   if($i["Ch_$rights"]=="T") return True;
   else return False;
}
function UserRights($WPNum,$DataBase="Projects") {
   // Will return true if user has rights to change given WPNum
   // Works with UserRights table in collection Admin.
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin",$DataBase);
//echo $DataBase."  $WPNum";
//exit;

   $obj->Name="Rights";
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $arrDB=$obj->obj->cursor[$obj->Name];
//echo "WPNum=$WPNum<br>";
   // find user and he/shes rights
   $UserExist=False;
   foreach($arrDB as $i) {
      if(strtoupper($i["User"])==strtoupper($_SESSION['username'])) {
         $UserExist=True;
         break;
      }
   }
   if($UserExist==False) return False;
   // Get string that contains 'WriteLevel' from DB
   $rights=substr($i["WriteLevel"],1);  // skip string start "#"
   $arrRights=explode("&",$rights);
//print_r($arrRights);
   $arrRights=array_map('trim',$arrRights); // trim array
//print_r($arrRights);
   // return True for super user (all rights)
   if($arrRights[0]=="*") return True;  // SUPER USER
   // make '?' the same match as in $WPNum
   // Eg. $WPNum=21.01   $r=??.02, the result ($f) will be 21.02
//echo "<br>";
   foreach ($arrRights as $r) {
//echo $r." - ";
      $ModifiedWPNum="";
      // Replace any "?" in $r with the same positioned number
      // from $WPNum. E.g. $r=??.02 and $WPNum=21.04 then 
      // $ModifiedWPNum=21.02
      $l1=strlen($r);
      $l2=strlen($WPNum);
      // make $l the length of the shortest string
      if($l2>$l1) $l=$l1;
      else $l=$l2;
      for($t=0;$t<$l;$t++) {
         if(substr($r,$t,1)=="?") $ModifiedWPNum.="?";
         else $ModifiedWPNum.=substr($WPNum,$t,1);
      }

      // Perform match
      // If modified string is less in size as rules then no go
      if(strlen($ModifiedWPNum)<strlen($r)) continue; // e.g. ??.02 and input is 23
      // If rule matches submitted WPNum then it is ok
      if(substr($ModifiedWPNum,0,$l1)==substr($r,0,$l1)) return true; // ??.05 and input is
         // 24.05 or 23.05 or 22.05.01.01 etc..
   }
   return false;
}
function StoreAdminOnUser($key,$value,$DataBase="") {
   $obj=new db_handling(array('User' => $_SESSION["username"]),"Backup");
   if($DataBase<>"") $obj->DataBase=$DataBase; 
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   // if user does not exist, then create one
   if(!isset($obj->cursor[$_SESSION["username"]])) $obj->cursor['User']=$_SESSION["username"];
   $obj->cursor[$key]=$value;
   $obj->save_collection();
}
function GetAdminOnUser($key,$DataBase="") {
   $obj=new db_handling(array('User' => $_SESSION["username"]),"Backup");
   if($DataBase<>"") $obj->DataBase=$DataBase; 
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   if(isset($obj->cursor[$key])) return $obj->cursor[$key];
   else return "";
}

function generateSelect($name = '', $options = array(), $selected, $AutoSubmit=0) {
// example $options = array(
//    'TextCol' => "",
//    'Black' => "#000000",
//    'White' => "#FFFFFF");
    if($AutoSubmit) $AutoSubmit=' onchange=\'this.form.submit()\' ';
    else $AutoSubmit="";
    $html = '<select name="'.$name.'"'.$AutoSubmit.'>';
    foreach ($options as $option => $value) {
//echo $value."  ".$selected."<br>";
        if($value==$selected) $opt=' selected="selected"';
        else $opt="";
        $html.='<option'.$opt.' value="'.$value.'">'.$option.'</option>';
    }
    $html .= '</select>';
    return $html;
}

function IsKeySet($key, $WPNum) {
// Return True if indicated $key in document $WPNum contains a value
// Leading or trialing spaces are removed before evaluation.
      $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
      $value=trim($obj->get_value($key));
      if($value=="") return False;   // Key not set
      else return True;  // Key is set
}
function GetSuperLevelValue($key,$WPNum,$DataBase="Projects") {
// The function finds the super level and return the value of indicated key
// If no super level, it returns ""
// E.g. $WPNum = 10.02.20 and $key="PSPNaviair" (PSP number for Naviair)
//       It will look into '10.02.20' for 'PSPNaviair' and if it is not available,
//       it will look into '10.02' for 'PSPNaviair' and if it is not available
//       it will look into '10' and return the value if existing
   do {
      $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
      $obj->DataBase=$DataBase;
      $value=$obj->get_value($key);   // return "" if key does not exist in DB
      if(trim($value)!="") return $value;
      $WPNum=GetNextSuperLevel($WPNum);
   }
   while ($WPNum!="");
   return "";    // super level does not exist - return empty
}
function GetNextSuperLevel($WPNum) {
// E.g: If $WPNum='20.10.15' then it reuturns '20.10'
//      If $WPNum='20' then it returns "". 
   for($t=strlen($WPNum)-1;$t>=0;$t--) {
      if(substr($WPNum,$t,1)==".") return substr($WPNum,0,$t);
   }
   return "";
}
function DifferenceDates($day1,$day2) {
   // Will return unix time counter for difference: day2-day1
   $arr1=explode("-",$day1);
   $arr2=explode("-",$day2);
   $diff=mktime(0,0,0,$arr2[1],$arr2[2],$arr2[0]) - 
         mktime(0,0,0,$arr1[1],$arr1[2],$arr1[0]);
   return $diff;
}
function AddDateAndUnixTime($day,$UnixTime) {
   // Will return $day+$UnixTime as format e.g. 2012-01-25
   $arr=explode("-",$day);
   $Unix=mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
   return date("Y-m-d",$Unix+$UnixTime);
}
function ValidWPNum($WPNum) {
  if (preg_match("/^[0-9]+$/i", $WPNum) )
  {
    return true;
  }   
}
function ValidFullWPNum($WPNum) {
  if (preg_match("/^[0-9.]+$/i", $WPNum) )
  {
    $arr=explode(".",$WPNum);
    // check each element after exploded and if it is not a number something is wrong
    foreach($arr as $a) {
       if (!preg_match("/^[0-9]+$/i", $a) ) return False;
    }
    return true;
  }   
}

function Debug($number,$var) {
   // make sure that 'log' directory has "chmod a=wrx log"
   //file_put_contents('php://stderr', print_r($var, TRUE));
   file_put_contents("log/error.log", "#$number#", FILE_APPEND | LOCK_EX);
   file_put_contents("log/error.log", print_r($var, TRUE), FILE_APPEND | LOCK_EX);
   file_put_contents("log/error.log", "\n", FILE_APPEND | LOCK_EX);
   return;
}

function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return strnatcmp($a[$key], $b[$key]);
    };
}

// FindMinMaxOfSubWPs("");
// Recursively find min and max date values (using 'StartDate' and 'EndData') of
// level $WPNum and down and store values into super levels of Work packages
// If called with $WPNum="" then all WPs are processed.
// It starts from super level and work down through sublevels.
// Function is rather processing intensive and should only be called every time
// a date is changed and eventually also only for the relevant top work package level
// e.g. '23' '24' ... etc.
function FindMinMaxOfSubWPs($WPNum) {
   if($WPNum=="") {
      // match strings only containing super level (Header 1) numbers
      $query=array('WPNum' => new MongoRegex("/^[^.]*$/"));
   }
   else {
      // match string starting with WPNum and then only one sublevel down.
      $query=array('WPNum' => new MongoRegex("/^$WPNum\.[^.]*$/"));
   } 
   $obj=new db_handling($query,"");
   $obj->get_cursor_find_sort();
   if($obj->cursor==NULL) return false;  // no sublevel numbers found
   $min="9999-99-99";
   $max="0000-00-00";
   foreach($obj->cursor as $o) {
//echo $o["WPNum"]."   ";
      if(!isset($o["WPNum"])) {
         echo "<br>WPNum does not exist in class_lib.php -> FindMinMaxOfSubWPs()<br>";
         print_r($o);
         exit;
      }
      $arrMinMax=FindMinMaxOfSubWPs($o["WPNum"]);
      // in case of subnumber DOES exist and it is returned with min/max values
      if($arrMinMax) {
         if($arrMinMax[0]<>"" && $arrMinMax[1]<>"") {
//            $Filter=array("WPNum"=>$o["WPNum"]);
//            $Update=array('$set'=>array("StartDate"=>$o["StartDate"]));
//            $obj->collection->update($Filter,$Update);
//            $Update=array('$set'=>array("EndDate"=>$o["EndDate"]));
//            $obj->collection->update($Filter,$Update);
            $o["StartDate"]=$arrMinMax[0];
            $o["EndDate"]=$arrMinMax[1];
            $obj->save_collection($o);  
         }
         if($arrMinMax[0]<>"" && $min>$arrMinMax[0]) $min=$arrMinMax[0];
         if($arrMinMax[1]<>"" && $max<$arrMinMax[1]) $max=$arrMinMax[1];
      }
      // in case of NO sub level headers
      else {
         if(isset($o["StartDate"]) && $o["StartDate"]<>"" && $min>$o["StartDate"]) $min=$o["StartDate"];
         if(isset($o["EndDate"]) && $o["EndDate"]<>"" && $max<$o["EndDate"]) $max=$o["EndDate"];
      }
   }
   if($min=="9999-99-99" && $max=="0000-00-00") {
      return false;  // no valid dates in DB
   }
   else {
      // If called from super level e.g. '23', '24', ... then store min max for super level
      if(!strpos($WPNum,".") && $WPNum<>"") {
         $obj=new db_handling(array('WPNum' => $WPNum),"");
         $obj->get_cursor_findone();
         $obj->cursor["StartDate"]=$min;
         $obj->cursor["EndDate"]=$max;
         $obj->save_collection();  
      }       
      return array($min,$max);
   }
}

function AddHTMLofWPToDB($WPNum,$DB="Projects",$site="res") {
   $html="";
   $obj=new db_handling(array("WPNum"=>$WPNum), "");
   $obj->DataBase=$DB;
   $obj->get_cursor_findone();

   if(!isset($obj->cursor["WPNum"])) {
      echo "WPNum is missing in class_lib.php function AddHTMLOfWPToDB()";
      exit;
   } 
   // **************************************
   // Get and fix text and background colour
   $TextCol="";
   if(isset($obj->cursor['TextCol'])) $TextCol=$obj->cursor['TextCol'];
   if($TextCol=="") $TextCol=GetSuperLevelValue('TextCol',$obj->cursor['WPNum'],$DB,$site);
   if($TextCol=="") $TextCol="#000000";
   $HighCol="";
   if(isset($obj->cursor['HighCol'])) $HighCol=$obj->cursor['HighCol'];
   if($HighCol=="") $HighCol=GetSuperLevelValue('HighCol',$obj->cursor['WPNum'],$DB,$site);
   if($HighCol=="") $HighCol="#FFFFFF";
   $obj->cursor['HTML_HighCol']=$HighCol;
   $HighCol="#HiCol#";
   $obj->cursor['HTML_TextCol']=$TextCol;
   $TextCol="#TxCol#";  // store text colour as tag so it can be replaced based on WP expired in relation to today
   // bgcolor="#FF0000"   style="color:#C00000"
   $TextCol=' style="color:'.$TextCol.'"';

   $obj->cursor['HTML_Col']=' nowrap="nowrap"'.$TextCol.' bgcolor="'.$HighCol.'"';
   $obj->cursor['HTML_ExpPlus']='<a href="'.$site.'_manage_wps.php?ExpColWP='.$obj->cursor['WPNum'].'">+&nbsp;</a>';
   $obj->cursor['HTML_ExpMinus']='<a href="'.$site.'_manage_wps.php?ExpColWP='.$obj->cursor['WPNum'].'">-&nbsp;</a>';
   $text=IndentHeadingNumber($obj->cursor['WPNum']);
   $obj->cursor['HTML_WPNum']='<a href="'.$site.'_manage_input_table.php?WPNum='.$text.'"'.$TextCol.'>'.$text.'</a>&nbsp;';

   // Substitute tag #W# with W+week number
   $Name=$obj->cursor['WPName'];
   if(isset($obj->cursor['StartDate'])) {
      $Name=str_replace("#W#","W".date('W', strtotime($obj->cursor['StartDate'])),$Name);
   }
   $obj->cursor['HTML_Name']='<a href="'.$site.'_manage_wps.php?Rename='.$obj->cursor['WPNum'].'"'.$TextCol.'>'.IndentHeadingName($obj->cursor['WPNum'],$Name)." </a>";
   $obj->cursor['HTML_Add']='&nbsp;<a href="'.$site.'_manage_wps.php?AddWP='.$obj->cursor['WPNum'].'"'.$TextCol.'>add</a>'."&nbsp;";
   $obj->cursor['HTML_Del']='<a href="'.$site.'_manage_wps.php?DelWP='.$obj->cursor['WPNum'].'"'.$TextCol.'>del</a>'."&nbsp;";
   $obj->cursor['HTML_Cop']='<a href="'.$site.'_manage_wps.php?CopyWP='.$obj->cursor['WPNum'].'"'.$TextCol.'>cop</a>'."&nbsp;&nbsp;";
   // StartDate
   if(isset($obj->cursor['StartDate']) and $obj->cursor['StartDate']<>"") {
      $DayOfWeek=date('D', strtotime($obj->cursor['StartDate']));  // e.g. Tue
      $WeekNum=date('W', strtotime($obj->cursor['StartDate']));
      $obj->cursor['HTML_StartDate']='<font face="courier">W'.$WeekNum.$DayOfWeek."</font> ".$obj->cursor['StartDate']."&nbsp;&nbsp;";
   }
   else $obj->cursor['HTML_StartDate']="";
   // EndDate
   if(isset($obj->cursor['EndDate'])) {
      $EndDate=$obj->cursor['EndDate'];
      if(isset($obj->cursor['Milestone']) && $obj->cursor['Milestone']=="T") {
            $EndDate="<u>".$EndDate."</u>";
      }
      $obj->cursor['HTML_EndDate']=$EndDate."&nbsp;&nbsp;";
   }
   else $obj->cursor['HTML_EndDate']="";
   $obj->save_collection();
}
function IndentHeadingNumber($string){
    return $string;
    $t=substr_count($string, '.');
    if($t==0) return $string; 
    $pos=strrpos($string, ".");   // position of last "."
    $LastNumber=substr($string,$pos+1);
    $string="";
    for($n=0;$n<$t;$n++) $string.="&nbsp;&nbsp;";
    $string.=$LastNumber;
    return $string;
}
function IndentHeadingName($HeadingNumber,$HeadingName){
    $t=substr_count($HeadingNumber, '.');
    if($t==0) return $HeadingName; 
    $HeadingNumber="";
    for($n=0;$n<$t;$n++) $HeadingNumber.="&nbsp;&nbsp;";
    $HeadingNumber.=$HeadingName;
    return $HeadingNumber;
}
function UpdateHTMLColoursofSubWPs($WPNum,$DB="Projects",$site="res") {
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/")),"Backup");
   $obj->DataBase=$DB;
   $obj->get_cursor_find();
   foreach($obj->cursor as $o) {
      AddHTMLofWPToDB($o["WPNum"],$DB,$site);
   }
}
// Make sure that key 'ManHours' in 'WPNum' has the same
// total as the individual resources
function AlignKeyManHoursWithResources($WPNum,$DB="Projects") {
   $obj=new db_handling(array('WPNum' => $WPNum),"");
   $obj->DataBase=$DB;
   $obj->get_cursor_findone();
   if(isset($obj->cursor['Ressources'])) {
      $arr=$obj->cursor['Ressources'];
      $ManHours=0.0;
      foreach($arr as $a) {
         $ManHours+=floatval($a["ManHours"]);
      }
      $obj->cursor['ManHours']=$ManHours;
      $obj->save_collection();
   }
}
?>
