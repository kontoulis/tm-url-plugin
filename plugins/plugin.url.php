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

/**
 * @param $aseco
 * @param $chat
 */
function processUrl($aseco, $chat)
{
    if ($chat[1] === (string)$aseco->server->serverlogin || strpos($chat[2], '$l') !== 0) {
        return;
    }
    $url = parse_url(trim(str_replace('$l', '', $chat[2])));
    if (!isset($url["scheme"])) {
        $url["scheme"] = "http";
    }
    parse_str($url["query"], $parsedQuery);
    if (in_array(str_replace("www.", "", $url["host"]), ["youtube.com", "youtu.be"])) {
        $youtubeId = (isset($parsedQuery["v"]) ? $parsedQuery["v"] : $url["path"]);
        getYoutubeTitle($aseco, $youtubeId);
    } else {
        getPageTitle($aseco, $url);
    }
    if (strpos($url["scheme"], "https") === 0) {
        httpsLink($aseco, http_build_url($url));
    }
}

/**
 * @param $aseco
 * @param $url
 */
function getPageTitle($aseco, $url)
{
    $link = http_build_url($url);
    $html = @file_get_contents($link);
    if ($html) {
        preg_match("/<title>(.+)<\/title>/i", $html, $matches);
        if (isset($matches[1])) {
            $aseco->client->query(
                'ChatSendServerMessage',
                $aseco->formatColors('$[$f00URL Bot$fff] ' .remove_emojis($matches[1]))
            );
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
 */
function getYoutubeTitle($aseco, $youtubeId)
{
    $config = $aseco->xml_parser->parseXml('url.xml', true);
    $googleApiKey = $config["URL"]["YOUTUBE"][0]["GOOGLE_API_KEY"][0];
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=$youtubeId&key=$googleApiKey&part=snippet";
    $apiRequest = json_decode(file_get_contents($apiUrl));
    if (isset($apiRequest->items[0]->snippet)) {
        $aseco->client->query(
            'ChatSendServerMessage',
            $aseco->formatColors('$[$f00YouTube Bot$fff] ' . remove_emojis($apiRequest->items[0]->snippet->title))
        );
    }
}
