<?php

/**
 * tweet.php - tweet using twitter's mobile interface.
 *
 * Copyright (C) 2013 Kuno Woudt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 **/

$cookie_jar = "/tmp/tweet.php.cookie.txt";

function start_curl ($url)
{
    global $cookie_jar;

    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_jar);
    curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_jar);
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt ($ch, CURLOPT_MAXREDIRS, 8);
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (tweet.php)");

    return $ch;
}

function end_curl ($ch)
{
    $page = curl_exec ($ch);
    curl_close ($ch);

    return $page;
}

function html_xpath ($html, $query)
{
    $dom = new DOMDocument ();
    $dom->recover = true;
    $dom->strictErrorChecking = false;
    @$dom->loadHTML ($html);
    $xpath = new DOMXpath ($dom);

    $elements = $xpath->query ($query);
    foreach ($elements as $node) {
        return $node->getAttribute('value');
    }

    return NULL;
}

function get_token ($html)
{
    if (preg_match ("/.*authenticity_token.*value=\"(\w*)\"/", $html, $matches))
    {
        return $matches[1];
    }

    return NULL;
}

function tweet ($username, $password, $tweet)
{
    global $cookie_jar;

    /* Get login page _____________________ */
    $ch = start_curl ("https://mobile.twitter.com/session/new");
    $page = end_curl ($ch);
    $token = get_token ($page);
    if (empty ($token))
        return FALSE;

    echo "Authenticating $username with token: $token ...\n";
    $ch = start_curl ("https://mobile.twitter.com/session");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "authenticity_token=$token&username=$username&password=$password");
    $page = end_curl ($ch);

    /* Get compose tweet page _____________________ */
    $ch = start_curl ("https://mobile.twitter.com/compose/tweet");
    $page = end_curl ($ch);
    $token = get_token ($page);
    if (empty ($token))
        return FALSE;

    echo "Tweeting with token: $token ...\n";
    $ch = start_curl ("https://mobile.twitter.com/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "authenticity_token=$token&tweet[text]=$tweet&tweet[display_coordinates]=false");
    $page = end_curl ($ch);

    unlink ($cookie_jar);

    return TRUE;
}

