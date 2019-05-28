<?php
/*
=======================================================================
Description: Shows website or youtube video title in chat
Authors: amgreborn, asmodai
Version: v1.0
Dependencies: plugin.localdatabase.php
=======================================================================
*/

require_once "includes/string_helper.php";

Aseco::registerEvent("onChat", "processUrl");
Aseco::registerEvent("onStartup", function($aseco){
    $aseco->pluginsChatReserve[] = '$l';
});

/**
 * @param $aseco
 * @param $chat
 */
function processUrl($aseco, $chat)
{
    if ($chat[1] === (string)$aseco->server->serverlogin || strpos($chat[2], '$l') === false) {
        return;
    }
    $chat[2] = preg_replace('/(https\:\/\/)/', 'http://', $chat[2]);
    $youtubes = [];
    $links = [];
    $results = split_chat_links_text($chat[2]);
    foreach($results as $i => &$result) {
        if(strpos($result, '$l') === 0) {
            $url = parse_url(trim(str_replace('$l', '', $result)));
            if (!isset($url["scheme"])) {
                $url["scheme"] = "http";
            }
            parse_str($url["query"], $parsedQuery);
            if (in_array(str_replace("www.", "", $url["host"]), ["youtube.com", "youtu.be"])) {
                $youtubeId = (isset($parsedQuery["v"]) ? $parsedQuery["v"] : $url["path"]);
                $youtubes[$i] = getYoutubeTitle($aseco, $youtubeId);
                $result = '$l$f00' . http_build_url($url);

            } else {
                $links[$i] = getPageTitle($url);
                $result = '$l$39f' . http_build_url($url);
            }
        }
    }

    foreach($results as $i => $result) {
        $aseco->client->query('ChatSendServerMessage', '$z$g$s[' . $aseco->getPlayerNick($chat[1]) . '$z$g$s] ' . $result);
        if (isset($youtubes[$i]) && !empty($youtubes[$i])) {
            $aseco->client->query(
                'ChatSendServerMessage',
                $aseco->formatColors('$[$f00YouTube Bot$fff] ' . $youtubes[$i])
            );
        }elseif(isset($links[$i]) && !empty($links[$i])){
            $aseco->client->query(
                'ChatSendServerMessage',
                $aseco->formatColors('$[$f00URL Bot$fff] '  .$links[$i])
            );
        }
    }
}

/**
 * @param $aseco
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
 * @param $aseco
 * @param $link
 */
function httpsLink($aseco, $link)
{
    $aseco->client->query(
        'ChatSendServerMessage',
        $aseco->formatColors(
            '$[$f00URL Bot$fff] https detected, click here to visit link $l' .
            str_replace("https", "http", $link)
        )
    );
}


/**
 * @param $aseco
 * @param $youtubeId
 * @return null|string|string[]
 */
function getYoutubeTitle($aseco, $youtubeId)
{
    $config = $aseco->xml_parser->parseXml('url.xml', true);
    $googleApiKey = $config["URL"]["YOUTUBE"][0]["GOOGLE_API_KEY"][0];
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=$youtubeId&key=$googleApiKey&part=snippet";
    $apiRequest = json_decode(file_get_contents($apiUrl));
    if (isset($apiRequest->items[0]->snippet)) {
       return remove_emojis($apiRequest->items[0]->snippet->title);
    }
}

