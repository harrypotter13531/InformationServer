<?php

require_once 'Vo/AmfResponse.php';

class amfParentMailService
{
    //e.g. param1: QUEST_FINISHED param2: THE_LOST_LOVER
    //This function would be stored into a db with other activity logs
    //The activity report was sent on random days on email "Your childs activity on Panfu 20XX-XX-XX"
    //Last finished quest would be shown in the email, with the "values" the quest teaches the children
    //... so yeah, useless for us
    //but need to include it or else The Magic Ice Diamond won't send the final state to finish the quest
    public function trackString($type, $content){
        $response = new AmfResponse();
        //do nothing
        $response->statusCode = 0;
        return $response;
    }
}

?>