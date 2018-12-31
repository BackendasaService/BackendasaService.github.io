<?php
$page = '{topics}/{contents}';

if (file_exists('layout.html')) {
    $page = file_get_contents('layout.html');
}

$topics = array();
foreach (glob("topics/*.html") as $file) {
    if ($file != '.'
        && $file != '..'
        && !is_dir($file)
        && !preg_match("/\.min/", $file)
    ) {
        $topic = substr($file, 7, -5);
        $topics[] = sprintf(
            '<li class="nav-group-task"><a href="%s.html">– /%s</a></li>',
            $topic,
            $topic
        );
    }
}
$page = preg_replace("/{topics}/", implode('', $topics), $page);

// Create page.
foreach (glob("topics/*.html") as $file) {
    if ($file != '.' && $file != '..' && !is_dir($file)) {
        $newPage = $page;

        $topic = substr($file, 7, -5);

        $newPage = preg_replace(
            '/{topicName}/',
            $topic,
            $newPage
        );

        $newPage = preg_replace(
            '/{topicText}/',
            file_get_contents($file),
            $newPage
        );

        file_put_contents(
            $topic . ".html",
            $newPage
        );
    }
}

// file_put_contents(
//     'index.html',
//     $page
// );
