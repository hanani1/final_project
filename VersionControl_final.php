<?php
class VersionControl {

    static $time = 0;

    // The main array, that holds all actions from the very beginning
    // Its key is a date and a value is actionInfo array
    private $actionsHistory_;
    private $recordsList_;
    
	public function __construct() {

        $this->actionsHistory_ = array();
        $this->recordsList_ = array();

	}

    public function registerAction($actionType, $itemId, $details = '') {
        $this->actionsHistory_[self::$time] = array($actionType, $itemId, $details);
        self::$time += 1;
    }

    public function printHistory() {

        foreach ($this->actionsHistory_ as $date => $actionInfo) {
            echo $date.' - ';

            echo $actionInfo[0].', ';
            echo $actionInfo[1].', ';

            foreach ($actionInfo[2] as $key => $value) {
                if ($actionInfo[0] === 'E') {
                    echo $key.'=>';
                }
                echo $value.',';
            }

            echo '<br>';
        }
    }

     //receives records list that has been built in getStateAt. receives a list of lists.
     public function printBuiltRecords(){
        foreach ($this->recordsList_ as $id => $record) {
            echo $id.' : ';
            foreach($record as $value) {
                echo $value.' ,';
            }
            echo '<br>';
        }
    }

    // function receives a date, keeps building the action history till it reaches the requested date. if not, builds it all.
    public function getStateAt($requestedDate) {
        $this->recordsList_ = array();
        foreach ($this->actionsHistory_ as $date => $actionInfo) {
            if($requestedDate < $date) {
                break;
            }
            // Inserts new row to the VC
            if($actionInfo[0] === 'I') {
                $this->recordsList_[$actionInfo[1]] = $actionInfo[2];
            }
            // Deletes the row with $actionInfo[1] as id
            elseif($actionInfo[0] === 'D') {
                unset($this->recordsList_[$actionInfo[1]]);
            }
            // Edits the row with $actionInfo[1] as id according to $actionInfo[2]
            elseif($actionInfo[0] === 'E') {
                foreach($actionInfo[2] as $key=>$value) {
                    $this->recordsList_[$actionInfo[1]][$key] = $value;
                }
            }
        }
    }

    public function buildStateFromTimeTillTime($start, $end) {
        foreach ($this->actionsHistory_ as $date => $actionInfo) {
            if($start > $date) {
                continue;
            }
            if($end < $date) {
                break;
            }
            // Inserts new row to the VC
            if($actionInfo[0] === 'I') {
                $this->recordsList_[$actionInfo[1]] = $actionInfo[2];
            }
            // Deletes the row with $actionInfo[1] as id
            elseif($actionInfo[0] === 'D') {
                unset($this->recordsList_[$actionInfo[1]]);
            }
            // Edits the row with $actionInfo[1] as id according to $actionInfo[2]
            elseif($actionInfo[0] === 'E') {
                foreach($actionInfo[2] as $key=>$value) {
                    $this->recordsList_[$actionInfo[1]][$key] = $value;
                }
            }
        }
    }

    public function getTimestamp() {
        return date("Y-m-d.H:i:s") . substr((string)microtime(), 1, 8);
    }
    
    // a function that given a type of change, prints all the changes that have occurred of the given type
    public function showChangesOfType($typeOfChange) {
        $listOfChanges = array();
        foreach ($this->actionsHistory_ as $date => $actionInfo) {
            if($actionInfo[0] === $typeOfChange){
                $listOfChanges[$actionInfo[1]] = $actionInfo[2];
            }
        }
        foreach($listOfChanges as $row => $changeList){
            echo $row.': ';
            foreach($changeList as $key => $value){
                echo $key.'-'.$value;
                echo ' ';
            }
            echo '<br>';
        }
    }

