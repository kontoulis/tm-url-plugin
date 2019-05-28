<?php
/**
 * Created by PhpStorm.
 * User: Asmodai
 * Date: 28/5/2019
 * Time: 3:29 μμ
 * @param $aseco
 * @param $chat
 */
Aseco::registerEvent("onChat", "loginAutocomplete");
Aseco::registerEvent("onStartup", function($aseco){
    $aseco->pluginsChatReserve[] = '%';
});
function loginAutocomplete($aseco, $chat){
    // if % found in chatmessage, handle the login auto completer and nickname feature
    if (strpos($chat[2], '%') !== false)
    {

        // get playerlist on server once here for later uses
        $aseco->client->query('GetPlayerList', 255, 0, 2);
        $playerListOnServer = $aseco->client->getResponse();
        $fullLoginPlayerArray = array();
        foreach ($playerListOnServer as $player)
        {
            array_push($fullLoginPlayerArray, $player["Login"]);
        }
        $fullLoginPlayerArray = array_diff($fullLoginPlayerArray, [$aseco->server->serverlogin]);
        // type PARTIALLOGINNAME% of some player to autocomplete to his full login in chat
        $partialLoginMatches = array();
        preg_match_all('/([a-z0-9\.\_\-]+)\%{1}/', $chat[2], $partialLoginMatches);
        // get all matches, index [0] is useless, everything is in [1]
        $partialLoginMatches = $partialLoginMatches[1];
        for ($i = 0; $i < sizeof($partialLoginMatches); $i++)
        {
            if($partialLoginMatches[$i] === "server"){
                $chat[2] = str_replace($partialLoginMatches[$i] . '%', $aseco->server->serverlogin, $chat[2], $fullLoginInChatCount);
            }else {
                foreach ($fullLoginPlayerArray as $fullLoginPlayer) {
                    if (substr($fullLoginPlayer, 0, mb_strlen($partialLoginMatches[$i])) === $partialLoginMatches[$i]) {
                        $fullLoginInChatCount = 1;
                        $chat[2] = str_replace($partialLoginMatches[$i] . '%', $fullLoginPlayer, $chat[2], $fullLoginInChatCount);
                        break;
                    }
                }
            }
        }

        // type %LOGINNAME of some player to display his real nickname in your chat message
        $realNickMatches = array();
        preg_match_all('/\%{1}([a-z0-9\.\_\-]+)/', $chat[2], $realNickMatches);
        // get all matches, index [0] is useless, everything is in [1]
        $realNickMatches = $realNickMatches[1];
        for ($i = 0; $i < sizeof($realNickMatches); $i++)
        {
            if($realNickMatches[$i] === $aseco->server->serverlogin){
                $chat[2] = str_replace('%'.$realNickMatches[$i], $aseco->getServerName().'$z$g$s', $chat[2]);
            }elseif (in_array($realNickMatches[$i], $fullLoginPlayerArray))
            {
                $chat[2] = str_replace('%'.$realNickMatches[$i], $aseco->getPlayerNick($realNickMatches[$i]).'$z$g$s', $chat[2]);
            }
        }
        $aseco->client->query('ChatSendServerMessage', '$z$g$s['.$aseco->getPlayerNick($chat[1]).'$z$g$s] '.$chat[2]);
    }
}