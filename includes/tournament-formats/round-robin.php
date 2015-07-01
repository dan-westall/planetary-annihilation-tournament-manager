<?php



class WPTM_Tournament_Formats {

    private $tournament_id;
    private $player_groups;

    function __construct($tournament_id) {
        $this->tournament_id = $tournament_id;
    }


    public static function schedule_format($teams){

        if (count($teams)%2 != 0){
            array_push($teams,"bye");
        }
        $away = array_splice($teams,(count($teams)/2));
        $home = $teams;
        for ($i=0; $i < count($home)+count($away)-1; $i++){
            for ($j=0; $j<count($home); $j++){
                $round[$i][$j]["Home"]=$home[$j];
                $round[$i][$j]["Away"]=$away[$j];
            }
            if(count($home)+count($away)-1 > 2){
                array_unshift($away,array_shift(array_splice($home,1,1)));
                array_push($home,array_pop($away));
            }
        }

        return $round;
    }




}