    public function getDiff($time1, $time2){
        echo 'showing diff for'.': '.$time1.'->'.$time2;
        echo '<br>';
        $differenceArray = array();
        for ($i = 0; $i < sizeof($this->recordsList_[$time1]); ++$i) {
            if($this->recordsList_[$time1][$i] != $this->recordsList_[$time2][$i]) {
                $differenceArray[$i] = $this->recordsList_[$time1][$i].'->'.$this->recordsList_[$time2][$i];
            } else{
                $differenceArray[$i] = 'null';
            }
        }
        foreach($differenceArray as $value){
            echo $value.', ';
        }
    }

    //**************************************************************4444444444444444444444444444444444444444************************************ */
    public function get_records_with_value_till_time($query) {
        preg_match('/(?<=value )\S+/i', $query, $match);
        $value = $match[0];
        preg_match('/(?<=column )\S+/i', $query, $match);
        $col = $match[0];
        preg_match('/(?<=time )\S+/i', $query, $match);
        $till_time = $match[0];
        if ($till_time > self::$time) {
            echo 'error - given time does not exist';
            echo '<br>';
        }
        $result = array();
        for($i = 0; $i <= $till_time; $i++) {
            $this->getStateAt($i);
            $current_records = array();
            foreach ($this->recordsList_ as $id => $record) {
                if ($this->recordsList_[$id][$col] == $value) {
                    array_push($current_records, $id);
                }
            }
            $result[$i] = $current_records;
        }
        foreach($result as $time => $record_ids) {
            echo 'at time ['.$time.'] the records with value ['.$value.'] where :';
            echo '<br>';
            foreach($record_ids as $id) {
                echo $id.',';
            }
            echo '<br>';
        }
    }

    //**************************************************************************33333333333333333333333333333333************************************* */
    public function check_when_record_is_deleted($query) {
        preg_match('/(?<=record_id )\S+/i', $query, $match);
        $id = $match[0];
        $result = array();
        foreach($this->actionsHistory_ as $time => $record) {
            if($record[0] == 'D' && $record[1] == $id) {
                array_push($result, $time);
            }
        }
        foreach($result as $value) {
            echo 'record: ['.$id.'] got deleted at time: ['.$value.']';
            echo '<br>';    
        }
        if(sizeof($result) == 0) {
            echo 'record: ['.$id.'] does not get deleted';
            echo '<br>';
        }
    }

    //******************************************************************2222222222222222222222222222222222222222******************************************************* */
    public function get_records_inserted_with_value($query) {
        preg_match('/(?<=column )\S+/i', $query, $match);
        $col = $match[0];
        preg_match('/(?<=value )\S+/i', $query, $match);
        $value = $match[0];
        $result = array();
        foreach($this->actionsHistory_ as $time => $action) {
            if ($action[0] == 'I' && $action[2][$col] == $value) {
                array_push($result, $action[1]);
            }
        }
        echo 'records inserted with value ['.$value.'] in column ['.$col.'] :';
        echo '<br>';
        foreach($result as $record_id) {
            echo $record_id.',';
        }
    }

    //*************************************************************************1111111111111111111111111111111111111************************************** */
    public function get_list_of_changes_for_col_since_time($query) {
        preg_match('/(?<=column )\S+/i', $query, $match);
        $col = $match[0];
        preg_match('/(?<=time )\S+/i', $query, $match);
        $time = $match[0];
        preg_match('/(?<=record_id )\S+/i', $query, $match);
        $record_id = $match[0];
        $this->getStateAt($time);
        $curr_value = $this->recordsList_[$record_id][$col];
        $max_time = max(array_keys($this->actionsHistory_));
        $curr_time = $time;
        $result = array();
        while ($curr_time <= $max_time) {
            $this->buildStateFromTimeTillTime($curr_time, $curr_time + 1);
            if($curr_value != $this->recordsList_[$record_id][$col]) {
                $curr_value = $this->recordsList_[$record_id][$col];
                array_push($result, $curr_time);
            }
            $curr_time++;
        }
        echo 'the times in which record ['.$record_id.'] at column ['.$col.'] changed since time ['.$time.'] are: ';
        echo '<br>';
        foreach($result as $value) {
            echo $value.', ';
        }
    }

