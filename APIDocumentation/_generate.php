<?php
if (file_exists('index_new.html')) {
    $page = file_get_contents('index_new.html');
}

$topics = array();

foreach (glob("topics/*.html") as $file) {
    if ($file != '.'
        && $file != '..'
        && !is_dir($file)
        && !preg_match("/\.min/", $file)
    ) {
        $topics[] = parseFile(
            file_get_contents($file)
        );
    }
}

// To easy, and fast JSON Highlighter
function jsonHighlight($input)
{
    $input = implode("{" . PHP_EOL, explode("{", $input));
    $input = implode("," . PHP_EOL, explode(",", $input));
    $input = implode(PHP_EOL . "}", explode("}", $input));

    $newReturn = "";
    $ident = 0;
    // Better way.
    // Walk trough input
    foreach (explode(PHP_EOL, $input) as $line) {
        if (preg_match("/\}/", $line)) {
            $ident--;
        }
        for ($i = 0; $i < $ident; $i++) {
            $newReturn .= "    ";
        }

        // preg_replace ($line)
        // "xxxx"   :       "xxx"   ,
        // Blue     black   green   black
        $parseline = explode(":", $line);
        if (sizeof($parseline) == 2) {
            $newReturn .= '<font color=\'#02bcf4\'>';
            $newReturn .= $parseline[0];
            $newReturn .= '</font><font color=\'white\'>:</font>';
            $parseline2 = explode(",", $parseline[1]);
            $newReturn .= '<font color=\'green\'>';
            $newReturn .= $parseline2[0];
            $newReturn .= '</font><font color=\'white\'>,</font>';
        } else {
            $newReturn .= '</font><font color=\'white\'>';
            $newReturn .= $line;
            $newReturn .= '</font>';
        }
        $newReturn .= PHP_EOL;

        if (preg_match("/\{/", $line)) {
            $ident++;
        }
    }
    return $newReturn;
}

function parseFile($file)
{
    // Split lines
    // 00 URL       = user.login
    // 01 DESC      = Login a user
    // 02 DESC_LONG = With this call you can login a user.
    // 03 REQUEST   = {"APIKey":"YOUR_API_KEY","password":""}
    // 04 RESP_01   = 200={..}
    // 05 RESP_02   = 300={...}
    // 06 RESP_03   = 400={....}
    // 07 RESP_04   = 500={.....}
    // .. RESP_..   = xxx=.......
    //                ^ Title
    //                    Value ^

    $data = explode("\n", $file);
    if (sizeof($data) < 5) {
        return $file;
    }

    $responses = array();

    for ($i = 3; $i < sizeof($data); $i++) {
        $parsed_data = explode("=", $data[$i]);
        if (!empty($parsed_data[1]) && !empty($parsed_data[0])) {
            $responses[] = array(
                "title" => $parsed_data[0],
                "response" => jsonHighlight($parsed_data[1]),
            );
        }
    }

    return array(
        "url" => $data[0],
        "safe_url" => preg_replace('/\./', '_', $data[0]),
        "short_description" => $data[1],
        "long_description" => $data[2],
        "request" => jsonHighlight($data[3]),
        "responses" => $responses,
    );
}

// Create (basic) menu
preg_match_all("/\[MENU_REPEAT\](.*)\[\/MENU_REPEAT\]/", $page, $extracted_menu);

// Repeat items.
preg_match_all("/\[ITEM_REPEAT\](.*?)\[\/ITEM_REPEAT\]/s", $page, $extracted_item);

$menu = array();
$items = array();

foreach ($topics as $topic) {
    if (isset($topic['url'])) {
        $labels = array();
        $contents = array();

        $unparsedMenu = $extracted_menu[1][0];
        $unparsedMenu = preg_replace("/\[MENU_ITEM\]/", $topic['url'], $unparsedMenu);
        $unparsedMenu = preg_replace("/\[MENU_URL\]/", $topic['safe_url'], $unparsedMenu);
        $menu[] = $unparsedMenu;

        $unparsedItem = $extracted_item[1][0];
        $unparsedItem = preg_replace("/\[REQUEST_URL\]/", $topic['url'], $unparsedItem);
        $unparsedItem = preg_replace("/\[SHORTCUT\]/", $topic['safeurl'], $unparsedItem);
        $unparsedItem = preg_replace("/\[SHORT_DESCRIPTION\]/", $topic['short_description'], $unparsedItem);
        $unparsedItem = preg_replace("/\[LONG_DESCRIPTION\]/", $topic['long_description'], $unparsedItem);
        $unparsedItem = preg_replace("/\[BODY_TEXT\]/", $topic['request'], $unparsedItem);

        // Walk trough Return types.
        preg_match_all("/\[RESPONSE_SHOWHIDE\](\<span class=\"tab-button\"\>\[RESPONSE_RESPONSE\]\<\/span\>)\[\/RESPONSE_SHOWHIDE\]/", $unparsedItem, $repeat_return_lbl);
        preg_match_all("/\[RESPONSE_SHOWHIDE\](.*?)\[\/RESPONSE_SHOWHIDE\]/s", $unparsedItem, $repeat_return_contents);

        for ($i = 0; $i < sizeof($topic['responses']); $i++) {
            $labels[] = preg_replace("/\[RESPONSE_RESPONSE\]/", $topic['responses'][$i]['title'], $repeat_return_lbl[1][0]);
            $contents[] = preg_replace("/\[RESPONSE_RETURN\]/", $topic['responses'][$i]['title'], $repeat_return_contents[1][1]);
        }

        $unparsedItem = preg_replace("/\[RESPONSE_SHOWHIDE\](\<span class=\"tab-button\"\>\[RESPONSE_RESPONSE\]\<\/span\>)\[\/RESPONSE_SHOWHIDE\]/", implode(PHP_EOL, $labels), $unparsedItem);
        $unparsedItem = preg_replace("/\[RESPONSE_SHOWHIDE\](.*?)\[\/RESPONSE_SHOWHIDE\]/s", implode(PHP_EOL, $contents), $unparsedItem);

        $items[] = $unparsedItem;
    }
}

$page = preg_replace("/\[MENU_REPEAT\].*\[\/MENU_REPEAT\]/", implode(PHP_EOL, $menu), $page);
$page = preg_replace("/\[ITEM_REPEAT\](.*?)\[\/ITEM_REPEAT\]/s", implode(PHP_EOL, $items), $page);
$page = preg_replace("/\[CURRENT_DATE\]/", date('D d M Y'), $page);

echo $page;
file_put_contents('index.php', $page);
