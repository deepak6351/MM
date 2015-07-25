<?php

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       Automation.php                                              ##
##  Developed by:  Deepak Deroa                                                ##
##  License:       Modern Mayhem                                               ##
##  Copyright:     Modern Mayhem. All rights reserved.                         ##
##                                                                             ##
#################################################################################

class Automation {

    private $bountyresarray = array();
    private $bountyinfoarray = array();
    private $bountyproduction = array();
    private $bountyocounter = array();
    private $bountyunitall = array();
    private $bountypop;
    private $bountyOresarray = array();
    private $bountyOinfoarray = array();
    private $bountyOproduction = array();
    private $bountyOpop = 1;

    public function isWinner() {
        $q = mysql_query("SELECT vref FROM " . TB_PREFIX . "fdata WHERE f99 = '100' and f99t = '40'");
        $isThere = mysql_num_rows($q);
        if ($isThere > 0) {
            return "WINNER";
        } else {
            ## there is no winner
        }
    }

    public function procResType($ref, $mode = 0, $isoasis = 0) {
        global $session;
        switch ($ref) {
            case 1: $build = "Woodcutter";
                break;
            case 2: $build = "Oil Well";
                break;
            case 3: $build = "Iron Mine";
                break;
            case 4: $build = "Farm";
                break;
            case 5: $build = "Barracks";
                break;
            case 6: $build = "Research Lab";
                break;
            case 7: $build = "Arsenal";
                break;
            case 8: $build = "RallyPoint";
                break;
            case 9: $build = "War Factory";
                break;
            case 10: $build = "Warehouse";
                break;
            case 11: $build = "Capitol";
                break;
            case 12: $build = "Hiding Place";
                break;
            case 13: $build = "Market";
                break;
            case 14: $build = "Wall";
                break;
            case 15: $build = "Headquarter";
                break;
            case 16: $build = "Titne Platform";
                break;
            //default: $build = "Nothing had"; break;
        }
        if ($build == "") {
            if ($mode) { //true to destroy village
                $build = "The village has been";
            } else { //capital or only 1 village left.. not destroy
                $build = "Village can't be";
            }
        }
        if ($isoasis != 0)
            $build = "Oasis had";
        return addslashes($build);
    }

    function recountPop($vid) {
        global $database;
        $fdata = $database->getResourceLevel($vid);
        $popTot = 0;

        for ($i = 1; $i <= 16; $i++) {
            $lvl = $fdata["f" . $i];
            $popTot += $this->buildingPOP($i, $lvl);
        }
        $this->recountCP($vid);
        $q = "UPDATE " . TB_PREFIX . "vdata set pop = $popTot where wref = $vid";
        mysql_query($q);
        $owner = $database->getVillageField($vid, "owner");
        $world_id = $database->getVillageField($vid, "world_id");
        $aid = $database->getVillageField($vid, "alliance");
        $this->procClimbers($owner, $world_id, $aid);

        return $popTot;
    }
    
     function recountexpPoints() {
        global $database;
        $q = "SELECT id FROM " . TB_PREFIX . "users  WHERE access =2 ";
         $result = mysql_query($q, $database->connection);
        $results  =  $database->mysql_fetch_all($result);
        foreach($results as $uid){
            $usersArray = $database->getUserArray($uid, 1);
            $userWorldArray = json_decode($usersArray['world_id']);
            $exp = 0;
       foreach($userWorldArray as $wid){
           $ksa = get_usermeta($uid, $wid, 'ap');
           $ksd = get_usermeta($uid, $wid, 'dp');
           $bonusPoint = get_usermeta($uid, $wid, 'bounsPoints');
           $exp += ((int)($ksa/100))*2 + ((int)($ksd/100)) + $bonusPoint;
           
       }
       $database->updateUserField($uid, 'expPoints', $exp, 1);
       $level = (int)($exp/10000);
        $quest->questexpLevel($uid, $wid, $level, 'expLevel');
     }
    }
    
    

    function recountbuffs($uid, $unit, $world_id) {
        global $database, ${'u' . $unit};
        $level = $database->getHeroLevel($uid, $unit, $world_id);
        $atkb = 0;
        $db = 0;
        $b1 = 0;
        $b2 = 0;
        $b3 = 0;
        $capb = 0;
        $wdb = 0;
        $cass = 0;
        $buspb = 0;
        $ispb = 0;
        $trspb = 0;
        $addslb = 0;

        for ($i = 1; $i <= $level; $i++) {
            $atkb += ${'u' . $unit}[$i]['atkb'];
            $db += ${'u' . $unit}[$i]['db'];
            $b1 += ${'u' . $unit}[$i]['b1'];
            $b2 += ${'u' . $unit}[$i]['b2'];
            $b3 += ${'u' . $unit}[$i]['b3'];
            $capb += ${'u' . $unit}[$i]['capb'];
            $wdb += ${'u' . $unit}[$i]['wdb'];
            $cass += ${'u' . $unit}[$i]['cass'];
            $buspb += ${'u' . $unit}[$i]['buspb'];
            $ispb += ${'u' . $unit}[$i]['ispb'];
            $trspb += ${'u' . $unit}[$i]['trspb'];
            $addslb += ${'u' . $unit}[$i]['addslb'];
        }

        $q = "UPDATE " . TB_PREFIX . "hero set attackbonus = $atkb, defencebonus = $db, b1 = $b1, b2 = $b2, b3 = $b3, capb = $capb, wdb = $wdb, cass = $cass, buspb = $buspb, ispb = $ispb, trspb = $trspb, addslb = $addslb where uid = $uid and unit = $unit and world_id = $world_id";
        mysql_query($q);
        return true;
    }

    function recountCP($vid) {
        global $database,$quest;
        $fdata = $database->getResourceLevel($vid);
        $popTot = 0;

        for ($i = 1; $i <= 16; $i++) {
            $lvl = $fdata["f" . $i];
            $popTot += $this->buildingCP($i, $lvl);
        }
        $popTot += 1056;
        $q = "UPDATE " . TB_PREFIX . "vdata set cp = $popTot where wref = $vid";
        mysql_query($q);
        $quest->questPoints($database->getVillageField($vid, 'owner'), $vid, $database->getVillageField($vid, 'world_id'), $popTot, 'points');
        return $popTot;
    }

    function buildingPOP($f, $lvl) {
        $name = "bid" . $f;
        global $$name;
        $popT = 0;
        $dataarray = $$name;

        for ($i = 1; $i <= $lvl; $i++) {
            $popT += $dataarray[$i]['pop'];
        }
        return $popT;
    }

    function buildingCP($f, $lvl) {
        $name = "bid" . $f;
        global $$name;
        $popT = 0;
        $dataarray = $$name;

        for ($i = 1; $i <= $lvl; $i++) {
            $popT += $dataarray[$i]['cp'];
        }
        return $popT;
    }

    public function Automation() {

        $this->procNewClimbers();
		$this->pruneResource();
        $this->pruneOResource();
        if (!file_exists("GameEngine/Prevention/culturepoints.txt") or time() - filemtime("GameEngine/Prevention/culturepoints.txt") > 50) {
            $this->culturePoints();
        }

        if (!file_exists("GameEngine/Prevention/build.txt") or time() - filemtime("GameEngine/Prevention/build.txt") > 50) {
            $this->buildComplete();
        }
        if (!file_exists("GameEngine/Prevention/demolition.txt") or time() - filemtime("GameEngine/Prevention/demolition.txt") > 50) {
//            $this->demolitionComplete();
        }
        $this->updateStore();

        if (!file_exists("GameEngine/Prevention/market.txt") or time() - filemtime("GameEngine/Prevention/market.txt") > 50) {
            $this->marketComplete();
        }
        if (!file_exists("GameEngine/Prevention/research.txt") or time() - filemtime("GameEngine/Prevention/research.txt") > 50) {
            $this->researchComplete();
        }
        if (!file_exists("GameEngine/Prevention/training.txt") or time() - filemtime("GameEngine/Prevention/training.txt") > 50) {
            $this->trainingComplete();
        }
        if (!file_exists("GameEngine/Prevention/starvation.txt") or time() - filemtime("GameEngine/Prevention/starvation.txt") > 50) {
//            $this->starvation();
        }

        if (!file_exists("GameEngine/Prevention/sendunits.txt") or time() - filemtime("GameEngine/Prevention/sendunits.txt") > 50) {
            $this->sendunitsComplete();
        }
        if (!file_exists("GameEngine/Prevention/loyalty.txt") or time() - filemtime("GameEngine/Prevention/loyalty.txt") > 60) {
            $this->loyaltyRegeneration();
        }
        if (!file_exists("GameEngine/Prevention/sendreinfunits.txt") or time() - filemtime("GameEngine/Prevention/sendreinfunits.txt") > 50) {
            $this->sendreinfunitsComplete();
        }
        if (!file_exists("GameEngine/Prevention/returnunits.txt") or time() - filemtime("GameEngine/Prevention/returnunits.txt") > 50) {
            $this->returnunitsComplete();
        }
		$this->updateStore();
        $this->regenerateOasisTroops();
    }

    private function loyaltyRegeneration() {  //done
        if (file_exists("GameEngine/Prevention/loyalty.txt")) {
            unlink("GameEngine/Prevention/loyalty.txt");
        }
        //create new file to check filetime
        //not every click regenerate but 1 minute or after


        $ourFileHandle = fopen("GameEngine/Prevention/loyalty.txt", 'w');
        fclose($ourFileHandle);
        global $database;
        $array = array();
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE loyalty<>100";
        $array = $database->query_return($q);
        if (!empty($array)) {
            foreach ($array as $loyalty) {

                $world_id = $loyalty['world_id'];
                $qw = "SELECT loyalty_regenrate FROM " . TB_PREFIX . "worlds WHERE fld_ID = $world_id";
                $result = mysql_query($qw, $database->connection);
                $dbarray = mysql_fetch_array($result);
                $value = $dbarray['loyalty_regenrate'];

                $newloyalty = min(100, $loyalty['loyalty'] + $value * (time() - $loyalty['lastupdate2']) / (60 * 60));
                $q = "UPDATE " . TB_PREFIX . "vdata SET loyalty = $newloyalty, lastupdate2=" . time() . " WHERE wref = '" . $loyalty['wref'] . "'";
                $database->query($q);
            }
        }
        $array = array();
        //   $q = "SELECT * FROM ".TB_PREFIX."odata, ".TB_PREFIX."wdata WHERE loyalty<>100";
        $q = "SELECT * FROM " . TB_PREFIX . "wdata left JOIN " . TB_PREFIX . "odata ON " . TB_PREFIX . "odata.wref = " . TB_PREFIX . "wdata.id where " . TB_PREFIX . "odata.loyalty<>100";
        $array = $database->query_return($q);
        if (!empty($array)) {
            foreach ($array as $loyalty) {

                $world_id = $loyalty['worldid'];
                $qw = "SELECT loyalty_regenrate FROM " . TB_PREFIX . "worlds WHERE fld_ID = $world_id";
                $result = mysql_query($qw, $database->connection);
                $dbarray = mysql_fetch_array($result);
                $value = $dbarray['loyalty_regenrate'];
                $newloyalty = min(100, $loyalty['loyalty'] + $value * (time() - $loyalty['lastupdated']) / (60 * 60));
                $q = "UPDATE " . TB_PREFIX . "odata SET loyalty = $newloyalty, lastupdated=" . time() . " WHERE wref = '" . $loyalty['wref'] . "'";
                $database->query($q);
            }
        }
    }

    private function getfieldDistance($coorx1, $coory1, $coorx2, $coory2) {
        $max = 2 * WORLD_MAX + 1;
        $x1 = intval($coorx1);
        $y1 = intval($coory1);
        $x2 = intval($coorx2);
        $y2 = intval($coory2);
        $distanceX = min(abs($x2 - $x1), abs($max - abs($x2 - $x1)));
        $distanceY = min(abs($y2 - $y1), abs($max - abs($y2 - $y1)));
        $dist = sqrt(pow($distanceX, 2) + pow($distanceY, 2));
        return round($dist, 1);
    }

    public function getTypeLevel($tid, $vid) { //done
        global $village, $database;


        $resourcearray = $database->getResourceLevel($vid);
        return $resourcearray['f' . $tid];
    }

    private function ClearUser() {
        global $database;
        if (AUTO_DEL_INACTIVE) {
            $time = time() + UN_ACT_TIME;
            $q = "DELETE from " . TB_PREFIX . "users where timestamp >= $time and act != ''";
            $database->query($q);
        }
    }

    private function ClearInactive() {
        global $database;
        if (TRACK_USR) {
            $timeout = time() - USER_TIMEOUT * 60;
            $q = "DELETE FROM " . TB_PREFIX . "active WHERE timestamp < $timeout";
            $database->query($q);
        }
    }

    private function pruneOResource() { //done
        global $database;
        if (!ALLOW_BURST) {
            $q = "SELECT * FROM " . TB_PREFIX . "odata WHERE maxstore < 800";
            $array = $database->query_return($q);
            foreach ($array as $getoasis) {
                if ($getoasis['maxstore'] < 800) {
                    $maxstore = 800;
                } else {
                    $maxstore = $getoasis['maxstore'];
                }

                $q = "UPDATE " . TB_PREFIX . "odata set maxstore = $maxstore where wref = " . $getoasis['wref'] . "";
                $database->query($q);
            }
            $q = "SELECT * FROM " . TB_PREFIX . "odata WHERE wood < 0 OR oil < 0 OR iron < 0 ";
            $array = $database->query_return($q);
            foreach ($array as $getoasis) {
                if ($getoasis['wood'] < 0) {
                    $wood = 0;
                } else {
                    $wood = $getoasis['wood'];
                }
                if ($getoasis['oil'] < 0) {
                    $oil = 0;
                } else {
                    $oil = $getoasis['oil'];
                }
                if ($getoasis['iron'] < 0) {
                    $iron = 0;
                } else {
                    $iron = $getoasis['iron'];
                }

                $q = "UPDATE " . TB_PREFIX . "odata set wood = $wood, oil = $oil, iron = $iron where wref = " . $getoasis['wref'] . "";
                $database->query($q);
            }
        }
    }

    private function pruneResource() { //done
        global $database;
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE maxstore < 800";
        $array = $database->query_return($q);
        foreach ($array as $getvillage) {
            if ($getvillage['maxstore'] < 800) {
                $maxstore = 800;
            } else {
                $maxstore = $getvillage['maxstore'];
            }
            $q = "UPDATE " . TB_PREFIX . "vdata set maxstore = $maxstore where wref = " . $getvillage['wref'] . "";
            $database->query($q);
        }
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE wood > maxstore OR oil > maxstore OR iron > maxstore";
        $array = $database->query_return($q);
        foreach ($array as $getvillage) {
            if ($getvillage['wood'] > $getvillage['maxstore']) {
                $wood = $getvillage['maxstore'];
            } else {
                $wood = $getvillage['wood'];
            }
            if ($getvillage['oil'] > $getvillage['maxstore']) {
                $oil = $getvillage['maxstore'];
            } else {
                $oil = $getvillage['oil'];
            }
            if ($getvillage['iron'] > $getvillage['maxstore']) {
                $iron = $getvillage['maxstore'];
            } else {
                $iron = $getvillage['iron'];
            }

            $q = "UPDATE " . TB_PREFIX . "vdata set wood = $wood, oil = $oil, iron = $iron where wref = " . $getvillage['wref'] . "";
            $database->query($q);
        }
        $q = "SELECT * FROM " . TB_PREFIX . "vdata WHERE wood < 0 OR oil < 0 OR iron < 0 ";
        $array = $database->query_return($q);
        foreach ($array as $getvillage) {
            if ($getvillage['wood'] < 0) {
                $wood = 0;
            } else {
                $wood = $getvillage['wood'];
            }
            if ($getvillage['oil'] < 0) {
                $oil = 0;
            } else {
                $oil = $getvillage['oil'];
            }
            if ($getvillage['iron'] < 0) {
                $iron = 0;
            } else {
                $iron = $getvillage['iron'];
            }

            $q = "UPDATE " . TB_PREFIX . "vdata set wood = $wood, oil = $oil, iron = $iron where wref = " . $getvillage['wref'] . "";
            $database->query($q);
        }
    }

    private function culturePoints() {
        if (file_exists("GameEngine/Prevention/culturepoints.txt")) {
            unlink("GameEngine/Prevention/culturepoints.txt");
        }
        global $database, $session;
        //
        if (SPEED > 10)
            $speed = 10;
        else
            $speed = SPEED;
        $dur_day = 86400 / $speed; //24 hours/speed
        if ($dur_day < 3600)
            $dur_day = 3600;
        $time = time() - 600; // recount every 10minutes

        $array = array();
        $q = "SELECT id, lastupdate FROM " . TB_PREFIX . "users WHERE lastupdate < $time";
        $array = $database->query_return($q);

        foreach ($array as $indi) {
            if ($indi['lastupdate'] <= $time && $indi['lastupdate'] > 0) {
                $cp = $database->getVSumField($indi['id'], 'cp') * (time() - $indi['lastupdate']) / $dur_day;

                $newupdate = time();
                $q = "UPDATE " . TB_PREFIX . "users set cp = cp + $cp, lastupdate = $newupdate where id = '" . $indi['id'] . "'";
                $database->query($q);
            }
        }
        if (file_exists("GameEngine/Prevention/culturepoints.txt")) {
            unlink("GameEngine/Prevention/culturepoints.txt");
        }
    }