    public function perform_closest_operation($query) {
        $distances = array();
        $distances[levenshtein($query, 'get list changes column time record_id')] = 1;
        $distances[levenshtein($query, 'get records inserted with value')] = 2;
        $distances[levenshtein($query, 'get when record_id deleted')] = 3;
        $distances[levenshtein($query, 'get records with value till time with column')] = 4;
        $this->choose_correct_operation($distances[min(array_keys($distances))], $query);
    }

    public function choose_correct_operation($id, $query) {
        switch($id) {
            case 1:
                echo 'performing 1 ';
                echo '<br>';
                $this->get_list_of_changes_for_col_since_time($query);
                break;
            case 2;
                echo 'performing 2 ';
                echo '<br>';
                $this->get_records_inserted_with_value($query);
                break;
            case 3:
                echo 'performing 3 ';
                echo '<br>';
                $this->check_when_record_is_deleted($query);
                break;
            case 4;
                echo 'performing 4 ';
                echo '<br>';
                $this->get_records_with_value_till_time($query);
                break;
            default:
                echo ' invalid options';
                break;
        }
    }
}



//actionInfo[0] is the opp
//actionInfo[1] is the id
//actionInfo[2] is the change
$versionControl = new VersionControl();

$versionControl->registerAction('I', '#1', array('a1', 'b1', 'c1'));
$versionControl->registerAction('I', '#2', array('a2', 'b1', 'c2'));
$versionControl->registerAction('I', '#3', array('a3', 'b1', 'c3'));
$versionControl->registerAction('I', '#4', array('a4', 'b1', 'c4'));
$versionControl->registerAction('I', '#5', array('a5', 'b5', 'c5'));
$versionControl->registerAction('I', '#6', array('a6', 'b1', 'c6'));
$versionControl->registerAction('I', '#7', array('a7', 'b6', 'c7'));
$versionControl->registerAction('E', '#2', array(2=>'B'));
$versionControl->registerAction('D', '#1', array());
$versionControl->registerAction('E', '#2', array(2=>'E'));

echo 'Edit history';
echo '<br>';
$versionControl->printHistory();

echo '<br>';
echo '<br>-><br>';
$versionControl->getStateAt(5);
$versionControl->printBuiltRecords();
echo '<br>-><br>';
$versionControl->getStateAt(6);
$versionControl->printBuiltRecords();
echo '<br>-><br>';
$versionControl->getStateAt(7);
$versionControl->printBuiltRecords();
echo '<br>-><br>';
$versionControl->getStateAt(999);
$versionControl->printBuiltRecords();
echo '<br>-><br>';
$versionControl->getDiff('#6', '#7');
echo '<br>-><br>';
echo '<br>-><br>';
echo 'PART B: ';
echo '<br>';
echo 'sentences that are compared to: ';
echo '<br>';
echo 'get list changes column time record_id';
echo '<br>';
echo 'get records inserted with value';
echo '<br>';
echo 'get when record_id deleted';
echo '<br>';
echo 'get records with value till time';
echo '<br>';
//task 1
echo 't1: ';
echo '<br>';
$versionControl->perform_closest_operation('get the list of my changes i did in my record history in the column 2 in past month or in time 1 with record_id #2');
echo '<br>-><br>';
//task 2
echo 't2: ';
echo '<br>';
$versionControl->perform_closest_operation('get the list of records inserted with a value b1 in column 1');
echo '<br>-><br>';
//task 3
echo 't3: ';
echo '<br>';
$versionControl->perform_closest_operation('when is record_id #1 deleted?');
echo '<br>-><br>';
//task 4
echo 't4: ';
echo '<br>';
$versionControl->perform_closest_operation('give me the records with value b1 in column 1 till time 4');
?>
