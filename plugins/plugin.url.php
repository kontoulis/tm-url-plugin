<?php
/*
=======================================================================
Description: Shows website or youtube video title in chat
Authors: amgreborn, asmodai
Version: v1.0
Dependencies: plugin.localdatabase.php
=======================================================================
*/
require_once 'includes/AbstractChatHandler.php';


require_once "includes/string_helper.php";

Aseco::registerEvent("onStartup", function($aseco){
    $aseco->registerChatHandler(new UrlChatHandler($aseco), 5);
});

class UrlChatHandler extends AbstractChatHandler{

    function __construct($aseco){
        parent::__construct($aseco);
        $this->aseco->pluginsChatReserve[] = '$l';
    }

    public function handle($chat)
    {
        if ($chat[1] === (string)$this->aseco->server->serverlogin || strpos($chat[2], '$l') === false ) {
            return $chat;
        }
        $chat[2] = preg_replace('/(https\:\/\/)/', 'http://', $chat[2]);
        $youtubes = [];
        $links = [];
        $results = explode(" ", $chat[2]);
        foreach($results as $i => &$result) {
            if(strpos($result, '$l') === 0) {
                $chat[2] = str_replace($result,'{link}',$chat[2]);
                $url = parse_url(trim(str_replace('$l', '', $result)));
                if (!isset($url["scheme"])) {
                    $url["scheme"] = "http";
                }
                parse_str($url["query"], $parsedQuery);
                if (in_array(str_replace("www.", "", $url["host"]), ["youtube.com", "youtu.be"])) {
                    $youtubeId = (isset($parsedQuery["v"]) ? $parsedQuery["v"] : $url["path"]);
                    $result = '$l$f00' . http_build_url($url);
                    $youtubes[$i] = $this->getYoutubeTitle($youtubeId) . ' - '.$result;
                } else {

                    $result = '$l$39f' . http_build_url($url);
                    $links[$i] = $this->getPageTitle($url). ' - '.$result;
                }
            }
        }



        foreach($results as $i => $result) {
            $message = "";
            if (isset($youtubes[$i]) && !empty($youtubes[$i])) {
                $message = $this->aseco->formatColors('$[$f00YouTube Bot$fff] ' . $youtubes[$i]);

            }elseif(isset($links[$i]) && !empty($links[$i])){
                $message = $this->aseco->formatColors('$[$f00URL Bot$fff] '  .$links[$i]);
            }
            if(!empty($message)){
                if($results[0] == "/pm"){
                    var_dump($message, $results[1]);
                    $this->pmLogin($message, $results[1]);

                }else{
                    $this->aseco->client->query('ChatSendServerMessage', $message);
                }
            }
        }
        return $chat;
    }
    function pmLogin($message, $login){
        $this->aseco->client->query('ChatSendServerMessageToLogin', $message, $login);
    }

    /**
     * @param $url
     * @return null|string|string[]
     */
    function getPageTitle( $url)
    {
        $link = http_build_url($url);
        $html = @file_get_contents($link);
        if ($html) {
            preg_match("/<title>(.+)<\/title>/i", $html, $matches);
            if (isset($matches[1])) {
                return remove_emojis($matches[1]);
            }
        }
    }

    /**
     * @param $link
     */
    function httpsLink($link)
    {
        $this->aseco->client->query(
            'ChatSendServerMessage',
            $this->aseco->formatColors(
                '$[$f00URL Bot$fff] https detected, click here to visit link $l' .
                str_replace("https", "http", $link)
            )
        );
    }


    /**
     * @param $youtubeId
     * @return null|string|string[]
     */
    function getYoutubeTitle($youtubeId)
    {
        $config = $this->aseco->xml_parser->parseXml('url.xml', true);
        $googleApiKey = $config["URL"]["YOUTUBE"][0]["GOOGLE_API_KEY"][0];
        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=$youtubeId&key=$googleApiKey&part=snippet";
        $apiRequest = json_decode(file_get_contents($apiUrl));
        if (isset($apiRequest->items[0]->snippet)) {
            return remove_emojis($apiRequest->items[0]->snippet->title);
        }
    }
}