    private function buildComplete() { //done
        if (file_exists("GameEngine/Prevention/build.txt")) {
            unlink("GameEngine/Prevention/build.txt");
        }
        global $database, $quest, $bid10;
        $time = time();
        $array = array();
        $q = "SELECT * FROM " . TB_PREFIX . "bdata where timestamp < $time ";
        $array = $database->query_return($q);
        foreach ($array as $indi) {
            $level = $database->getFieldLevel($indi['wid'], $indi['field']);
            if (($level + 1) == $indi['level']) {
                $q = "UPDATE " . TB_PREFIX . "fdata set f" . $indi['field'] . " = " . $indi['level'] . " where vref = " . $indi['wid'];
            } else {
                $indi['level'] = ($level + 1);
                $q = "UPDATE " . TB_PREFIX . "fdata set f" . $indi['field'] . " = " . $indi['level'] . " where vref = " . $indi['wid'];
            }
            if ($database->query($q)) {
                $level = $database->getFieldLevel($indi['wid'], $indi['field']);
                $this->recountPop($indi['wid']);
                $this->procClimbers($database->getVillageField($indi['wid'], 'owner'), $database->getVillageField($indi['wid'], 'world_id'), $database->getVillageField($indi['wid'], 'alliance'));
                if ($level % 5 == 0) {
                    $quest->questBuilding($database->getVillageField($indi['wid'], 'owner'), $indi['wid'], $database->getVillageField($indi['wid'], 'world_id'), $level, $indi['type']);
                }
                if ($indi['type'] == 11) {
                    $quest->questBuilding($database->getVillageField($indi['wid'], 'owner'), $indi['wid'], $database->getVillageField($indi['wid'], 'world_id'), $level, $indi['type']);
                }
                if ($indi['type'] == 10) {
                    $max = $database->getVillageField($indi['wid'], "maxstore");
                    $max = $bid10[$level]['attri'];
                    $database->setVillageField($indi['wid'], "maxstore", $max);
                }


                $q = "DELETE FROM " . TB_PREFIX . "bdata where id = " . $indi['id'];
                $database->query($q);
            }

            $unitarrays = $this->getAllUnits($indi['wid']);
            $village = $database->getVillage($indi['wid']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
            $starv = $database->getVillageField($indi['wid'], "starv");
          
        }
        if (file_exists("GameEngine/Prevention/build.txt")) {
            unlink("GameEngine/Prevention/build.txt");
        }
    }

    //titan Level complete

    private function TitanLevelComplete() { //done
        global $database;
        $time = time();
        $array = array();
        $q = "SELECT * FROM " . TB_PREFIX . "tidata where timestamp < $time ";
        $array = $database->query_return($q);
        foreach ($array as $indi) {
            $level = $database->getHeroLevel($indi['uid'], $indi['unit'], $indi['world_id']);
            if (($level + 1) == $indi['level']) {
                $q = "UPDATE " . TB_PREFIX . "hero set level = " . $indi['level'] . " where uid = " . $indi['uid'] . "and unit= " . $indi['unit'] . "world_id =" . $indi['world_id'];
            } else {
                $indi['level'] = ($level + 1);
                $q = "UPDATE " . TB_PREFIX . "hero set level = " . $indi['level'] . " where uid = " . $indi['uid'] . "and unit= " . $indi['unit'] . "world_id =" . $indi['world_id'];
            }
            if ($database->query($q)) {
                $level = $database->getHeroLevel($indi['uid'], $indi['unit'], $indi['world_id']);
                if ($level % 5 == 0 && $level <> 25 || $level == 1) {
                    $this->recountbuffs($indi['uid'], $indi['unit'], $indi['world_id']);
                }
                $q = "DELETE FROM " . TB_PREFIX . "tidata where id = " . $indi['id'];
                $database->query($q);
            }
        }
    }

  
    private function getPop($tid, $level) { //done
        $name = "bid" . $tid;
        global $$name, $village;
        $dataarray = $$name;
        $pop = $dataarray[($level + 1)]['pop'];
        $cp = $dataarray[($level + 1)]['cp'];
        return array($pop, $cp);
    }

    private function marketComplete() { //done
        global $database;
        $time = microtime(true);
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "send where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "send.id and " . TB_PREFIX . "movement.proc = 0 and sort_type = 0 and endtime < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {

            if ($data['wood'] >= $data['oil'] && $data['wood'] >= $data['iron']) {
                $sort_type = "10";
            } elseif ($data['oil'] >= $data['wood'] && $data['oil'] >= $data['iron']) {
                $sort_type = "11";
            } elseif ($data['iron'] >= $data['wood'] && $data['iron'] >= $data['oil']) {
                $sort_type = "12";
            }

            $to = $database->getMInfo($data['to']);
            $world_id = $to['worldid'];
            $from = $database->getMInfo($data['from']);
            $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, $sort_type, '' . addslashes($from['name']) . ' send resources to ' . addslashes($to['name']) . '', '' . $from['owner'] . ',' . $from['wref'] . ',' . $data['wood'] . ',' . $data['oil'] . ',' . $data['iron'] , $data['endtime']);
            if ($from['owner'] != $to['owner']) {
                $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, $sort_type, '' . addslashes($from['name']) . ' send resources to ' . addslashes($to['name']) . '', '' . $from['owner'] . ',' . $from['wref'] . ',' . $data['wood'] . ',' . $data['oil'] . ',' . $data['iron'] . ',' .$data['endtime']);
            }
            $database->modifyResource($data['to'], $data['wood'], $data['oil'], $data['iron'], 1);
            $tocoor = $database->getCoor($data['from']);
            $fromcoor = $database->getCoor($data['to']);
            $targettribe = 2;
            $endtime = $this->procDistanceTime($tocoor, $fromcoor, $targettribe, 0) + $data['endtime'];
            $database->addMovement(2, $data['to'], $data['from'], $data['merchant'], time(), $endtime, $data['send'], $data['wood'], $data['oil'], $data['iron']);
            $database->setMovementProc($data['moveid']);
        }
        $q1 = "SELECT * FROM " . TB_PREFIX . "movement where proc = 0 and sort_type = 2 and endtime < $time";
        $dataarray1 = $database->query_return($q1);
        foreach ($dataarray1 as $data1) {
            $database->setMovementProc($data1['moveid']);
            if ($data1['send'] > 1) {
                $targettribe1 = 2;
                $send = $data1['send'] - 1;
                $this->sendResource2($data1['wood'], $data1['oil'], $data1['iron'], $data1['to'], $data1['from'], $targettribe1, $send);
            }
        }
        if (file_exists("GameEngine/Prevention/market.txt")) {
            unlink("GameEngine/Prevention/market.txt");
        }
    }

    private function sendResource2($wtrans, $ctrans, $itrans, $from, $to, $tribe, $send) { //done
        global $bid13, $database, $generator, $logging;
        $availableWood = $database->getWoodAvailable($from);
        $availableOil = $database->getOilAvailable($from);
        $availableIron = $database->getIronAvailable($from);
        if ($availableWood >= $wtrans AND $availableOil >= $ctrans AND $availableIron >= $itrans) {
            $merchant2 = ($this->getTypeLevel(13, $from) > 0) ? $this->getTypeLevel(13, $from) : 0;
            $used2 = $database->totalMerchantUsed($from);
            $merchantAvail2 = $merchant2 - $used2;
            $maxcarry2 = 1000;
            $maxcarry2 *= TRADER_CAPACITY;

            $resource = array($wtrans, $ctrans, $itrans);
            $reqMerc = ceil((array_sum($resource) - 0.1) / $maxcarry2);
            if ($merchantAvail2 != 0 && $reqMerc <= $merchantAvail2) {
                $coor = $database->getCoor($to);
                $coor2 = $database->getCoor($from);
                if ($database->getVillageState($to)) {
                    $timetaken = $generator->procDistanceTime($coor, $coor2, $tribe, 0);
                    $res = $resource[0] + $resource[1] + $resource[2] + $resource[3];
                    if ($res != 0) {
                        $reference = $database->sendResource($resource[0], $resource[1], $resource[2], $resource[3], $reqMerc, 0);
                        $database->modifyResource($from, $resource[0], $resource[1], $resource[2], $resource[3], 0);
                        $database->addMovement(0, $from, $to, $reference, microtime(true), microtime(true) + $timetaken, $send);
                    }
                }
            }
        }
    }

    private function sendunitsComplete() {
        if (file_exists("GameEngine/Prevention/sendunits.txt")) {
            unlink("GameEngine/Prevention/sendunits.txt");
        }
        global $bid23, $bid34, $database, $battle, $village, $technology, $logging, $generator, $session, $units;
        $ourFileHandle = fopen("GameEngine/Prevention/sendunits.txt", 'w');
        fclose($ourFileHandle);
        $time = time();
       // movement.sort_type : movement id for attacking someone city
//        attack_type : spy = 1
//                      support = 2
//                      normal attack : 3
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = '0' and " . TB_PREFIX . "movement.sort_type = '3' and " . TB_PREFIX . "attacks.attack_type != '2' and endtime < $time ORDER BY endtime ASC";
        $dataarray = $database->query_return($q);
        $totalattackdead = 0;
        $data_num = 0;
        foreach ($dataarray as $data) {
            //set base things
            //$battle->resolveConflict($data);
            $tocoor = $database->getCoor($data['from']);
            $fromcoor = $database->getCoor($data['to']);
            $isoasis = $database->isVillageOases($data['to']);
            $AttackArrivalTime = $data['endtime'];
            $AttackerWref = $data['from'];
            $DefenderWref = $data['to'];
            if ($isoasis == 0) { //village
                $Attacker['id'] = $database->getUserField($database->getVillageField($data['from'], "owner"), "id", 0);
                $Defender['id'] = $database->getUserField($database->getVillageField($data['to'], "owner"), "id", 0);
                $AttackerID = $Attacker['id'];
                $DefenderID = $Defender['id'];
                if ($AttackerID == $DefenderID){
                 
                     $data_num++; //writing a code for return movement(troops) when a user already conqure a city
                        continue;
                }
//                $ownally = $database->getVillageField($data['from'], "alliance");
//                $targetally = $database->getVillageField($data['to'], "alliance");
                $to = $database->getMInfo($data['to']);
                $from = $database->getMInfo($data['from']);
                $toF = $database->getVillage($data['to']);
                $fromF = $database->getVillage($data['from']);
                $world_id = $to['worldid'];
                $conqureby = 0;
                if (!isset($to['name']) || $to['name'] == '')
                    $to['name'] = "??";

                $DefenderUnit = array();
                $DefenderUnit = $database->getUnit($data['to']);
//                $gold = $database->getUserField($DefenderID, "gold", 0);
                
//               $attackid = $database->addAttack($data['to'], $data['u1'], $data['u2'], $data['u3'], $data['u4'], $data['u5'], $data['u6'], $data['u7'], $data['u8'], $data['u9'], $data['u10'], $data['u11'], 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
//                        $database->addMovement(4, 0, $data['to'], $attackid, microtime(true), microtime(true) + (180 / EVASION_SPEED));
                //get defence units
                $Defender = array();
                $enforDefender = array();
                $rom = $ger = $gal = 0;
                $defendertitan = 0;
                $Defender = $database->getUnit($data['to']);

                for ($i = 11; $i <= 25; $i++) {
                    if (!isset($Defender['u' . $i])) {
                        $Defender['u' . $i] = '0';
                    } else {
                        if ($Defender['u' . $i] == '' or $Defender['u' . $i] <= '0') {
                            $Defender['u' . $i] = '0';
                        } 
                    }
                    
                    if($i>19 && $dataarray[$data_num]['u' . $y] > 0 && $database->IsTitanActive($DefenderWref, $i)){
                        $defendertitan = $i;
                        $Defender['u' . $i] = 1;
                    }elseif($i>19){
                        $Defender['u' . $i] = '0';
                    }else{
                         $Defender['u' . $i] = $Defender['u' . $i];
                    }
                }
                $Defender['hero'] = $defendertitan;
                //get attack units
                $Attacker = array();
                $start = 11;
                $end = 25;
                $u = 10;
                $catapult = array(18);
                $ram = array(17);
                $chief = array(19);
                $spys = array(14);
                $titanunit = 0;
                for ($i = $start; $i <= $end; $i++) {
                    $y = $i - $u;
                    $Attacker['u' . $i] = $dataarray[$data_num]['t' . $y];
                    if($i>19 && $dataarray[$data_num]['t' . $y] > 0){
                       $titanunit = $i;
                     }
                    
                }
                $Attacker['uhero'] =  $titanunit;
                //need to set these variables.
                $def_wall = $database->getFieldLevel($data['to'], 14);
                $attpop = $fromF['pop'];
                $defpop = $toF['pop'];

                //type of attack
                if ($dataarray[$data_num]['attack_type'] == 1) {
                    $type = 1;
                    $scout = 1;
                }
                if ($dataarray[$data_num]['attack_type'] == 2) {
                    $type = 2;
                }
                if ($dataarray[$data_num]['attack_type'] == 3) {
                    $type = 3;
                }
                if ($dataarray[$data_num]['attack_type'] == 4) {
                    $type = 4;
                }
                $tblevel = '1';

                /* --------------------------------
                  // End village Battle part
                  -------------------------------- */
            } else {
                $Attacker['id'] = $database->getUserField($database->getVillageField($data['from'], "owner"), "id", 0);
                $Defender['id'] = $database->getUserField($database->getOasisField($data['to'], "owner"), "id", 0);
                $AttackerID = $Attacker['id'];
                $DefenderID = $Defender['id'];
                if ($AttackerID == $DefenderID){
                 
                     $data_num++; //writing a code for return movement(troops) when a user already conqure a city
                        continue;
                }
                $ownally = $database->getVillageField($data['from'], "alliance");
                $to = $database->getOMInfo($data['to']);
                $world_id = $to['worldid'];
                $from = $database->getMInfo($data['from']);
                $toF = $database->getOasisV($data['to']);
                $fromF = $database->getVillage($data['from']);
                $conqureby = $toF['conqured'];
                //get defence units
                $Defender = array();
                $enforDefender = array();
                $rom = $ger = $gal = $nat = $natar = 0;
                $Defender = $database->getUnit($data['to']);
                $defendertitan = 0;
//                $enforcementarray = $database->getEnforceVillage($data['to'], 0);

                for ($i = 11; $i <= 25; $i++) {
                    if (!isset($Defender['u' . $i])) {
                        $Defender['u' . $i] = '0';
                    } else {
                        if ($Defender['u' . $i] == '' or $Defender['u' . $i] <= '0') {
                            $Defender['u' . $i] = '0';
                        } 
                    }
                    if($i>19 && $dataarray[$data_num]['u' . $y] > 0 && $database->IsTitanActive($DefenderWref, $i)){
                        $defendertitan = $i;
                        $Defender['u' . $i] = 1;
                    }elseif($i>19){
                        $Defender['u' . $i] = '0';
                    }else{
                         $Defender['u' . $i] = $Defender['u' . $i];
                    }
                }
                $Defender['hero'] = $defendertitan;

                //get attack units
                $Attacker = array();
                $start = 11;
                $end = 25;
                $u = 10;
                $catapult = array(18);
                $titanunit = 0;
                $ram = array(17);
                $chief = array(19);
                $spys = array(14);
                for ($i = $start; $i <= $end; $i++) {
                    $y = $i - $u;
                   if($i>19 && $dataarray[$data_num]['t' . $y] > 0){
                       $titanunit = $i;
                     }
                }
                $Attacker['uhero'] = $titanunit;
                //need to set these variables.
                $def_wall = $database->getFieldLevel($data['to'], 14);
                $attpop = $fromF['pop'];

                //type of attack
                if ($dataarray[$data_num]['attack_type'] == 1) {
                    $type = 1;
                    $scout = 1;
                }
                if ($dataarray[$data_num]['attack_type'] == 2) {
                    $type = 2;
                }
                if ($dataarray[$data_num]['attack_type'] == 3) {
                    $type = 3;
                }
                if ($dataarray[$data_num]['attack_type'] == 4) {
                    $type = 4;
                }

                $empty = '1';
                $tblevel = '0';
            }

            $DefendersAll = $database->getEnforceVillage($DefenderWref,0);
            if(!empty($DefendersAll) && $DefenderWref>0){
                foreach($DefendersAll as $defenders) {
                    for ($i = 11; $i <= 25; $i++) {
                       $Defender['u' . $i] +=  $defenders['u' . $i];
                    }
                   }
            }
            $ctar1 = $dataarray[$data_num]['ctar1'];
            $ctar2 = $dataarray[$data_num]['ctar2'];    
            //to battle.php
            $battlepart = $battle->calculateBattle($Attacker, $Defender, $def_wall, $type, $tblevel, $AttackerID, $DefenderID, $AttackerWref, $DefenderWref, $conqureby, $world_id, $ctar1, $ctar2);

            //units attack string for battleraport
            $unitssend_att = '' . $data['t1'] . ',' . $data['t2'] . ',' . $data['t3'] . ',' . $data['t4'] . ',' . $data['t5'] . ',' . $data['t6'] . ',' . $data['t7'] . ',' . $data['t8'] . ',' . $data['t9'] . ',' . $data['t10'] . '';
            $herosend_att = $data['t11'];
            if ($herosend_att > 0) {
                $unitssend_att_check = $unitssend_att . ',' . $data['t11'];
            } else {
                $unitssend_att_check = $unitssend_att;
            }
            //units defence string for battleraport
            $DefenderHero = array();
            $d = 0;
            if (isset($Defender['hero'])) {
                if ($Defender['hero'] > 0) {
                    $d = 1;
                    $DefenderHero[$d] = $DefenderID;
                }
            }

            $enforcementarray2 = $database->getEnforceVillage($data['to'], 0);



            $unitssend_def[2] = '' . $Defender['u11'] . ',' . $Defender['u12'] . ',' . $Defender['u13'] . ',' . $Defender['u14'] . ',' . $Defender['u15'] . ',' . $Defender['u16'] . ',' . $Defender['u17'] . ',' . $Defender['u18'] . ',' . $Defender['u19'] . ',' . $Defender['u20'] . '';

            $herosend_def = 0;

            $totalsend_alldef[2] = $Defender['u11'] + $Defender['u12'] + $Defender['u13'] + $Defender['u14'] + $Defender['u15'] + $Defender['u16'] + $Defender['u17'] + $Defender['u18'] + $Defender['u19'] + $Defender['u20'];

            $totalsend_alldef = $totalsend_alldef[2];

            $unitssend_deff[2] = '?,?,?,?,?,?,?,?,?,?,';
            //how many troops died? for battleraport



            for ($i = 1; $i <= 11; $i++) {
                if ($battlepart['casualties_attacker'][$i] <= 0) {
                    ${dead . $i} = 0;
                } elseif ($battlepart['casualties_attacker'][$i] > $data['t' . $i]) {
                    ${dead . $i} = $data['t' . $i];
                } else {
                    ${dead . $i} = $battlepart['casualties_attacker'][$i];
                }
            }
            //if the defender does not have spies, the attacker will not die spies.	FIXED BY Armando
            if ($scout) {
                $spy_def_Detect = 0;
                for ($i = 11; $i <= (20); $i++) {
                    if ($i == 14) {
                        if ($Defender['u' . $i] > 0) {
                            $spy_def_Detect = $i;
                            break;
                        }
                    }
                }
                if ($spy_def_Detect == 0) {
                    $dead3 = 0;
                    $dead4 = 0;
                    $battlepart['casualties_attacker'][3] = 0;
                    $battlepart['casualties_attacker'][4] = 0;
                }
            }

            #################################################

            $dead = array();
            $owndead = array();
            $alldead = array();
            $heroAttackDead = $dead11;
            //kill own defence
            $q = "SELECT * FROM " . TB_PREFIX . "units WHERE vref='" . $data['to'] . "'";
            $unitlist = $database->query_return($q);
            $start = 11;
            $end = 20;

            $u = "1";
            $ger = '1';
            for ($i = 11; $i <= $end; $i++) {
                if ($i == 20) {
                    $u = 2;
                }
                if ($unitlist) {
                    $owndead[$i] = round($battlepart[2] * $unitlist[0]['u' . $i]);
                    $database->modifyUnit($data['to'], array($i), array($owndead[$i]), array(0));
                }
            }
            $owndead['hero'] = '0';
            if ($unitlist) {
                $owndead['hero'] = $battlepart['deadherodef'];
                $database->modifyUnit($data['to'], array("hero"), array($owndead['hero']), array(0));
            }
            //kill other defence in village
            $totalsend_att = $data['t1'] + $data['t2'] + $data['t3'] + $data['t4'] + $data['t5'] + $data['t6'] + $data['t7'] + $data['t8'] + $data['t9'] + $data['t10'] + $data['t11'];
            for ($i = 1; $i <= 50; $i++) {
                $alldead[$i]+=$owndead[$i];
            }

            $unitsdead_def[2] = '' . $alldead['11'] . ',' . $alldead['12'] . ',' . $alldead['13'] . ',' . $alldead['14'] . ',' . $alldead['15'] . ',' . $alldead['16'] . ',' . $alldead['17'] . ',' . $alldead['18'] . ',' . $alldead['19'] . ',' . $alldead['20'] . '';

            $unitsdead_deff[2] = '?,?,?,?,?,?,?,?,?,?,';

            $deadhero = $alldead['hero'] + $owndead['hero'];

            $totaldead_alldef[2] = $alldead['11'] + $alldead['12'] + $alldead['13'] + $alldead['14'] + $alldead['15'] + $alldead['16'] + $alldead['17'] + $alldead['18'] + $alldead['19'] + $alldead['20'];


            $totaldead_alldef = $totaldead_alldef[2];
            $totalattackdead += $totaldead_alldef;


            // Set units returning from attack

            for ($i = 1; $i <= 11; $i++) {
                $t_units.="t" . $i . "=t" . $i . " - " . ${dead . $i} . (($i > 10) ? '' : ', ');
                $p_units.="t" . $i . "=t" . $i . " - " . ${traped . $i} . (($i > 10) ? '' : ', ');
            }

            $database->modifyAttack3($data['ref'], $t_units);
            $database->modifyAttack3($data['ref'], $p_units);

            $unitsdead_att = '' . $dead1 . ',' . $dead2 . ',' . $dead3 . ',' . $dead4 . ',' . $dead5 . ',' . $dead6 . ',' . $dead7 . ',' . $dead8 . ',' . $dead9 . ',' . $dead10 . '';
            $unitstraped_att = '' . $traped1 . ',' . $traped2 . ',' . $traped3 . ',' . $traped4 . ',' . $traped5 . ',' . $traped6 . ',' . $traped7 . ',' . $traped8 . ',' . $traped9 . ',' . $traped10 . ',' . $traped11 . '';
            if ($herosend_att > 0) {
                $unitsdead_att_check = $unitsdead_att . ',' . $dead11;
            } else {
                $unitsdead_att_check = $unitsdead_att;
            }
            //$unitsdead_def = ''.$dead11.','.$dead12.','.$dead13.','.$dead14.','.$dead15.','.$dead16.','.$dead17.','.$dead18.','.$dead19.','.$dead20.'';
            //top 10 attack and defence update
            $totaldead_att = $dead1 + $dead2 + $dead3 + $dead4 + $dead5 + $dead6 + $dead7 + $dead8 + $dead9 + $dead10 + $dead11;
            $totalattackdead += $totaldead_att;
            $troopsdead1 = $dead1;
            $troopsdead2 = $dead2;
            $troopsdead3 = $dead3;
            $troopsdead4 = $dead4;
            $troopsdead5 = $dead5;
            $troopsdead6 = $dead6;
            $troopsdead7 = $dead7;
            $troopsdead8 = $dead8;
            $troopsdead9 = $dead9 + 1;
            $troopsdead10 = $dead10;
            $troopsdead11 = $dead11;
            for ($i = 1; $i <= 50; $i++) {
                if ($unitarray) {
                    reset($unitarray);
                }
                $unitarray = $GLOBALS["u" . $i];

                $totaldead_def += $alldead['' . $i . ''];

                $totalpoint_att += ($alldead['' . $i . ''] * $unitarray['pop']);
            }
            $totalpoint_att += ($alldead['hero'] * 6);

            if ($Attacker['uhero'] != 0) {
                $heroxp = $totalpoint_att;
                $database->modifyHeroXp("experience", $heroxp, $from['owner']);
            }

            for ($i = 1; $i <= 10; $i++) {
                if ($unitarray) {
                    reset($unitarray);
                }
                $unitarray = $GLOBALS["u" . (($att_tribe - 1) * 10 + $i)];
                $totalpoint_def += (${dead . $i} * $unitarray['pop']);
            }
            $totalpoint_def +=$dead11 * 6;
            if ($Defender['hero'] > 0) {
                //counting heroxp
                $defheroxp = intval($totalpoint_def / count($DefenderHero));
                for ($i = 1; $i <= count($DefenderHero); $i++) {
                    $reinfheroxp = $defheroxp;
                    $database->modifyHeroXp("experience", $reinfheroxp, $DefenderHero[$i]);
                }
            }

            $database->modifyPoints($toF['owner'], $world_id, 'dpall', $totalpoint_def);
            $database->modifyPoints($from['owner'], $world_id, 'apall', $totalpoint_att);
            $database->modifyPoints($toF['owner'], $world_id, 'dp', $totalpoint_def);
            $database->modifyPoints($from['owner'], $world_id, 'ap', $totalpoint_att);
            $database->modifyPointsAlly($targetally, 'Adp', $totalpoint_def);
            $database->modifyPointsAlly($ownally, 'Aap', $totalpoint_att);
            $database->modifyPointsAlly($targetally, 'dp', $totalpoint_def);
            $database->modifyPointsAlly($ownally, 'ap', $totalpoint_att);

            if ($isoasis == 0) {
                // get toatal cranny value(hiding palace):
                $buildarray = $database->getResourceLevel($data['to']);
                $cranny = 0;
                for ($i = 19; $i < 39; $i++) {
                    if ($buildarray['f' . $i . 't'] == 23) {
                        $cranny += $bid23[$buildarray['f' . $i . '']]['attri'] * CRANNY_CAPACITY;
                    }
                }

                //cranny efficiency
                $atk_bonus = ($owntribe == 2) ? (4 / 5) : 1;
                $def_bonus = ($targettribe == 3) ? 2 : 1;
                $to_owner = $database->getVillageField($data['to'], "owner");

                $artefact_bouns = 1;

                $foolartefact = $database->getFoolArtefactInfo(7, $vid, $session->uid);

                $cranny_eff = ($cranny * $atk_bonus) * $def_bonus * $artefact_bouns;

                // work out available resources.
                $this->updateRes($data['to'], $to['owner']);
                $this->pruneResource();

                $totoil = $database->getVillageField($data['to'], 'oil');
                $totiron = $database->getVillageField($data['to'], 'iron');
                $totwood = $database->getVillageField($data['to'], 'wood');
            } else {
                $cranny_eff = 0;

                // work out available resources.
                $this->updateORes($data['to']);
                $this->pruneOResource();

                if ($conqureby > 0) { //10% from owner proc village owner 
                    $totoil = intval($database->getVillageField($conqureby, 'oil') / 10);
                    $totiron = intval($database->getVillageField($conqureby, 'iron') / 10);
                    $totwood = intval($database->getVillageField($conqureby, 'wood') / 10);
                } else {
                    $totoil = $database->getOasisField($data['to'], 'oil');
                    $totiron = $database->getOasisField($data['to'], 'iron');
                    $totwood = $database->getOasisField($data['to'], 'wood');
                }
            }
            $avoil = floor($totoil - $cranny_eff);
            $aviron = floor($totiron - $cranny_eff);
            $avwood = floor($totwood - $cranny_eff);

            $avoil = ($avoil < 0) ? 0 : $avoil;
            $aviron = ($aviron < 0) ? 0 : $aviron;
            $avwood = ($avwood < 0) ? 0 : $avwood;


            $avtotal = array($avwood, $avoil, $aviron);

            $av = $avtotal;

            // resources (wood,oil,iron)
            $steal = array(0, 0, 0);

            //bounty variables
            $btotal = $battlepart['bounty'];
            $bmod = 0;


            for ($i = 0; $i < 5; $i++) {
                for ($j = 0; $j < 4; $j++) {
                    if (isset($avtotal[$j])) {
                        if ($avtotal[$j] < 1)
                            unset($avtotal[$j]);
                    }
                }
                if (!$avtotal) {
                    // echo 'array empty'; *no resources left to take.
                    break;
                }
                if ($btotal < 1 && $bmod < 1)
                    break;
                if ($btotal < 1) {
                    while ($bmod) {
                        //random select
                        $rs = array_rand($avtotal);
                        if (isset($avtotal[$rs])) {
                            $avtotal[$rs] -= 1;
                            $steal[$rs] += 1;
                            $bmod -= 1;
                        }
                    }
                }

                // handle unballanced amounts.
                $btotal +=$bmod;
                $bmod = $btotal % count($avtotal);
                $btotal -=$bmod;
                $bsplit = $btotal / count($avtotal);

                $max_steal = (min($avtotal) < $bsplit) ? min($avtotal) : $bsplit;

                for ($j = 0; $j < 4; $j++) {
                    if (isset($avtotal[$j])) {
                        $avtotal[$j] -= $max_steal;
                        $steal[$j] += $max_steal;
                        $btotal -= $max_steal;
                    }
                }
            }

            //work out time of return
            $start = 11;
            $end = 20;

            $unitspeeds = array(6, 5, 7, 16, 14, 10, 4, 3, 4, 5,
                7, 7, 6, 9, 10, 9, 4, 3, 4, 5,
                7, 6, 17, 19, 16, 13, 4, 3, 4, 5,
                7, 7, 6, 9, 10, 9, 4, 3, 4, 5,
                7, 7, 6, 9, 10, 9, 4, 3, 4, 5);

            $speeds = array();

            //find slowest unit.
            for ($i = 1; $i <= 10; $i++) {
                if ($data['t' . $i] > $battlepart['casualties_attacker'][$i]) {
                    if ($unitarray) {
                        reset($unitarray);
                    }
                    $unitarray = $GLOBALS["u" . (($owntribe - 1) * 10 + $i)];
                    $speeds[] = $unitarray['speed'];
                }
            }
            if ($herosend_att > 0) {
                $qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                $resulth = mysql_query($qh);
                $hero_f = mysql_fetch_array($resulth);
                $hero_unit = $hero_f['unit'];
                $speeds[] = $GLOBALS['u' . $hero_unit]['speed'];
            }

// Data for when troops return.
            //catapults look :D
            $info_cat = $info_chief = $info_ram = $info_hero = ",";
            //check to see if can destroy village
            $varray = $database->getProfileVillages($to['owner']);
            if (count($varray) != '1' AND $to['capital'] != '1') {
                $can_destroy = 1;
            } else {
                $can_destroy = 0;
            }
            if ($isoasis == 1)
                $can_destroy = 0;

            if ($type == '3') {
                if (($data['t7'] - $traped7) > 0) {
                    if (isset($empty)) {
                        $info_ram = "" . $ram_pic . ",There is no wall to destroy.";
                    } else

                    if ($battlepart[8] > $battlepart[7]) {
                        $info_ram = "" . $ram_pic . ",Wall destroyed.";
                        $database->setVillageLevel($data['to'], "f" . $wallid . "", '0');
                        $database->setVillageLevel($data['to'], "f" . $wallid . "t", '0');
                        $pop = $this->recountPop($data['to']);
                    } elseif ($battlepart[8] == 0) {

                        $info_ram = "" . $ram_pic . ",Wall was not damaged.";
                    } else {

                        $demolish = $battlepart[8] / $battlepart[7];
                        $totallvl = round(sqrt(pow(($walllevel + 0.5), 2) - ($battlepart[8] * 8)));
                        if ($walllevel == $totallvl) {
                            $info_ram = "" . $ram_pic . ",Wall was not damaged.";
                        } else {
                            $info_ram = "" . $ram_pic . ",Wall damaged from level <b>" . $walllevel . "</b> to level <b>" . $totallvl . "</b>.";
                            $database->setVillageLevel($data['to'], "f" . $wallid . "", $totallvl);
                        }
                    }
                }
            } elseif (($data['t7'] - $traped7) > 0) {
                $info_ram = "" . $ram_pic . ",Hint: The ram does not work during a raid.";
            }
            if ($type == '3') {
                if (($data['t8'] - $traped8) > 0) {
                    $pop = $this->recountPop($data['to']);
                    if ($isoasis == 0) {
                        $pop = $this->recountPop($data['to']);
                    } else
                        $pop = 10; //oasis cannot be destroy bt cata/ram
                    if ($pop <= 0) {
                        if ($can_destroy == 1) {
                            $info_cat = "" . $catp_pic . ", Village already destroyed.";
                        } else {
                            $info_cat = "" . $catp_pic . ", Village can\'t be destroyed.";
                        }
                    } else {
                        $basearray = $data['to'];

                        if ($data['ctar2'] == 0) {
                            $bdo2 = mysql_query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                            $bdo = mysql_fetch_array($bdo2);

                            $rand = $data['ctar1'];

                            if ($rand != 0) {
                                $_rand = array();
                                $__rand = array();
                                $j = 0;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i . 't'] == $rand && $bdo['f' . $i] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                        $j++;
                                        $_rand[$j] = $bdo['f' . $i];
                                        $__rand[$j] = $i;
                                    }
                                }
                                if (count($_rand) > 0) {
                                    if (max($_rand) <= 0)
                                        $rand = 0;
                                    else {
                                        $rand = rand(1, $j);
                                        $rand = $__rand[$rand];
                                    }
                                } else {
                                    $rand = 0;
                                }
                            }

                            if ($rand == 0) {
                                $list = array();
                                $j = 1;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                        $list[$j] = $i;
                                        $j++;
                                    }
                                }
                                $rand = rand(1, $j);
                                $rand = $list[$rand];
                            }

                            $tblevel = $bdo['f' . $rand];
                            $tbgid = $bdo['f' . $rand . 't'];
                            $tbid = $rand;
                            if ($battlepart[4] > $battlepart[3]) {
                                $info_cat = "" . $catp_pic . ", " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.";
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                if ($tbid >= 19 && $tbid != 99) {
                                    $database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                }
                                $buildarray = $GLOBALS["bid" . $tbgid];
                                if ($tbgid == 10 || $tbgid == 38) {
                                    $tsql = mysql_query("select `maxstore` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxstore = $t_sql['maxstore'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxstore < 800)
                                        $tmaxstore = 800;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "'*32 WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 11 || $tbgid == 39) {
                                    $tsql = mysql_query("select `maxstore` from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxcrop = $t_sql['maxcrop'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxcrop < 800)
                                        $tmaxcrop = 800;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "'*32 WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 18) {
                                    $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                }
                                if ($isoasis == 0) {
                                    $pop = $this->recountPop($data['to']);
                                    $capital = $database->getVillage($data['to']);
                                    if ($pop == '0' && $can_destroy == 1) {
                                        $village_destroyed = 1;
                                    }
                                }
                            } elseif ($battlepart[4] == 0) {
                                $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.";
                            } else {
                                $demolish = $battlepart[4] / $battlepart[3];
                                $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - ($battlepart[4] * 8)));
                                if ($tblevel == $totallvl)
                                    $info_cata = " was not damaged.";
                                else {
                                    $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                    $buildarray = $GLOBALS["bid" . $tbgid];
                                    if ($tbgid == 10 || $tbgid == 38) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxstore = $t_sql['maxstore'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxstore < 800)
                                            $tmaxstore = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxcrop < 800)
                                            $tmaxcrop = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 18) {
                                        $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                    }
                                    $pop = $this->recountPop($data['to']);
                                }
                                $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata;
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                            }
                        } else {
                            $bdo2 = mysql_query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                            $bdo = mysql_fetch_array($bdo2);
                            $rand = $data['ctar1'];
                            if ($rand != 0) {
                                $_rand = array();
                                $__rand = array();
                                $j = 0;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i . 't'] == $rand && $bdo['f' . $i] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                        $j++;
                                        $_rand[$j] = $bdo['f' . $i];
                                        $__rand[$j] = $i;
                                    }
                                }
                                if (count($_rand) > 0) {
                                    if (max($_rand) <= 0)
                                        $rand = 0;
                                    else {
                                        $rand = rand(1, $j);
                                        $rand = $__rand[$rand];
                                    }
                                } else {
                                    $rand = 0;
                                }
                            }

                            if ($rand == 0) {
                                $list = array();
                                $j = 0;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                        $j++;
                                        $list[$j] = $i;
                                    }
                                }
                                $rand = rand(1, $j);
                                $rand = $list[$rand];
                            }

                            $tblevel = $bdo['f' . $rand];
                            $tbgid = $bdo['f' . $rand . 't'];
                            $tbid = $rand;
                            if ($battlepart[4] > $battlepart[3]) {
                                $info_cat = "" . $catp_pic . ", " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.";
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                if ($tbid >= 19 && $tbid != 99) {
                                    $database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                }
                                $buildarray = $GLOBALS["bid" . $tbgid];
                                if ($tbgid == 10 || $tbgid == 38) {
                                    $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxstore = $t_sql['maxstore'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxstore < 800)
                                        $tmaxstore = 800 * 32;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 11 || $tbgid == 39) {
                                    $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxcrop = $t_sql['maxcrop'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxcrop < 800)
                                        $tmaxcrop = 800 * 32;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 18) {
                                    $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                }
                                if ($isoasis == 0) {
                                    $pop = $this->recountPop($data['to']);
                                }
                                if ($isoasis == 0) {
                                    $pop = $this->recountPop($data['to']);
                                    if ($pop == '0') {
                                        if ($can_destroy == 1) {
                                            $village_destroyed = 1;
                                        }
                                    }
                                }
                            } elseif ($battlepart[4] == 0) {
                                $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.";
                            } else {
                                $demolish = $battlepart[4] / $battlepart[3];
                                $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - (($battlepart[4] / 2) * 8)));
                                if ($tblevel == $totallvl)
                                    $info_cata = " was not damaged.";
                                else {
                                    $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                    $buildarray = $GLOBALS["bid" . $tbgid];
                                    if ($tbgid == 10) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxstore = $t_sql['maxstore'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxstore < 800)
                                            $tmaxstore = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxcrop < 800)
                                            $tmaxcrop = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 18) {
                                        $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                    }
                                    $pop = $this->recountPop($data['to']);
                                }
                                $info_cat = "" . $catp_pic . "," . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata;
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                            }
                            $bdo2 = mysql_query("select * from " . TB_PREFIX . "fdata where vref = $basearray");
                            $bdo = mysql_fetch_array($bdo2);
                            $rand = $data['ctar2'];
                            if ($rand != 99) {
                                $_rand = array();
                                $__rand = array();
                                $j = 0;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i . 't'] == $rand && $bdo['f' . $i] > 0 && $rand != 31 && $rand != 32 && $rand != 33) {
                                        $j++;
                                        $_rand[$j] = $bdo['f' . $i];
                                        $__rand[$j] = $i;
                                    }
                                }
                                if (count($_rand) > 0) {
                                    if (max($_rand) <= 0)
                                        $rand = 99;
                                    else {
                                        $rand = rand(1, $j);
                                        $rand = $__rand[$rand];
                                    }
                                } else {
                                    $rand = 99;
                                }
                            }

                            if ($rand == 99) {
                                $list = array();
                                $j = 0;
                                for ($i = 1; $i <= 41; $i++) {
                                    if ($i == 41)
                                        $i = 99;
                                    if ($bdo['f' . $i] > 0) {
                                        $j++;
                                        $list[$j] = $i;
                                    }
                                }
                                $rand = rand(1, $j);
                                $rand = $list[$rand];
                            }

                            $tblevel = $bdo['f' . $rand];
                            $tbgid = $bdo['f' . $rand . 't'];
                            $tbid = $rand;
                            if ($battlepart[4] > $battlepart[3]) {
                                $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
					<img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . " destroyed.</td></tr></tbody>";
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", '0');
                                if ($tbid >= 19 && $tbid != 99) {
                                    $database->setVillageLevel($data['to'], "f" . $tbid . "t", '0');
                                }
                                $buildarray = $GLOBALS["bid" . $tbgid];
                                if ($tbgid == 10 || $tbgid == 38) {
                                    $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxstore = $t_sql['maxstore'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxstore < 800)
                                        $tmaxstore = 800;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 11 || $tbgid == 39) {
                                    $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                    $t_sql = mysql_fetch_array($tsql);
                                    $tmaxcrop = $t_sql['maxcrop'] - $buildarray[$tblevel]['attri'];
                                    if ($tmaxcrop < 800)
                                        $tmaxcrop = 800;
                                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                    $database->query($q);
                                }
                                if ($tbgid == 18) {
                                    $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                }
                                if ($isoasis == 0) {
                                    $pop = $this->recountPop($data['to']);
                                }
                                if ($isoasis == 0) {
                                    $pop = $this->recountPop($data['to']);
                                    if ($pop == '0' && $can_destroy == 1) {
                                        $village_destroyed = 1;
                                    }
                                }
                            } elseif ($battlepart[4] == 0) {
                                $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
					<img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . " was not damaged.</td></tr></tbody>";
                            } else {
                                $demolish = $battlepart[4] / $battlepart[3];
                                $totallvl = round(sqrt(pow(($tblevel + 0.5), 2) - (($battlepart[4] / 2) * 8)));
                                if ($tblevel == $totallvl)
                                    $info_cata = " was not damaged.";
                                else {
                                    $info_cata = " damaged from level <b>" . $tblevel . "</b> to level <b>" . $totallvl . "</b>.";
                                    $buildarray = $GLOBALS["bid" . $tbgid];
                                    if ($tbgid == 10 || $tbgid == 38) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxstore = $t_sql['maxstore'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxstore < 800)
                                            $tmaxstore = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`='" . $tmaxstore . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 11 || $tbgid == 39) {
                                        $tsql = mysql_query("select `maxstore`  from " . TB_PREFIX . "vdata where wref=" . $data['to'] . "");
                                        $t_sql = mysql_fetch_array($tsql);
                                        $tmaxcrop = $t_sql['maxcrop'] + $buildarray[$totallvl]['attri'] - $buildarray[$tblevel]['attri'];
                                        if ($tmaxcrop < 800)
                                            $tmaxcrop = 800;
                                        $q = "UPDATE " . TB_PREFIX . "vdata SET `maxcrop`='" . $tmaxcrop . "' WHERE wref=" . $data['to'];
                                        $database->query($q);
                                    }
                                    if ($tbgid == 18) {
                                        $this->updateMax($database->getVillageField($data['to'], 'owner'));
                                    }
                                    if ($isoasis == 0) {
                                        $pop = $this->recountPop($data['to']);
                                    }
                                }

                                $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
					<img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> " . $this->procResType($tbgid, $can_destroy, $isoasis) . $info_cata . "</td></tr></tbody>";
                                $database->setVillageLevel($data['to'], "f" . $tbid . "", $totallvl);
                            }
                        }
                    }
                }
            }

            //chiefing village
            //there are senators
            if (($data['t9'] - $dead9 - $traped9) > 0) {
                if ($type == '3') {

                    $palacelevel = $database->getResourceLevel($from['wref']);
                    for ($i = 1; $i <= 40; $i++) {
                        if ($palacelevel['f' . $i . 't'] == 26) {
                            $plevel = $i;
                        } else if ($palacelevel['f' . $i . 't'] == 25) {
                            $plevel = $i;
                        }
                    }
                    if ($palacelevel['f' . $plevel . 't'] == 26) {
                        if ($palacelevel['f' . $plevel] < 10) {
                            $canconquer = 0;
                        } elseif ($palacelevel['f' . $plevel] < 15) {
                            $canconquer = 1;
                        } elseif ($palacelevel['f' . $plevel] < 20) {
                            $canconquer = 2;
                        } else {
                            $canconquer = 3;
                        }
                    } else if ($palacelevel['f' . $plevel . 't'] == 25) {
                        if ($palacelevel['f' . $plevel] < 10) {
                            $canconquer = 0;
                        } elseif ($palacelevel['f' . $plevel] < 20) {
                            $canconquer = 1;
                        } else {
                            $canconquer = 2;
                        }
                    }

                    $exp1 = $database->getVillageField($from['wref'], 'exp1');
                    $exp2 = $database->getVillageField($from['wref'], 'exp2');
                    $exp3 = $database->getVillageField($from['wref'], 'exp3');
                    if ($exp1 == 0) {
                        $villexp = 0;
                    } elseif ($exp2 == 0) {
                        $villexp = 1;
                    } elseif ($exp3 == 0) {
                        $villexp = 2;
                    } else {
                        $villexp = 3;
                    }
                    $varray = $database->getProfileVillages($to['owner']);
                    $varray1 = count($database->getProfileVillages($from['owner']));
                    $mode = CP;
                    $cp_mode = $GLOBALS['cp' . $mode];
                    $need_cps = $cp_mode[$varray1 + 1];
                    $user_cps = $database->getUserField($from['owner'], "cp", 0);
                    //kijken of laatste dorp is, of hoofddorp
                    if ($user_cps >= $need_cps) {
                        if (count($varray) != '1' AND $to['capital'] != '1' AND $villexp < $canconquer) {
                            if ($to['owner'] != 3 OR $to['name'] != 'WW Buildingplan') {
                                //if there is no Palace/Residence
                                for ($i = 18; $i < 39; $i++) {
                                    if ($database->getFieldLevel($data['to'], "" . $i . "t") == 25 or $database->getFieldLevel($data['to'], "" . $i . "t") == 26) {
                                        $nochiefing = '1';
                                        $info_chief = "" . $chief_pic . ",The Palace/Residence isn\'t destroyed!";
                                    }
                                }
                                if (!isset($nochiefing)) {
                                    //$info_chief = "".$chief_pic.",You don't have enought CP to chief a village.";
                                    if ($this->getTypeLevel(35, $data['from']) == 0) {
                                        for ($i = 0; $i < ($data['t9'] - $dead9); $i++) {
                                            if ($owntribe == 1) {
                                                $rand+=rand(20, 30);
                                            } else {
                                                $rand+=rand(20, 25);
                                            }
                                        }
                                    } else {
                                        for ($i = 0; $i < ($data['t9'] - $dead9); $i++) {
                                            $rand+=rand(5, 15);
                                        }
                                    }
                                    //loyalty is more than 0
                                    if (($toF['loyalty'] - $rand) > 0) {
                                        $info_chief = "" . $chief_pic . ",The loyalty was lowered from <b>" . floor($toF['loyalty']) . "</b> to <b>" . floor($toF['loyalty'] - $rand) . "</b>.";
                                        $database->setVillageField($data['to'], loyalty, ($toF['loyalty'] - $rand));
                                    } else {
                                        //you took over the village
                                        $villname = addslashes($database->getVillageField($data['to'], "name"));
                                        $artifact = $database->getOwnArtefactInfo($data['to']);
                                        $info_chief = "" . $chief_pic . ",Inhabitants of " . $villname . " village decided to join your empire.";
                                        if ($artifact['vref'] == $data['to']) {
                                            $database->claimArtefact($data['to'], $data['to'], $database->getVillageField($data['from'], "owner"));
                                        }
                                        $database->setVillageField($data['to'], loyalty, 0);
                                        $database->setVillageField($data['to'], owner, $database->getVillageField($data['from'], "owner"));
                                        //delete upgrades in armory and blacksmith
                                        $q = "DELETE FROM " . TB_PREFIX . "abdata WHERE vref = " . $data['to'] . "";
                                        $database->query($q);
                                        $database->addABTech($data['to']);
                                        //delete researches in academy
                                        $q = "DELETE FROM " . TB_PREFIX . "tdata WHERE vref = " . $data['to'] . "";
                                        $database->query($q);
                                        $database->addTech($data['to']);
                                        //delete reinforcement
                                        $q = "DELETE FROM " . TB_PREFIX . "enforcement WHERE `from` = " . $data['to'] . "";
                                        $database->query($q);
                                        // check buildings
                                        $pop1 = $database->getVillageField($data['from'], "pop");
                                        $pop2 = $database->getVillageField($data['to'], "pop");
                                        if ($pop1 > $pop2) {
                                            $buildlevel = $database->getResourceLevel($data['to']);
                                            for ($i = 1; $i <= 39; $i++) {
                                                if ($buildlevel['f' . $i] != 0) {
                                                    if ($buildlevel['f' . $i . "t"] != 35 && $buildlevel['f' . $i . "t"] != 36 && $buildlevel['f' . $i . "t"] != 41) {
                                                        $leveldown = $buildlevel['f' . $i] - 1;
                                                        $database->setVillageLevel($data['to'], "f" . $i, $leveldown);
                                                    } else {
                                                        $database->setVillageLevel($data['to'], "f" . $i, 0);
                                                        $database->setVillageLevel($data['to'], "f" . $i . "t", 0);
                                                    }
                                                }
                                            }
                                            if ($buildlevel['f99'] != 0) {
                                                $leveldown = $buildlevel['f99'] - 1;
                                                $database->setVillageLevel($data['to'], "f99", $leveldown);
                                            }
                                        }
                                        //destroy wall
                                        $database->setVillageLevel($data['to'], "f40", 0);
                                        $database->setVillageLevel($data['to'], "f40t", 0);
                                        $database->clearExpansionSlot($data['to']);


                                        $exp1 = $database->getVillageField($data['from'], 'exp1');
                                        $exp2 = $database->getVillageField($data['from'], 'exp2');
                                        $exp3 = $database->getVillageField($data['from'], 'exp3');

                                        if ($exp1 == 0) {
                                            $exp = 'exp1';
                                            $value = $data['to'];
                                        } elseif ($exp2 == 0) {
                                            $exp = 'exp2';
                                            $value = $data['to'];
                                        } else {
                                            $exp = 'exp3';
                                            $value = $data['to'];
                                        }
                                        $database->setVillageField($data['from'], $exp, $value);
                                        //remove oasis related to village
                                        $units->returnTroops($data['to'], 1);
                                        $chiefing_village = 1;
                                    }
                                }
                            } else {
                                $info_chief = "" . $chief_pic . ",You cant take over this village.";
                            }
                        } else {
                            $info_chief = "" . $chief_pic . ",You cant take over this village.";
                        }
                    } else {
                        $info_chief = "" . $chief_pic . ",Not enough culture points.";
                    }
                    unset($plevel);
                } else {
                    $info_chief = "" . $chief_pic . ",Could not reduce cultural points during raid";
                }
            }

            if (($data['t11'] - $dead11 - $traped11) > 0) { //hero
                if ($heroxp == 0) {
                    $xp = "";
                    $info_hero = $hero_pic . ",Your hero had nothing to kill therefore gains no XP at all";
                } else {
                    $xp = " and gained " . $heroxp . " XP from the battle";
                    $info_hero = $hero_pic . ",Your hero gained " . $heroxp . " XP";
                }

                if ($isoasis != 0) { //oasis 
                    if ($to['owner'] != $from['owner']) {
                        $troopcount = $database->countOasisTroops($data['to']);
                        $canqured = $database->canConquerOasis($data['from'], $data['to']);
                        if ($canqured == 1 && $troopcount == 0) {
                            $database->conquerOasis($data['from'], $data['to']);
                            $info_hero = $hero_pic . ",Your hero has conquered this oasis" . $xp;
                        } else {
                            if ($canqured == 3 && $troopcount == 0) {
                                if ($type == '3') {
                                    $Oloyaltybefore = intval($to['loyalty']);
                                    //$database->modifyOasisLoyalty($data['to']);
                                    //$OasisInfo = $database->getOasisInfo($data['to']);
                                    $Oloyaltynow = intval($database->modifyOasisLoyalty($data['to'])); //intval($OasisInfo['loyalty']);
                                    $info_hero = $hero_pic . ",Your hero has reduced oasis loyalty to " . $Oloyaltynow . " from " . $Oloyaltybefore . $xp;
                                } else {
                                    $info_hero = $hero_pic . ",Could not reduce loyalty during raid" . $xp;
                                }
                            }
                        }
                    }
                } else {
                    global $form;
                    if ($heroxp == 0) {
                        $xp = " no XP from the battle";
                    } else {
                        $xp = " gained " . $heroxp . " XP from the battle";
                    }
                }
            } elseif ($data['t11'] > 0) {
                if ($heroxp == 0) {
                    $xp = "";
                } else {
                    $xp = " but gained " . $heroxp . " XP from the battle";
                }
                if ($traped11 > 0) {
                    $info_hero = $hero_pic . ",Your hero was trapped" . $xp;
                } else
                    $info_hero = $hero_pic . ",Your hero died" . $xp;
            }
            if ($DefenderID == 0) {
                $natar = 0;
            }
            if ($scout) {
                if ($data['spy'] == 1) {
                    $info_spy = "" ;
                } else if ($data['spy'] == 2) {
                    if ($isoasis == 0) {
                        $resarray = $database->getResourceLevel($data['to']);


                        $crannylevel = 0;
                        $rplevel = 0;
                        $walllevel = 0;
                        for ($j = 19; $j <= 40; $j++) {
                            if ($resarray['f' . $j . 't'] == 25 || $resarray['f' . $j . 't'] == 26) {

                                $rplevel = $database->getFieldLevel($data['to'], $j);
                            }
                        }
                        for ($j = 19; $j <= 40; $j++) {
                            if ($resarray['f' . $j . 't'] == 31 || $resarray['f' . $j . 't'] == 32 || $resarray['f' . $j . 't'] == 33) {

                                $walllevel = $database->getFieldLevel($data['to'], $j);
                            }
                        }
                        for ($j = 19; $j <= 40; $j++) {
                            if ($resarray['f' . $j . 't'] == 23) {

                                $crannylevel = $database->getFieldLevel($data['to'], $j);
                            }
                        }
                    } else {
                        $crannylevel = 0;
                        $walllevel = 0;
                        $rplevel = 0;
                    }

                    $palaceimg = "<img src=\"" . GP_LOCATE . "img/g/g26.gif\" height=\"20\" width=\"15\" alt=\"Palace\" title=\"Palace\" />";
                    $crannyimg = "<img src=\"" . GP_LOCATE . "img/g/g23.gif\" height=\"20\" width=\"15\" alt=\"Cranny\" title=\"Cranny\" />";
                    $wallimg = "<img src=\"" . GP_LOCATE . "img/g/g3" . $targettribe . "Icon.gif\" height=\"20\" width=\"15\" alt=\"Wall\" title=\"Wall\" />";
                    $info_spy = "" . $spy_pic . "," . $palaceimg . " Residance/Palace Level : " . $rplevel . "

				<br>" . $crannyimg . " Cranny level: " . $crannylevel . "<br>" . $wallimg . " Wall level : " . $walllevel . "";
                }

                $data2 = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',0,0,0,0,0,' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_def[1] . ',' . $unitsdead_def[1] . ',' . $ger . ',' . $unitssend_def[2] . ',' . $unitsdead_def[2] . ',' . $gal . ',' . $unitssend_def[3] . ',' . $unitsdead_def[3] . ',' . $nat . ',' . $unitssend_def[4] . ',' . $unitsdead_def[4] . ',' . $natar . ',' . $unitssend_def[5] . ',' . $unitsdead_def[5] . ',' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_spy . ',,' . $data['t11'] . ',' . $dead11 . ',' . $herosend_def . ',' . $deadhero . ',' . $unitstraped_att;
            } else {
                if ($village_destroyed == 1 && $can_destroy == 1) {
                    //check if village pop=0 and no info destroy
                    if (strpos($info_cat, "The village has") == 0) {
                        $info_cat .= "<br><tbody class=\"goods\"><tr><th>Information</th><td colspan=\"11\">
                                          <img class=\"unit u" . $catp_pic . "\" src=\"img/x.gif\" alt=\"Catapult\" title=\"Catapult\" /> The village has been destroyed.</td></tr></tbody>";
                    }
                }
                $data2 = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',' . $steal[0] . ',' . $steal[1] . ',' . $steal[2] . ',' . $steal[3] . ',' . $battlepart['bounty'] . ',' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_def[1] . ',' . $unitsdead_def[1] . ',' . $ger . ',' . $unitssend_def[2] . ',' . $unitsdead_def[2] . ',' . $gal . ',' . $unitssend_def[3] . ',' . $unitsdead_def[3] . ',' . $nat . ',' . $unitssend_def[4] . ',' . $unitsdead_def[4] . ',' . $natar . ',' . $unitssend_def[5] . ',' . $unitsdead_def[5] . ',' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_spy . ',,' . $data['t11'] . ',' . $dead11 . ',' . $herosend_def . ',' . $deadhero . ',' . $unitstraped_att;
            }





            // When all troops die, sends no info...send info
            $info_troop = "None of your soldiers have returned";
            $data_fail = '' . $from['owner'] . ',' . $from['wref'] . ',' . $owntribe . ',' . $unitssend_att . ',' . $unitsdead_att . ',' . $steal[0] . ',' . $steal[1] . ',' . $steal[2] . ',' . $steal[3] . ',' . $battlepart['bounty'] . ',' . $to['owner'] . ',' . $to['wref'] . ',' . addslashes($to['name']) . ',' . $targettribe . ',,,' . $rom . ',' . $unitssend_deff[1] . ',' . $unitsdead_deff[1] . ',' . $ger . ',' . $unitssend_deff[2] . ',' . $unitsdead_deff[2] . ',' . $gal . ',' . $unitssend_deff[3] . ',' . $unitsdead_deff[3] . ',' . $nat . ',' . $unitssend_deff[4] . ',' . $unitsdead_deff[4] . ',' . $natar . ',' . $unitssend_deff[5] . ',' . $unitsdead_deff[5] . ',,,' . $data['t11'] . ',' . $dead11 . ',' . $unitstraped_att . ',,' . $info_ram . ',' . $info_cat . ',' . $info_chief . ',' . $info_troop . ',' . $info_hero;

            //Undetected and detected in here.
            if ($scout) {

                for ($i = 1; $i <= 10; $i++) {
                    if ($battlepart['casualties_attacker'][$i]) {
                        if ($from['owner'] == 3) {
                            $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                            break;
                        } else if ($unitsdead_att == $unitssend_att && $defspy) {
                            $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                            break;
                        } else if ($defspy) {
                            $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 21, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                            break;
                        }
                    }
                }
            } else {
                if ($type == 3 && $totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {
                    $prisoners = $database->getPrisoners($to['wref']);
                    if (count($prisoners) > 0) {
                        $anothertroops = 0;
                        $mytroops = 0;
                        foreach ($prisoners as $prisoner) {
                            $p_owner = $database->getVillageField($prisoner['from'], "owner");
                            if ($p_owner == $from['owner']) {
                                $database->modifyAttack2($data['ref'], 1, $prisoner['t1']);
                                $database->modifyAttack2($data['ref'], 2, $prisoner['t2']);
                                $database->modifyAttack2($data['ref'], 3, $prisoner['t3']);
                                $database->modifyAttack2($data['ref'], 4, $prisoner['t4']);
                                $database->modifyAttack2($data['ref'], 5, $prisoner['t5']);
                                $database->modifyAttack2($data['ref'], 6, $prisoner['t6']);
                                $database->modifyAttack2($data['ref'], 7, $prisoner['t7']);
                                $database->modifyAttack2($data['ref'], 8, $prisoner['t8']);
                                $database->modifyAttack2($data['ref'], 9, $prisoner['t9']);
                                $database->modifyAttack2($data['ref'], 10, $prisoner['t10']);
                                $database->modifyAttack2($data['ref'], 11, $prisoner['t11']);
                                $mytroops += $prisoner['t1'] + $prisoner['t2'] + $prisoner['t3'] + $prisoner['t4'] + $prisoner['t5'] + $prisoner['t6'] + $prisoner['t7'] + $prisoner['t8'] + $prisoner['t9'] + $prisoner['t10'] + $prisoner['t11'];
                                $database->deletePrisoners($prisoner['id']);
                            } else {
                                $p_alliance = $database->getUserField($p_owner, "alliance", 0);
                                $friendarray = $database->getAllianceAlly($p_alliance, 1);
                                $neutralarray = $database->getAllianceAlly($p_alliance, 2);
                                $friend = (($friendarray[0]['alli1'] > 0 and $friendarray[0]['alli2'] > 0 and $p_alliance > 0) and ( $friendarray[0]['alli1'] == $ownally or $friendarray[0]['alli2'] == $ownally) and ( $ownally != $p_alliance and $ownally and $p_alliance)) ? '1' : '0';
                                $neutral = (($neutralarray[0]['alli1'] > 0 and $neutralarray[0]['alli2'] > 0 and $p_alliance > 0) and ( $neutralarray[0]['alli1'] == $ownally or $neutralarray[0]['alli2'] == $ownally) and ( $ownally != $p_alliance and $ownally and $p_alliance)) ? '1' : '0';
                                if ($p_alliance == $ownally or $friend == 1 or $neutral == 1) {
                                    $p_tribe = $database->getUserField($p_owner, "tribe", 0);

                                    $p_eigen = $database->getCoor($prisoner['wref']);
                                    $p_from = array('x' => $p_eigen['x'], 'y' => $p_eigen['y']);
                                    $p_ander = $database->getCoor($prisoner['from']);
                                    $p_to = array('x' => $p_ander['x'], 'y' => $p_ander['y']);
                                    $p_tribe = $database->getUserField($p_owner, "tribe", 0);

                                    $p_speeds = array();

                                    //find slowest unit.
                                    for ($i = 1; $i <= 10; $i++) {
                                        if ($prisoner['t' . $i]) {
                                            if ($prisoner['t' . $i] != '' && $prisoner['t' . $i] > 0) {
                                                if ($p_unitarray) {
                                                    reset($p_unitarray);
                                                }
                                                $p_unitarray = $GLOBALS["u" . (($p_tribe - 1) * 10 + $i)];
                                                $p_speeds[] = $p_unitarray['speed'];
                                            }
                                        }
                                    }



                                    $p_fastertroops = 1;

                                    $p_time = round($this->procDistanceTime($p_to, $p_from, min($p_speeds), 1) / $p_fastertroops);


                                    $p_reference = $database->addAttack($prisoner['from'], $prisoner['t1'], $prisoner['t2'], $prisoner['t3'], $prisoner['t4'], $prisoner['t5'], $prisoner['t6'], $prisoner['t7'], $prisoner['t8'], $prisoner['t9'], $prisoner['t10'], $prisoner['t11'], 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                                    $database->addMovement(4, $prisoner['wref'], $prisoner['from'], $p_reference, microtime(true), ($p_time + microtime(true)));
                                    $anothertroops += $prisoner['t1'] + $prisoner['t2'] + $prisoner['t3'] + $prisoner['t4'] + $prisoner['t5'] + $prisoner['t6'] + $prisoner['t7'] + $prisoner['t8'] + $prisoner['t9'] + $prisoner['t10'] + $prisoner['t11'];
                                    $database->deletePrisoners($prisoner['id']);
                                }
                            }
                        }

                        $newtraps = round(($mytroops + $anothertroops) / 3);
                        $database->modifyUnit($data['to'], array("99"), array($newtraps), array(0));
                        $database->modifyUnit($data['to'], array("99o"), array($mytroops + $anothertroops), array(0));
                        $trapper_pic = "<img src=\"" . GP_LOCATE . "img/u/98.gif\" alt=\"Trap\" title=\"Trap\" />";
                        $p_username = $database->getUserField($from['owner'], "username", 0);
                        if ($mytroops > 0 && $anothertroops > 0) {
                            $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $mytroops . "</b> from his troops and <b>" . $anothertroops . "</b> friendly troops.";
                        } elseif ($mytroops > 0) {
                            $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $mytroops . "</b> from his troops.";
                        } elseif ($anothertroops > 0) {
                            $info_trap = "" . $trapper_pic . " " . $p_username . " released <b>" . $anothertroops . "</b> friendly troops.";
                        }
                    }
                }
                if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {
                    $info_troop = "";
                }
                $data2 = $data2 . ',' . addslashes($info_trap) . ',,' . $info_troop . ',' . $info_hero;
                if ($totalsend_alldef == 0) {
                    $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 7, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                } else if ($totaldead_alldef == 0) {
                    $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 4, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                } else if ($totalsend_alldef > $totaldead_alldef) {
                    $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 5, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                } else if ($totalsend_alldef == $totaldead_alldef) {
                    $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 6, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                }
            }
            //to here
            // If the dead units not equal the ammount sent they will return and report
            if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 0) {

                $fastertroops = 1;

                $endtime = round($this->procDistanceTime($from, $to, min($speeds), 1) / $fastertroops);

                $endtime += $AttackArrivalTime;
                if ($type == 1) {
                    if ($from['owner'] == 3) { //fix natar report by dkd
                        $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 20, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    } elseif ($totaldead_att == 0 && $totaltraped_att == 0) {
                        $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 18, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    } else {
                        $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 21, '' . addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                } else {
                    if ($totaldead_att == 0 && $totaltraped_att == 0) {
                        $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 1, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    } else {
                        $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 2, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data2, $AttackArrivalTime);
                    }
                }

                $database->setMovementProc($data['moveid']);
                if ($chiefing_village != 1) {
                    $database->addMovement(4, $DefenderWref, $AttackerWref, $data['ref'], $AttackArrivalTime, $endtime);

                    // send the bounty on type 6.
                    if ($type !== 1) {

                        $reference = $database->sendResource($steal[0], $steal[1], $steal[2], $steal[3], 0, 0);
                        if ($isoasis == 0) {
                            $database->modifyResource($DefenderWref, $steal[0], $steal[1], $steal[2], $steal[3], 0);
                        } else {
                            $database->modifyOasisResource($DefenderWref, $steal[0], $steal[1], $steal[2], $steal[3], 0);
                        }
                        $database->addMovement(6, $DefenderWref, $AttackerWref, $reference, $AttackArrivalTime, $endtime, 1, 0, 0, 0, 0, $data['ref']);
                        $totalstolengain = $steal[0] + $steal[1] + $steal[2] + $steal[3];
                        $totalstolentaken = ($totalstolentaken - ($steal[0] + $steal[1] + $steal[2] + $steal[3]));
                        $database->modifyPoints($from['owner'], 'RR', $totalstolengain);
                        $database->modifyPoints($to['owner'], 'RR', $totalstolentaken);
                        $database->modifyPointsAlly($targetally, 'RR', $totalstolentaken);
                        $database->modifyPointsAlly($ownally, 'RR', $totalstolengain);
                    }
                } else { //if only 1 chief left to conqured - don't add with zero enforces
                    if ($totalsend_att - ($totaldead_att + $totaltraped_att) > 1) {
                        $database->addEnforce2($data, $owntribe, $troopsdead1, $troopsdead2, $troopsdead3, $troopsdead4, $troopsdead5, $troopsdead6, $troopsdead7, $troopsdead8, $troopsdead9, $troopsdead10, $troopsdead11);
                    }
                }
            } else { //else they die and don't return or report.
                $database->setMovementProc($data['moveid']);
                if ($type == 1) {
                    $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 19, addslashes($from['name']) . ' scouts ' . addslashes($to['name']) . '', $data_fail, $AttackArrivalTime);
                } else {
                    $database->addNotice($from['owner'], $to['wref'], $world_id, $ownally, 3, '' . addslashes($from['name']) . ' attacks ' . addslashes($to['name']) . '', $data_fail, $AttackArrivalTime);
                }
            }
            if ($type == 3 or $type == 4) {
                $database->addGeneralAttack($totalattackdead);
            }
            if ($village_destroyed == 1) {
                if ($can_destroy == 1) {
                    $this->DelVillage($data['to']);
                }
            }

            //check if not natar tribe
            $getvillage = $database->getVillage($to['wref']);
            if ($getvillage['owner'] != 3) {
                $unitarrays = $this->getAllUnits($to['wref']);
                $village_upkeep = $getvillage['pop'] + $this->getUpkeep($unitarrays, 0);
                $starv = $getvillage['starv'];
               
                unset($unitarrays, $getvillage, $village_upkeep);
            }

            #### PHP.NET manual: unset() destroy more than one variable unset($foo1, $foo2, $foo3);######
            ################################################################################
            $data_num++;
            unset(
                    $Attacker
                    , $Defender
                    , $enforce
                    , $unitssend_att
                    , $unitssend_def
                    , $battlepart
                    , $unitlist
                    , $unitsdead_def
                    , $dead
                    , $steal
                    , $from
                    , $data
                    , $data2
                    , $to
                    , $artifact
                    , $artifactBig
                    , $canclaim
                    , $data_fail
                    , $owntribe
                    , $unitsdead_att
                    , $herosend_def
                    , $deadhero
                    , $heroxp
                    , $AttackerID
                    , $DefenderID
                    , $totalsend_att
                    , $totalsend_alldef
                    , $totaldead_att
                    , $totaltraped_att
                    , $totaldead_def
                    , $unitsdead_att_check
                    , $totalattackdead
                    , $enforce1
                    , $defheroowner
                    , $enforceowner
                    , $defheroxp
                    , $reinfheroxp
                    , $AttackerWref
                    , $DefenderWref
                    , $troopsdead1
                    , $troopsdead2
                    , $troopsdead3
                    , $troopsdead4
                    , $troopsdead5
                    , $troopsdead6
                    , $troopsdead7
                    , $troopsdead8
                    , $troopsdead9
                    , $troopsdead10
                    , $troopsdead11
                    , $DefenderUnit);

            #################################################
        }
        if (file_exists("GameEngine/Prevention/sendunits.txt")) {
            unlink("GameEngine/Prevention/sendunits.txt");
        }
       
    }

    function DelVillage($wref, $mode = 0) {
        global $database, $units;
        $database->clearExpansionSlot($wref);
        $q = "DELETE FROM " . TB_PREFIX . "abdata where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "bdata where wid = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "market where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "odata where wref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "research where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "tdata where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "fdata where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "training where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "units where vref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "farmlist where wref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "raidlist where towref = $wref";
        $database->query($q);
        $q = "DELETE FROM " . TB_PREFIX . "movement where proc = 0 AND ((`to` = $wref AND sort_type=4) OR (`from` = $wref AND sort_type=3))";
        $database->query($q);

        $getmovement = $database->getMovement(3, $wref, 1);
        foreach ($getmovement as $movedata) {
            $time = microtime(true);
            $time2 = $time - $movedata['starttime'];
            $database->setMovementProc($movedata['moveid']);
            $database->addMovement(4, $movedata['to'], $movedata['from'], $movedata['ref'], $time, $time + $time2);
        }
        $q = "DELETE FROM " . TB_PREFIX . "enforcement WHERE `from` = $wref";
        $database->query($q);

        //check    return enforcement from del village
        $units->returnTroops($wref);

        $q = "DELETE FROM " . TB_PREFIX . "vdata WHERE `wref` = $wref";
        $database->query($q);

        if (mysql_affected_rows() > 0) {
            $q = "UPDATE " . TB_PREFIX . "wdata set occupied = 0 where id = $wref";
            $database->query($q);

            $getprisoners = $database->getPrisoners($wref);
            foreach ($getprisoners as $pris) {
                $troops = 0;
                for ($i = 1; $i < 12; $i++) {
                    $troops += $pris['t' . $i];
                }
                $database->modifyUnit($pris['wref'], array("99o"), array($troops), array(0));
                $database->deletePrisoners($pris['id']);
            }
            $getprisoners = $database->getPrisoners3($wref);
            foreach ($getprisoners as $pris) {
                $troops = 0;
                for ($i = 1; $i < 12; $i++) {
                    $troops += $pris['t' . $i];
                }
                $database->modifyUnit($pris['wref'], array("99o"), array($troops), array(0));
                $database->deletePrisoners($pris['id']);
            }
        }
    }

    private function sendTroopsBack($post) {
        global $form, $database, $village, $generator, $session, $technology;

        $enforce = $database->getEnforceArray($post['ckey'], 0);
        $to = $database->getVillage($enforce['from']);
        $Gtribe = "1";

        for ($i = 1; $i < 10; $i++) {
            if (isset($post['t' . $i])) {
                if ($i != 10) {
                    if ($post['t' . $i] > $enforce['u' . $Gtribe . $i]) {
                        $form->addError("error", "You can't send more units than you have");
                        break;
                    }

                    if ($post['t' . $i] < 0) {
                        $form->addError("error", "You can't send negative units.");
                        break;
                    }
                }
            } else {
                $post['t' . $i . ''] = '0';
            }
        }
        if (isset($post['t11'])) {
            if ($post['t11'] > $enforce['hero']) {
                $form->addError("error", "You can't send more units than you have");
                break;
            }

            if ($post['t11'] < 0) {
                $form->addError("error", "You can't send negative units.");
                break;
            }
        } else {
            $post['t11'] = '0';
        }

        if ($form->returnErrors() > 0) {
            $_SESSION['errorarray'] = $form->getErrors();
            $_SESSION['valuearray'] = $_POST;
            header("Location: a2b.php");
        } else {

            //change units
            $start = 11;
            $end = 20;

            $j = '1';
            for ($i = $start; $i <= $end; $i++) {
                $database->modifyEnforce($post['ckey'], $i, $post['t' . $j . ''], 0);
                $j++;
            }

            //get cord
            $from = $database->getVillage($enforce['from']);
            $fromcoor = $database->getCoor($enforce['from']);
            $tocoor = $database->getCoor($enforce['vref']);
            $fromCor = array('x' => $tocoor['x'], 'y' => $tocoor['y']);
            $toCor = array('x' => $fromcoor['x'], 'y' => $fromcoor['y']);

            $speeds = array();

            //find slowest unit.
            for ($i = 1; $i <= 10; $i++) {
                if (isset($post['t' . $i])) {
                    if ($post['t' . $i] != '' && $post['t' . $i] > 0) {
                        if ($unitarray) {
                            reset($unitarray);
                        }
                        $unitarray = $GLOBALS["u" . (10 + $i)];
                        $speeds[] = $unitarray['speed'];
                    } else {
                        $post['t' . $i . ''] = '0';
                    }
                } else {
                    $post['t' . $i . ''] = '0';
                }
            }
            if (isset($post['t11'])) {
                if ($post['t11'] != '' && $post['t11'] > 0) {
                    $qh = "SELECT * FROM " . TB_PREFIX . "hero WHERE uid = " . $from['owner'] . "";
                    $resulth = mysql_query($qh);
                    $hero_f = mysql_fetch_array($resulth);
                    $hero_unit = $hero_f['unit'];
                    $speeds[] = $GLOBALS['u' . $hero_unit]['speed'];
                } else {
                    $post['t11'] = '0';
                }
            } else {
                $post['t11'] = '0';
            }
            $artefact = count($database->getOwnUniqueArtefactInfo2($from['owner'], 2, 3, 0));
            $artefact1 = count($database->getOwnUniqueArtefactInfo2($from['vref'], 2, 1, 1));
            $artefact2 = count($database->getOwnUniqueArtefactInfo2($from['owner'], 2, 2, 0));
            if ($artefact > 0) {
                $fastertroops = 3;
            } else if ($artefact1 > 0) {
                $fastertroops = 2;
            } else if ($artefact2 > 0) {
                $fastertroops = 1.5;
            } else {
                $fastertroops = 1;
            }
            $time = round($generator->procDistanceTime($fromCor, $toCor, min($speeds), 1) / $fastertroops);
            $foolartefact4 = $database->getFoolArtefactInfo(2, $from['wref'], $from['owner']);
            if (count($foolartefact4) > 0) {
                foreach ($foolartefact4 as $arte) {
                    if ($arte['bad_effect'] == 1) {
                        $time *= $arte['effect2'];
                    } else {
                        $time /= $arte['effect2'];
                        $time = round($endtime);
                    }
                }
            }
            $reference = $database->addAttack($enforce['from'], $post['t1'], $post['t2'], $post['t3'], $post['t4'], $post['t5'], $post['t6'], $post['t7'], $post['t8'], $post['t9'], $post['t10'], $post['t11'], 2, 0, 0, 0, 0);
            $database->addMovement(4, $village->wid, $enforce['from'], $reference, $AttackArrivalTime, ($time + $AttackArrivalTime));
            $technology->checkReinf($post['ckey']);

            header("Location: build.php?id=39");
        }
    }

    private function sendreinfunitsComplete() {
        if (file_exists("GameEngine/Prevention/sendreinfunits.txt")) {
            unlink("GameEngine/Prevention/sendreinfunits.txt");
        }
        global $bid23, $database, $battle, $session;
        $reload = false;
        $time = time();
        $ourFileHandle = fopen("GameEngine/Prevention/sendreinfunits.txt", 'w');
        fclose($ourFileHandle);
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = '0' and " . TB_PREFIX . "movement.sort_type = '3' and " . TB_PREFIX . "attacks.attack_type = '2' and endtime < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {
            $isoasis = $database->isVillageOases($data['to']);
            if ($isoasis == 0) {
                $to = $database->getMInfo($data['to']);
                $toF = $database->getVillage($data['to']);
                $DefenderID = $to['owner'];
                $targettribe = 2;
                $conqureby = 0;
            } else {
                $to = $database->getOMInfo($data['to']);
                $toF = $database->getOasisV($data['to']);
                $DefenderID = $to['owner'];
                $targettribe = 2;
                $conqureby = $toF['conqured'];
            }
            $world_id = $to['worldid'];
            if ($data['from'] == 0) {
                $DefenderID = $database->getVillageField($data['to'], "owner");
                if ($session->uid == $AttackerID || $session->uid == $DefenderID)
                    $reload = true;
                $database->addEnforce($data);
                $reinf = $database->getEnforce($data['to'], $data['from']);
                $database->modifyEnforce($reinf['id'], 31, 1, 1);
                $data_fail = '0,0,4,1,0,0,0,0,0,0,0,0,0,0';
                $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 8, 'village of the elders reinforcement ' . addslashes($to['name']) . '', $data_fail, $AttackArrivalTime);
                $database->setMovementProc($data['moveid']);
                if ($session->uid == $DefenderID)
                    $reload = true;
            }else {
                //set base things
                $from = $database->getMInfo($data['from']);
                $world_id = $from['worldid'];
                $fromF = $database->getVillage($data['from']);
                $AttackerID = $from['owner'];
                $owntribe = 2;


                if ($session->uid == $AttackerID || $session->uid == $DefenderID)
                    $reload = true;

                $HeroTransfer = 0;
                $troopsPresent = 0;
                for ($i = 1; $i <= 15; $i++) {
                    if ($data['t' . $i] > 0) {
                        $troopsPresent = 1;
                        break;
                    }
                }

                if ($troopsPresent) {
                    //check if there is defence from town in to town
                    $check = $database->getEnforce($data['to'], $data['from']);
                    if (!isset($check['id'])) {
                        //no:
                        $database->addEnforce($data);
                    } else {
                        //yes
                        $start = 11;
                        $end = 25;
                        //add unit.
                        $j = '1';
                        for ($i = $start; $i <= $end; $i++) {
                            $t_units.="u" . $i . "=u" . $i . " + " . $data['t' . $j] . (($j > 14) ? '' : ', ');
                            $j++;
                        }
                        $q = "UPDATE " . TB_PREFIX . "enforcement set $t_units where id =" . $check['id'];
                        $database->query($q);
//                            $database->modifyEnforce($check['id'],'hero',$data['t11'],1);
                    }
                }
                //send rapport
                $unitssend_att = '' . $data['t1'] . ',' . $data['t2'] . ',' . $data['t3'] . ',' . $data['t4'] . ',' . $data['t5'] . ',' . $data['t6'] . ',' . $data['t7'] . ',' . $data['t8'] . ',' . $data['t9'] . ',' . $data['t10'] . ',' . $data['t11'] . ',' . $data['t12'] . ',' . $data['t13'] . ',' . $data['t14'] . ',' . $data['t15'] . '';
                $data_fail = '' . $from['wref'] . ',' . $from['owner'] . ',' . $owntribe . ',' . $unitssend_att . '';


                if ($isoasis == 0) {
                    $to_name = $to['name'];
                } else {
                    $to_name = "Oasis " . $database->getVillageField($to['conqured'], "name");
                }
                $database->addNotice($from['owner'], $from['wref'], $world_id, $ownally, 8, '' . addslashes($from['name']) . ' reinforcement ' . addslashes($to_name) . '', $data_fail, $AttackArrivalTime);
                if ($from['owner'] != $to['owner']) {
                    $database->addNotice($to['owner'], $to['wref'], $world_id, $targetally, 8, '' . addslashes($from['name']) . ' reinforcement ' . addslashes($to_name) . '', $data_fail, $AttackArrivalTime);
                }
                //update status
                $database->setMovementProc($data['moveid']);
            }
            $farm = $database->getCropProdstarv($data['to']);
            $unitarrays = $this->getAllUnits($data['to']);
            $village = $database->getVillage($data['to']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
            $starv = $database->getVillageField($data['to'], "starv");
            if ($farm < $upkeep) {
                // add starv data
                $database->setVillageField($data['to'], 'starv', $upkeep);
                if ($starv == 0) {
                    $database->setVillageField($data['to'], 'starvupdate', $time);
                }
            }

            //check empty reinforcement in rally point
            $e_units = '';
            for ($i = 1; $i <= 50; $i++) {
                $e_units.='u' . $i . '=0 AND ';
            }
            $e_units.='hero=0';
            $q = "DELETE FROM " . TB_PREFIX . "enforcement WHERE " . $e_units . " AND (vref=" . $data['to'] . " OR `from`=" . $data['to'] . ")";
            $database->query($q);
        }

        if (file_exists("GameEngine/Prevention/sendreinfunits.txt")) {
            unlink("GameEngine/Prevention/sendreinfunits.txt");
        }
       
    }

    private function returnunitsComplete() {
        if (file_exists("GameEngine/Prevention/returnunits.txt")) {
            unlink("GameEngine/Prevention/returnunits.txt");
        }
        global $database;
        $ourFileHandle = fopen("GameEngine/Prevention/returnunits.txt", 'w');
        fclose($ourFileHandle);
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "attacks where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "attacks.id and " . TB_PREFIX . "movement.proc = '0' and " . TB_PREFIX . "movement.sort_type = '4' and endtime < $time";
        $dataarray = $database->query_return($q);

        foreach ($dataarray as $data) {

            $tribe = $database->getUserField($database->getVillageField($data['to'], "owner"), "tribe", 0);

            if ($tribe == 1) {
                $u = "";
            } elseif ($tribe == 2) {
                $u = "1";
            } elseif ($tribe == 3) {
                $u = "2";
            } elseif ($tribe == 4) {
                $u = "3";
            } else {
                $u = "4";
            }
            $database->modifyUnit(
                    $data['to'], array($u . "1", $u . "2", $u . "3", $u . "4", $u . "5", $u . "6", $u . "7", $u . "8", $u . "9", $tribe . "0", "hero"), array($data['t1'], $data['t2'], $data['t3'], $data['t4'], $data['t5'], $data['t6'], $data['t7'], $data['t8'], $data['t9'], $data['t10'], $data['t11']), array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
            );
            $database->setMovementProc($data['moveid']);
            $farm = $database->getCropProdstarv($data['to']);
            $unitarrays = $this->getAllUnits($data['to']);
            $village = $database->getVillage($data['to']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
            $starv = $database->getVillageField($data['to'], "starv");
            if ($farm < $upkeep) {
                // add starv data
                $database->setVillageField($data['to'], 'starv', $upkeep);
                if ($starv == 0) {
                    $database->setVillageField($data['to'], 'starvupdate', $time);
                }
            }
        }

        // Recieve the bounty on type 6.

        $q = "SELECT * FROM " . TB_PREFIX . "movement, " . TB_PREFIX . "send where " . TB_PREFIX . "movement.ref = " . TB_PREFIX . "send.id and " . TB_PREFIX . "movement.proc = 0 and sort_type = 6 and endtime < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {

            if ($data['wood'] >= $data['oil'] && $data['wood'] >= $data['iron'] && $data['wood'] >= $data['farm']) {
                $sort_type = "10";
            } elseif ($data['oil'] >= $data['wood'] && $data['oil'] >= $data['iron'] && $data['oil'] >= $data['farm']) {
                $sort_type = "11";
            } elseif ($data['iron'] >= $data['wood'] && $data['iron'] >= $data['oil'] && $data['iron'] >= $data['farm']) {
                $sort_type = "12";
            } elseif ($data['farm'] >= $data['wood'] && $data['farm'] >= $data['oil'] && $data['farm'] >= $data['iron']) {
                $sort_type = "13";
            }

            $to = $database->getMInfo($data['to']);
            $from = $database->getMInfo($data['from']);
            $database->modifyResource($data['to'], $data['wood'], $data['oil'], $data['iron'], $data['farm'], 1);
            //$database->updateVillage($data['to']);
            $database->setMovementProc($data['moveid']);
            $farm = $database->getCropProdstarv($data['to']);
            $unitarrays = $this->getAllUnits($data['to']);
            $village = $database->getVillage($data['to']);
            $upkeep = $village['pop'] + $this->getUpkeep($unitarrays, 0);
            $starv = $database->getVillageField($data['to'], "starv");
            if ($farm < $upkeep) {
                // add starv data
                $database->setVillageField($data['to'], 'starv', $upkeep);
                if ($starv == 0) {
                    $database->setVillageField($data['to'], 'starvupdate', $time);
                }
            }
        }

        $this->pruneResource();

        // Settlers

        $q = "SELECT * FROM " . TB_PREFIX . "movement where ref = 0 and proc = '0' and sort_type = '4' and endtime < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {

            $tribe = $database->getUserField($database->getVillageField($data['to'], "owner"), "tribe", 0);

            $database->modifyUnit($data['to'], array($tribe . "0"), array(3), array(1));
            $database->setMovementProc($data['moveid']);
        }

        if (file_exists("GameEngine/Prevention/returnunits.txt")) {
            unlink("GameEngine/Prevention/returnunits.txt");
        }
    }

    private function sendSettlersComplete() {
        if (file_exists("GameEngine/Prevention/settlers.txt")) {
            unlink("GameEngine/Prevention/settlers.txt");
        }
        global $database, $building, $session;
        $ourFileHandle = fopen("GameEngine/Prevention/settlers.txt", 'w');
        fclose($ourFileHandle);
        $time = microtime(true);
        $q = "SELECT * FROM " . TB_PREFIX . "movement where proc = 0 and sort_type = 5 and endtime < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {
            $ownerID = $database->getUserField($database->getVillageField($data['from'], "owner"), "id", 0);
            if ($session->uid == $ownerID)
            $to = $database->getMInfo($data['from']);
            $user = addslashes($database->getUserField($to['owner'], 'username', 0));
            $taken = $database->getVillageState($data['to']);
            if ($taken != 1) {
                $database->setFieldTaken($data['to']);
                $database->addVillage($data['to'], $to['owner'], $user, '0');
                $database->addResourceFields($data['to'], $database->getVillageType($data['to']));
                $database->addUnits($data['to']);
                $database->addTech($data['to']);
                $database->addABTech($data['to']);
                $database->setMovementProc($data['moveid']);

                $exp1 = $database->getVillageField($data['from'], 'exp1');
                $exp2 = $database->getVillageField($data['from'], 'exp2');
                $exp3 = $database->getVillageField($data['from'], 'exp3');

                if ($exp1 == 0) {
                    $exp = 'exp1';
                    $value = $data['to'];
                } elseif ($exp2 == 0) {
                    $exp = 'exp2';
                    $value = $data['to'];
                } else {
                    $exp = 'exp3';
                    $value = $data['to'];
                }
                $database->setVillageField($data['from'], $exp, $value);
            } else {
                // here must come movement from returning settlers
                $database->addMovement(4, $data['to'], $data['from'], $data['ref'], $time, $time + ($time - $data['starttime']));
                $database->setMovementProc($data['moveid']);
            }
        }
        if (file_exists("GameEngine/Prevention/settlers.txt")) {
            unlink("GameEngine/Prevention/settlers.txt");
        }
       
    }

    private function researchComplete() { //done
        if (file_exists("GameEngine/Prevention/research.txt")) {
            unlink("GameEngine/Prevention/research.txt");
        }
        global $database;
        $ourFileHandle = fopen("GameEngine/Prevention/research.txt", 'w');
        fclose($ourFileHandle);
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "research where timestamp < $time";
        $dataarray = $database->query_return($q);
        foreach ($dataarray as $data) {
            $sort_type = substr($data['tech'], 0, 1);
            switch ($sort_type) {
                case "t":
                    $q = "UPDATE " . TB_PREFIX . "tdata set " . $data['tech'] . " = " . $data['tech'] . " + 1 where vref = " . $data['vref'];
                    break;
                case "a":
                case "b":
                    $q = "UPDATE " . TB_PREFIX . "abdata set " . $data['tech'] . " = " . $data['tech'] . " + 1 where vref = " . $data['vref'];
                    break;
            }
            $database->query($q);
            $q = "DELETE FROM " . TB_PREFIX . "research where id = " . $data['id'];
            $database->query($q);
        }
        if (file_exists("GameEngine/Prevention/research.txt")) {
            unlink("GameEngine/Prevention/research.txt");
        }
    }

    private function updateRes($bountywid, $uid) { //done
        global $session;


        $this->bountyLoadTown($bountywid);
        $this->bountycalculateProduction($bountywid, $uid);
        $this->bountyprocessProduction($bountywid);
    }

    private function updateORes($bountywid) { //done
        global $session;
        $this->bountyLoadOTown($bountywid);
        $this->bountycalculateOProduction($bountywid);
        $this->bountyprocessOProduction($bountywid);
    }

    private function bountyLoadOTown($bountywid) {  //done
        global $database, $session, $logging, $technology;
        $this->bountyinfoarray = $database->getOasisV($bountywid);
        $this->bountyresarray = $database->getResourceLevel($bountywid);
        $this->bountypop = 2;
    }

    private function bountyLoadTown($bountywid) {  //done
        global $database, $session, $logging, $technology;
        $this->bountyinfoarray = $database->getVillage($bountywid);
        $this->bountyresarray = $database->getResourceLevel($bountywid);
        $this->bountyoasisowned = $database->getOasis($bountywid);
        $this->bountyocounter = $this->bountysortOasis();
        $this->bountypop = $this->bountyinfoarray['pop'];
    }

    private function bountysortOasis() { // oasis type only 1-9 only done
        $oil = $wood = $iron = 0;
        foreach ($this->bountyoasisowned as $oasis) {
            switch ($oasis['type']) {
                case 1:
                case 2:
                case 3:
                    $wood += 1;
                    break;
                case 4:
                case 5:
                case 6:
                    $oil += 1;
                    break;
                case 7:
                case 8:
                case 9:
                    $iron += 1;
                    break;
            }
        }
        return array($wood, $oil, $iron);
    }

    function getAllUnits($base) {
        global $database;
        $ownunit = $database->getUnit($base);
        $enforcementarray = $database->getEnforceVillage($base, 0);
        if (count($enforcementarray) > 0) {
            foreach ($enforcementarray as $enforce) {
                for ($i = 11; $i <= 25; $i++) {
                    $ownunit['u' . $i] += $enforce['u' . $i];
                }
            }
        }
        $enforceoasis = $database->getOasisEnforce($base, 0);
        if (count($enforceoasis) > 0) {
            foreach ($enforceoasis as $enforce) {
                for ($i = 11; $i <= 25; $i++) {
                    $ownunit['u' . $i] += $enforce['u' . $i];
                }
            }
        }
        $enforceoasis1 = $database->getOasisEnforce($base, 1);
        if (count($enforceoasis1) > 0) {
            foreach ($enforceoasis1 as $enforce) {
                for ($i = 1; $i <= 50; $i++) {
                    $ownunit['u' . $i] += $enforce['u' . $i];
                }
            }
        }
        $movement = $database->getVillageMovement($base);
        if (!empty($movement)) {
            for ($i = 1; $i <= 50; $i++) {
                $ownunit['u' . $i] += $movement['u' . $i];
            }
        }

        return $ownunit;
    }

    public function getUpkeep($array, $type, $vid = 0, $prisoners = 0) { //done
        global $database, $session, $village;

        if ($vid == 0) {
            $vid = $village->wid;
        }
        $buildarray = array();
        if ($vid != 0) {
            $buildarray = $database->getResourceLevel($vid);
        }
        $upkeep = 0;
        $start = 11;
        $end = 30;
        for ($i = $start; $i <= $end; $i++) {
            $k = $i - $start + 1;
            $unit = "u" . $i;
            $unit2 = "t" . $k;
            global $$unit;
            $dataarray = $$unit;

            if ($prisoners == 0) {
                $upkeep += $dataarray['pop'] * $array[$unit];
            } else {
                $upkeep += $dataarray['pop'] * $array[$unit2];
            }
        }

        return $upkeep;
    }

    private function bountycalculateOProduction($bountywid) {  //done
        global $technology, $database;
        $this->bountyOproduction['wood'] = $this->bountyGetOWoodProd();
        $this->bountyOproduction['oil'] = $this->bountyGetOOilProd();
        $this->bountyOproduction['iron'] = $this->bountyGetOIronProd();
    }

    private function bountycalculateProduction($bountywid, $uid) {  //done
        global $technology, $database;
        $this->bountyproduction['wood'] = $this->bountyGetWoodProd();
        $this->bountyproduction['oil'] = $this->bountyGetOilProd();
        $this->bountyproduction['iron'] = $this->bountyGetIronProd();
    }

    private function bountyprocessProduction($bountywid) {  //done
        global $database;
        $timepast = time() - $this->bountyinfoarray['lastupdate'];
        $nwood = ($this->bountyproduction['wood'] / 3600) * $timepast;
        $noil = ($this->bountyproduction['oil'] / 3600) * $timepast;
        $niron = ($this->bountyproduction['iron'] / 3600) * $timepast;
        $database->modifyResource($bountywid, $nwood, $noil, $niron, 1);
        $database->updateVillage($bountywid);
    }

    private function bountyprocessOProduction($bountywid) {  //done
        global $database;
        $timepast = time() - $this->bountyinfoarray['lastupdated'];
        $nwood = ($this->bountyproduction['wood'] / 3600) * $timepast;
        $noil = ($this->bountyproduction['oil'] / 3600) * $timepast;
        $niron = ($this->bountyproduction['iron'] / 3600) * $timepast;
        $database->modifyOasisResource($bountywid, $nwood, $noil, $niron, 1);
        $database->updateOasis($bountywid);
    }

    private function bountyGetWoodProd() {  //done
        global $bid1, $session;
        $wood = 0;
        $wood+= $bid1[$this->bountyresarray['f1']]['prod'];
        $wood *= SPEED;
        return round($wood);
    }

    private function bountyGetOWoodProd() {  //done
        global $session;
        $wood = 0;
        $wood += 40;
        $wood *= SPEED;
        return round($wood);
    }

    private function bountyGetOOilProd() {  //done
        global $session;
        $oil = 0;
        $oil += 40;
        $oil *= SPEED;
        return round($oil);
    }

    private function bountyGetOIronProd() {  //done
        global $session;
        $iron = 0;
        $iron += 40;
        $iron *= SPEED;
        return round($iron);
    }

    private function bountyGetOilProd() {  //done
        global $bid2, $session;
        $oil = 0;
        $oil+= $bid2[$this->bountyresarray['f2']]['prod'];
        $oil *= SPEED;
        return round($oil);
    }

    private function bountyGetIronProd() {  //done
        global $bid3, $session;
        $iron = 0;
        $iron+= $bid3[$this->bountyresarray['f3']]['prod'];
        $iron *= SPEED;
        return round($iron);
    }

    private function trainingComplete() {  //done
        if (file_exists("GameEngine/Prevention/training.txt")) {
            unlink("GameEngine/Prevention/training.txt");
        }
        global $database, $building, $quest;
        $time = time();
        $ourFileHandle = fopen("GameEngine/Prevention/training.txt", 'w');
        fclose($ourFileHandle);
        $trainlist = $database->getTrainingList();
        if (count($trainlist) > 0) {
            foreach ($trainlist as $train) {
                $timepast = $train['timestamp2'] - $time;
                $pop = $train['pop'];
                if ($timepast <= 0 && $train['amt'] > 0) {
                    $timepast2 = $time - $train['timestamp2'];
                    $trained = 1;
                    while ($timepast2 >= $train['eachtime']) {
                        $timepast2 -= $train['eachtime'];
                        $trained += 1;
                    }
                    if ($trained > $train['amt']) {
                        $trained = $train['amt'];
                    }
                    $database->modifyUnit($train['vref'], array($train['unit']), array($trained), array(1));
                    $database->updateTraining($train['id'], $trained, $trained * $train['eachtime']);
                }
                if ($train['amt'] == 0) {
                    $database->trainUnit($train['id'], 0, 0, 0, 0, 1, 1);
                }
                $VunitArray = $database->getUnit($train['vref']);
                $quest->questTroops($database->getVillageField($train['vref'], 'owner'), $train['vref'], $database->getVillageField($train['vref'], 'world_id'), $VunitArray['u'.$train['unit']], $train['unit']);
                
                $village = $database->getVillage($train['vref']);
                $unitarrays = $this->getAllUnits($train['vref']);
                $inTriningUpkeep = 0;
                $unitUpkeep = $this->getUpkeep($unitarrays, 0, $train['vref']);
                $buildingUpKeep = $village['pop'];
                $trininglist = $database->getTraining($train['vref']);
                foreach ($trininglist as $train) {
                    $inTriningUpkeep+= $train['pop'] * $train['amt'];
                }

                $maxUpkeep = $building->farmupkeep($train['vref']);
                $upkeepConsuption = $unitUpkeep + $buildingUpKeep + $inTriningUpkeep;

                $starv = $database->getVillageField($train['vref'], "starv");
                if ($maxUpkeep < $upkeepConsuption) {
                    // add starv data
                    $database->setVillageField($train['vref'], 'starv', $upkeepConsuption);
                    if ($starv == 0) {
                        $database->setVillageField($train['vref'], 'starvupdate', $time);
                    }
                }
            }
        }
        if (file_exists("GameEngine/Prevention/training.txt")) {
            unlink("GameEngine/Prevention/training.txt");
        }
    }

    public function procDistanceTime($coor, $thiscoor, $ref, $mode) {
        $xdistance = ABS($thiscoor['x'] - $coor['x']);
        $ydistance = ABS($thiscoor['y'] - $coor['y']);
        $distance = SQRT(POW($xdistance, 2) + POW($ydistance, 2));
        if (!$mode) {
            if ($ref == 1) {
                $speed = 16;
            } else if ($ref == 2) {
                $speed = 12;
            } else if ($ref == 3) {
                $speed = 24;
            } else if ($ref == 300) {
                $speed = 5;
            } else {
                $speed = 1;
            }
        } else {
            $speed = $ref;
        }
        if ($speed != 0) {
            return round(($distance / $speed) * 3600 / INCREASE_SPEED);
        } else {
            return round($distance * 3600 / INCREASE_SPEED);
        }
    }

    private function getsort_typeLevel($tid, $resarray) {  //done
        global $village;
        return $resarray['f' . $tid];
    }

    private function demolitionComplete() { //done
        if (file_exists("GameEngine/Prevention/demolition.txt")) {
            unlink("GameEngine/Prevention/demolition.txt");
        }
        global $building, $database, $village;
        $ourFileHandle = fopen("GameEngine/Prevention/demolition.txt", 'w');
        fclose($ourFileHandle);

        $varray = $database->getDemolition();
        foreach ($varray as $vil) {
            if ($vil['timetofinish'] <= time()) {
                $type = $vil['buildnumber'];
                $level = $vil['lvl'];
                $buildarray = $GLOBALS["bid" . $type];
                if ($type == 10) {
                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`=`maxstore`-" . $buildarray[$level]['attri'] . " WHERE wref=" . $vil['vref'];
                    $database->query($q);
                    $q = "UPDATE " . TB_PREFIX . "vdata SET `maxstore`=800 WHERE `maxstore`<= 800 AND wref=" . $vil['vref'];
                    $database->query($q);
                }


                if ($level == 1) {
                    $clear = ",f" . $vil['buildnumber'] . "=0";
                } else {
                    $clear = "";
                }

                $q = "UPDATE " . TB_PREFIX . "fdata SET f" . $vil['buildnumber'] . "=" . ($level) . " WHERE vref=" . $vil['vref'];
                $database->query($q);
                $pop = $this->getPop($type, $level - 1);
                $database->modifyPop($vil['vref'], $pop[0], 1);
                $this->procClimbers($database->getVillageField($vil['vref'], 'owner'), $database->getVillageField($vil['vref'], 'world_id'), $database->getVillageField($vil['vref'], 'alliance'));
                $database->delDemolition($vil['vref']);
            }
        }
        if (file_exists("GameEngine/Prevention/demolition.txt")) {
            unlink("GameEngine/Prevention/demolition.txt");
        }
    }

    private function updateHero() {
        if (file_exists("GameEngine/Prevention/updatehero.txt")) {
            unlink("GameEngine/Prevention/updatehero.txt");
        }
        global $database, $hero_levels;
        $harray = $database->getHero();
        if (!empty($harray)) {
            foreach ($harray as $hdata) {
                if ((time() - $hdata['lastupdate']) >= 1) {
                    if ($hdata['health'] < 100 and $hdata['health'] > 0) {
                        if (SPEED <= 10) {
                            $speed = SPEED;
                        } else if (SPEED <= 100) {
                            $speed = ceil(SPEED / 10);
                        } else {
                            $speed = ceil(SPEED / 100);
                        }
                        $reg = $hdata['health'] + $hdata['regeneration'] * 5 * $speed / 86400 * (time() - $hdata['lastupdate']);
                        if ($reg <= 100) {
                            $database->modifyHero("health", $reg, $hdata['heroid']);
                        } else {
                            $database->modifyHero("health", 100, $hdata['heroid']);
                        }
                        $database->modifyHero("lastupdate", time(), $hdata['heroid']);
                    }
                }
                $herolevel = $hdata['level'];
                for ($i = $herolevel + 1; $i < 100; $i++) {
                    if ($hdata['experience'] >= $hero_levels[$i]) {
                        mysql_query("UPDATE " . TB_PREFIX . "hero SET level = $i WHERE heroid = '" . $hdata['heroid'] . "'");
                        if ($i < 99) {
                            mysql_query("UPDATE " . TB_PREFIX . "hero SET points = points + 5 WHERE heroid = '" . $hdata['heroid'] . "'");
                        }
                    }
                }
                $villunits = $database->getUnit($hdata['wref']);
                if ($villunits['hero'] == 0 && $hdata['trainingtime'] < time() && $hdata['inrevive'] == 1) {
                    mysql_query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = " . $hdata['wref'] . "");
                    mysql_query("UPDATE " . TB_PREFIX . "hero SET `dead` = '0', `inrevive` = '0', `health` = '100', `lastupdate` = " . $hdata['trainingtime'] . " WHERE `uid` = '" . $hdata['uid'] . "'");
                }
                if ($villunits['hero'] == 0 && $hdata['trainingtime'] < time() && $hdata['intraining'] == 1) {
                    mysql_query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = " . $hdata['wref'] . "");
                    mysql_query("UPDATE " . TB_PREFIX . "hero SET `intraining` = '0', `lastupdate` = " . $hdata['trainingtime'] . " WHERE `uid` = '" . $hdata['uid'] . "'");
                }
            }
        }
        if (file_exists("GameEngine/Prevention/updatehero.txt")) {
            unlink("GameEngine/Prevention/updatehero.txt");
        }
    }

    private function updateStore() { //done
        global $bid10;

        $result = mysql_query('SELECT * FROM `' . TB_PREFIX . 'fdata`');
        while ($row = mysql_fetch_assoc($result)) {
            $ress = 0;
            $ress += $bid10[$row['f10']]['attri'] * STORAGE_MULTIPLIER;
            mysql_query('UPDATE `' . TB_PREFIX . 'vdata` SET `maxstore` = ' . $ress . ' WHERE `wref` = ' . $row['vref']) or die(mysql_error());
        }
    }

    private function oasisResourcesProduce() {
        global $database;
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "odata WHERE wood < 800 OR oil < 800 OR iron < 800 OR farm < 800";
        $array = $database->query_return($q);
        foreach ($array as $getoasis) {
            $oasiswood = $getoasis['wood'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            $oasisoil = $getoasis['oil'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            $oasisiron = $getoasis['iron'] + (8 * SPEED / 3600) * (time() - $getoasis['lastupdated']);
            if ($oasiswood > $getoasis['maxstore']) {
                $oasiswood = $getoasis['maxstore'];
            }
            if ($oasisoil > $getoasis['maxstore']) {
                $oasisoil = $getoasis['maxstore'];
            }
            if ($oasisiron > $getoasis['maxstore']) {
                $oasisiron = $getoasis['maxstore'];
            }

            $q = "UPDATE " . TB_PREFIX . "odata set wood = $oasiswood, oil = $oasisoil, iron = $oasisiron where wref = " . $getoasis['wref'] . "";
            $database->query($q);
            $database->updateOasis($getoasis['wref']);
        }
    }

    private function checkInvitedPlayes() {
        global $database;
        $q = "SELECT * FROM " . TB_PREFIX . "users WHERE invited != 0";
        $array = $database->query_return($q);
        foreach ($array as $user) {
            $numusers = mysql_query("SELECT * FROM " . TB_PREFIX . "users WHERE id = " . $user['invited']);
            if (mysql_num_rows($numusers) > 0) {
                $varray = count($database->getProfileVillages($user['id']));
                if ($varray > 1) {
                    $usergold = $database->getUserField($user['invited'], "gold", 0);
                    $gold = $usergold + 50;
                    $database->updateUserField($user['invited'], "gold", $gold, 1);
                    $database->updateUserField($user['id'], "invited", 0, 1);
                }
            }
        }
    }

    private function updateGeneralAttack() {
        global $database;
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "general WHERE shown = 1";
        $array = $database->query_return($q);
        foreach ($array as $general) {
            if (time() - (86400 * 8) > $general['time']) {
                mysql_query("UPDATE " . TB_PREFIX . "general SET shown = 0 WHERE id = " . $general['id'] . "");
            }
        }
    }

    /*     * **********************************************
      References:
     * ********************************************** */

    private function starvation() {
        if (file_exists("GameEngine/Prevention/starvation.txt")) {
            unlink("GameEngine/Prevention/starvation.txt");
        }
        global $database, $village;
        $ourFileHandle = fopen("GameEngine/Prevention/starvation.txt", 'w');
        fclose($ourFileHandle);
        $time = time();

        //update starvation
        $getvillage = $database->getVillage($village->wid);
        $starv = $getvillage['starv'];
        if ($getvillage['owner'] != 3 && $starv == 0) {
            $farm = $database->getCropProdstarv($village->wid);
            $unitarrays = $this->getAllUnits($village->wid);
            $village_upkeep = $getvillage['pop'] + $this->getUpkeep($unitarrays, 0);
            
            unset($unitarrays, $getvillage, $village_upkeep);
        }

        // load villages with minus prod

        $starvarray = array();
        $starvarray = $database->getStarvation();
        foreach ($starvarray as $starv) {
            $unitarrays = $this->getAllUnits($starv['wref']);
            $howweeating = $this->getUpkeep($unitarrays, 0, $starv['wref']);
            $upkeep = $starv['pop'] + $howweeating;


            // get enforce other player from oasis
            $q = "SELECT e.*,o.conqured,o.wref,o.high, o.owner as ownero, v.owner as ownerv FROM " . TB_PREFIX . "enforcement as e LEFT JOIN " . TB_PREFIX . "odata as o ON e.vref=o.wref LEFT JOIN " . TB_PREFIX . "vdata as v ON e.from=v.wref where o.conqured=" . $starv['wref'] . " AND o.owner<>v.owner";
            $enforceoasis = $database->query_return($q);
            $maxcount = 0;
            $totalunits = 0;
            if (count($enforceoasis) > 0) {
                foreach ($enforceoasis as $enforce) {
                    for ($i = 1; $i <= 50; $i++) {
                        $units = $enforce['u' . $i];
                        if ($enforce['u' . $i] > $maxcount) {
                            $maxcount = $enforce['u' . $i];
                            $maxtype = $i;
                            $enf = $enforce['id'];
                        }
                        $totalunits += $enforce['u' . $i];
                    }
                    if ($totalunits == 0) {
                        $maxcount = $enforce['hero'];
                        $maxtype = "hero";
                    }
                    $other_reinf = true;
                }
            } else { //own troops from oasis
                $q = "SELECT e.*,o.conqured,o.wref,o.high, o.owner as ownero, v.owner as ownerv FROM " . TB_PREFIX . "enforcement as e LEFT JOIN " . TB_PREFIX . "odata as o ON e.vref=o.wref LEFT JOIN " . TB_PREFIX . "vdata as v ON e.from=v.wref where o.conqured=" . $starv['wref'] . " AND o.owner=v.owner";
                $enforceoasis = $database->query_return($q);
                if (count($enforceoasis) > 0) {
                    foreach ($enforceoasis as $enforceO) {
                        for ($i = 1; $i <= 50; $i++) {
                            $units = $enforce['u' . $i];
                            if ($enforce['u' . $i] > $maxcount) {
                                $maxcount = $enforce['u' . $i];
                                $maxtype = $i;
                                $enf = $enforce['id'];
                            }
                            $totalunits += $enforce['u' . $i];
                        }
                        if ($totalunits == 0) {
                            $maxcount = $enforce['hero'];
                            $maxtype = "hero";
                        }
                    }
                } else { //get enforce other player from village
                    $q = "SELECT e.*, v.owner as ownerv, v1.owner as owner1 FROM " . TB_PREFIX . "enforcement as e LEFT JOIN " . TB_PREFIX . "vdata as v ON e.from=v.wref LEFT JOIN " . TB_PREFIX . "vdata as v1 ON e.vref=v1.wref where e.vref=" . $starv['wref'] . " AND v.owner<>v1.owner";
                    $enforcearray = $database->query_return($q);
                    if (count($enforcearray) > 0) {
                        foreach ($enforcearray as $enforce) {
                            for ($i = 0; $i <= 50; $i++) {
                                $units = $enforce['u' . $i];
                                if ($enforce['u' . $i] > $maxcount) {
                                    $maxcount = $enforce['u' . $i];
                                    $maxtype = $i;
                                    $enf = $enforce['id'];
                                }
                                $totalunits += $enforce['u' . $i];
                            }
                            if ($totalunits == 0) {
                                $maxcount = $enforce['hero'];
                                $maxtype = "hero";
                            }
                        }
                    } else { //get own reinforcement from other village
                        $q = "SELECT e.*, v.owner as ownerv, v1.owner as owner1 FROM " . TB_PREFIX . "enforcement as e LEFT JOIN " . TB_PREFIX . "vdata as v ON e.from=v.wref LEFT JOIN " . TB_PREFIX . "vdata as v1 ON e.vref=v1.wref where e.vref=" . $starv['wref'] . " AND v.owner=v1.owner";
                        $enforcearray = $database->query_return($q);
                        if (count($enforcearray) > 0) {
                            foreach ($enforcearray as $enforce) {
                                for ($i = 0; $i <= 50; $i++) {
                                    $units = $enforce['u' . $i];
                                    if ($enforce['u' . $i] > $maxcount) {
                                        $maxcount = $enforce['u' . $i];
                                        $maxtype = $i;
                                        $enf = $enforce['id'];
                                    }
                                    $totalunits += $enforce['u' . $i];
                                }
                                if ($totalunits == 0) {
                                    $maxcount = $enforce['hero'];
                                    $maxtype = "hero";
                                }
                            }
                        } else { //get own unit
                            $unitarray = $database->getUnit($starv['wref']);
                            for ($i = 0; $i <= 50; $i++) {
                                $units = $unitarray['u' . $i];
                                if ($unitarray['u' . $i] > $maxcount) {
                                    $maxcount = $unitarray['u' . $i];
                                    $maxtype = $i;
                                }
                                $totalunits += $unitarray['u' . $i];
                            }
                            if ($totalunits == 0) {
                                $maxcount = $unitarray['hero'];
                                $maxtype = "hero";
                            }
                        }
                    }
                }
            }

            // counting

            $timedif = $time - $starv['starvupdate'];
            $skolko = $database->getCropProdstarv($starv['wref']) - $starv['starv'];
            if ($skolko < 0) {
                $golod = true;
            }
            if ($golod) {
                $starvsec = (abs($skolko) / 3600);
               

                if ($difcrop > 0) {
                    global ${u . $maxtype};
                    $hungry = array();
                    $hungry = ${u . $maxtype};
                   

                    if ($killunits > 0) {
                        $pskolko = abs($skolko);
                        if ($killunits > $pskolko && $skolko < 0) {
                            $killunits = $pskolko;
                        }
                        if (isset($enf)) {
                            if ($killunits < $maxcount) {
                                $database->modifyEnforce($enf, $maxtype, $killunits, 0);
                                $database->setVillageField($starv['wref'], 'starv', $upkeep);
                                $database->setVillageField($starv['wref'], 'starvupdate', $time);
                                $database->modifyResource($starv['wref'], 0, 0, 0, 1);
                                if ($maxtype == "hero") {
                                    $heroid = $database->getHeroField($database->getVillageField($enf, "owner"), "heroid");
                                    $database->modifyHero("dead", 1, $heroid);
                                }
                            } else {
                                $database->deleteReinf($enf);
                                $database->setVillageField($starv['wref'], 'starv', $upkeep);
                                $database->setVillageField($starv['wref'], 'starvupdate', $time);
                            }
                        } else {
                            if ($killunits < $maxcount) {
                                $database->modifyUnit($starv['wref'], array($maxtype), array($killunits), array(0));
                                $database->setVillageField($starv['wref'], 'starv', $upkeep);
                                $database->setVillageField($starv['wref'], 'starvupdate', $time);
                                $database->modifyResource($starv['wref'], 0, 0, 0, 1);
                                if ($maxtype == "hero") {
                                    $heroid = $database->getHeroField($starv['owner'], "heroid");
                                    $database->modifyHero("dead", 1, $heroid);
                                }
                            } elseif ($killunits > $maxcount) {
                                $killunits = $maxcount;
                                $database->modifyUnit($starv['wref'], array($maxtype), array($killunits), array(0));
                                $database->setVillageField($starv['wref'], 'starv', $upkeep);
                                $database->setVillageField($starv['wref'], 'starvupdate', $time);
                                if ($maxtype == "hero") {
                                    $heroid = $database->getHeroField($starv['owner'], "heroid");
                                    $database->modifyHero("dead", 1, $heroid);
                                }
                            }
                        }
                    }
                }
            }
            

            unset($starv, $unitarrays, $enforcearray, $enforce, $starvarray);
        }

        if (file_exists("GameEngine/Prevention/starvation.txt")) {
            unlink("GameEngine/Prevention/starvation.txt");
        }
    }

    /*     * **********************************************
      References:
     * ********************************************** */

    private function procNewClimbers() {
        if (file_exists("GameEngine/Prevention/climbers.txt")) {
            unlink("GameEngine/Prevention/climbers.txt");
        }
        global $database, $ranking;
        $ranking->procRankArray();
        $climbers = $ranking->getRank();
        if (count($ranking->getRank()) > 0) {
            $q = "SELECT * FROM " . TB_PREFIX . "medal order by week DESC LIMIT 0, 1";
            $result = mysql_query($q);
            if (mysql_num_rows($result)) {
                $row = mysql_fetch_assoc($result);
                $week = ($row['week'] + 1);
            } else {
                $week = '1';
            }
            $q = "SELECT * FROM " . TB_PREFIX . "usermeta where meta_key = 'oldrank' and meta_value = 0 and uid > 5";
            $array = $database->query_return($q);
            foreach ($array as $user) {
                $newrank = $ranking->getUserRank($user['uid'], $user['wid']);
                $climbers = $ranking->filter_by_value($climbers, 'world_id', $user['wid']);
                if ($week > 1) {
                    for ($i = $newrank + 1; $i < count($ranking->filter_by_value($ranking->getRank(), 'world_id', $user['wid'])); $i++) {
                        $oldrank = $ranking->getUserRank($climbers[$i]['userid'], $climbers[$i]['world_id']);
                        $totalpoints = $oldrank - $climbers[$i]['oldrank'];
                        $database->removeclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    }
                    $database->updateoldrank($user['uid'], $climbers[$i]['world_id'], $newrank);
                } else {
                    $totalpoints = count($ranking->filter_by_value($ranking->getRank(), 'world_id', $user['wid'])) - $newrank;
                    $database->setclimberrankpop($user['uid'], $user['wid'], $totalpoints);
                    $database->updateoldrank($user['uid'], $user['wid'], $newrank);
                    for ($i = 1; $i < $newrank; $i++) {
                        $oldrank = $ranking->getUserRank($climbers[$i]['userid'], $climbers[$i]['world_id']);
                        $totalpoints = count($ranking->filter_by_value($ranking->getRank(), 'world_id', $climbers[$i]['world_id'])) - $oldrank;
                        $database->setclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    }
                    for ($i = $newrank + 1; $i < count($ranking->filter_by_value($ranking->getRank(), 'world_id', $climbers[$i]['world_id'])); $i++) {
                        $oldrank = $ranking->getUserRank($climbers[$i]['userid'], $climbers[$i]['world_id']);
                        $totalpoints = count($ranking->filter_by_value($ranking->getRank(), 'world_id', $climbers[$i]['world_id'])) - $oldrank;
                        $database->setclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    }
                }
            }
        }
        if (file_exists("GameEngine/Prevention/climbers.txt")) {
            unlink("GameEngine/Prevention/climbers.txt");
        }
    }

    private function procClimbers($uid, $world_id, $aid) {
        global $database, $ranking;
        $ranking->procRankArray();
        $climbers = $ranking->getRank();
        $climbers = $this->filter_by_value($climbers, 'world_id', $wid);
        if (count($climbers) > 0) {
            $q = "SELECT * FROM " . TB_PREFIX . "medal order by week DESC LIMIT 0, 1";
            $result = mysql_query($q);
            if (mysql_num_rows($result)) {
                $row = mysql_fetch_assoc($result);
                $week = ($row['week'] + 1);
            } else {
                $week = '1';
            }
            $myrank = $ranking->getUserRank($uid, $world_id);
            if ($climbers[$myrank]['oldrank'] > $myrank) {
                for ($i = $myrank + 1; $i <= $climbers[$myrank]['oldrank']; $i++) {
                    $oldrank = $ranking->getUserRank($climbers[$i]['userid'], $climbers[$i]['world_id']);
                    if ($week > 1) {
                        $totalpoints = $oldrank - $climbers[$i]['oldrank'];
                        $database->removeclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    } else {
                        $totalpoints = count($climbers) - $oldrank;
                        $database->setclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    }
                }
                if ($week > 1) {
                    $totalpoints = $climbers[$myrank]['oldrank'] - $myrank;
                    $database->addclimberrankpop($climbers[$myrank]['userid'], $climbers[$i]['world_id'], $totalpoints);
                    $database->updateoldrank($climbers[$myrank]['userid'], $climbers[$i]['world_id'], $myrank);
                } else {
                    $totalpoints = count($climbers) - $myrank;
                    $database->setclimberrankpop($climbers[$myrank]['userid'], $climbers[$i]['world_id'], $totalpoints);
                    $database->updateoldrank($climbers[$myrank]['userid'], $climbers[$i]['world_id'], $myrank);
                }
            } else if ($climbers[$myrank]['oldrank'] < $myrank) {
                for ($i = $climbers[$myrank]['oldrank']; $i < $myrank; $i++) {
                    $oldrank = $ranking->getUserRank($climbers[$i]['userid'], $climbers[$i]['world_id']);
                    if ($week > 1) {
                        $totalpoints = $climbers[$i]['oldrank'] - $oldrank;
                        $database->addclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    } else {
                        $totalpoints = count($ranking->filter_by_value($ranking->getRank(), 'world_id', $climbers[$i]['world_id'])) - $oldrank;
                        $database->setclimberrankpop($climbers[$i]['userid'], $climbers[$i]['world_id'], $totalpoints);
                        $database->updateoldrank($climbers[$i]['userid'], $climbers[$i]['world_id'], $oldrank);
                    }
                }
                if ($week > 1) {
                    $totalpoints = $myrank - $climbers[$myrank - 1]['oldrank'];
                    $database->removeclimberrankpop($climbers[$myrank - 1]['userid'], $climbers[$i]['world_id'], $totalpoints);
                    $database->updateoldrank($climbers[$myrank - 1]['userid'], $climbers[$i]['world_id'], $myrank);
                } else {
                    $totalpoints = count($ranking->filter_by_value($ranking->getRank(), 'world_id', $climbers[$i]['world_id'])) - $myrank;
                    $database->setclimberrankpop($climbers[$myrank - 1]['userid'], $climbers[$i]['world_id'], $totalpoints);
                    $database->updateoldrank($climbers[$myrank - 1]['userid'], $climbers[$i]['world_id'], $myrank);
                }
            }
        }
        $ranking->procARankArray();
        $aid = $aid;
        if (count($climbers) > 0 && $aid != 0) {
            $ally = $database->getAlliance($aid);
            $memberlist = $database->getAllMember($ally['id']);
            $oldrank = 0;
            foreach ($memberlist as $member) {
                $oldrank += $database->getVSumField($member['id'], "pop", $world_id);
            }
            if ($ally['oldrank'] != $oldrank) {
                if ($ally['oldrank'] < $oldrank) {
                    $totalpoints = $oldrank - $ally['oldrank'];
                    $database->addclimberrankpopAlly($ally['id'], $totalpoints);
                    $database->updateoldrankAlly($ally['id'], $oldrank);
                } else
                if ($ally['oldrank'] > $oldrank) {
                    $totalpoints = $ally['oldrank'] - $oldrank;
                    $database->removeclimberrankpopAlly($ally['id'], $totalpoints);
                    $database->updateoldrankAlly($ally['id'], $oldrank);
                }
            }
        }
    }

    private function checkBan() {
        global $database;
        $time = time();
        $q = "SELECT * FROM " . TB_PREFIX . "banlist WHERE active = 1 and end < $time";
        $array = $database->query_return($q);
        foreach ($array as $banlist) {
            mysql_query("UPDATE " . TB_PREFIX . "banlist SET active = 0 WHERE id = " . $banlist['id'] . "");
            mysql_query("UPDATE " . TB_PREFIX . "users SET access = 2 WHERE id = " . $banlist['uid'] . "");
        }
    }

    private function regenerateOasisTroops() {
        global $database;
        $time = time();
        $time2 = NATURE_REGTIME;
        $q = "SELECT * FROM " . TB_PREFIX . "odata where conqured = 0 and lastupdated2 + $time2 < $time";
        $array = $database->query_return($q);
        foreach ($array as $oasis) {
            $database->populateOasisUnits($oasis['wref'], $oasis['high']);
            $database->updateOasis2($oasis['wref'], $time2);
        }
    }

    private function updateMax($leader) {
        global $bid18, $database;
        $q = mysql_query("SELECT * FROM " . TB_PREFIX . "alidata where leader = $leader");
        if (mysql_num_rows($q) > 0) {
            $villages = $database->getVillagesID2($leader);
            $max = 0;
            foreach ($villages as $village) {
                $field = $database->getResourceLevel($village['wref']);
                for ($i = 19; $i <= 40; $i++) {
                    if ($field['f' . $i . 't'] == 18) {
                        $level = $field['f' . $i];
                        $attri = $bid18[$level]['attri'];
                    }
                }
                if ($attri > $max) {
                    $max = $attri;
                }
            }
            $q = "UPDATE " . TB_PREFIX . "alidata set max = $max where leader = $leader";
            $database->query($q);
        }
    }

    private function checkReviveHero() {
        global $database, $session;
        $herodata = $database->getHero($session->uid, 1);
        if ($herodata[0]['dead'] == 1) {
            mysql_query("UPDATE " . TB_PREFIX . "units SET hero = 0 WHERE vref = " . $session->villages[0] . "");
        }
        if ($herodata[0]['trainingtime'] <= time()) {
            if ($herodata[0]['trainingtime'] != 0) {
                if ($herodata[0]['dead'] == 0) {
                    mysql_query("UPDATE " . TB_PREFIX . "hero SET trainingtime = '0' WHERE uid = " . $session->uid . "");
                    mysql_query("UPDATE " . TB_PREFIX . "units SET hero = 1 WHERE vref = " . $session->villages[0] . "");
                }
            }
        }
    }


}

$automation = new Automation;
