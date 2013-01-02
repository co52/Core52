<?php

// Example Usage - uncomment to test
// ----------------------------

require 'apachelogregex.class.php';

// Get some log data
$log = array (
    '192.168.0.1 - - [29/Aug/2006:00:36:59 +0100] "GET /scum/ HTTP/1.1" 200 33658 "http://kitty0.org/scum/search.php?search_author=deepnausea1982&sid=58699c7eaf59d276db952b3b48e5bb0b" "FAST MetaWeb Crawler (helpdesk at fastsearch dot com)"',
    '192.168.0.2 - - [29/Aug/2006:00:37:38 +0100] "GET / HTTP/1.1" 301 5 "http://www.google.co.uk/search?hl=en&q=hamish+++morgan&btnG=Google+Search&meta=" "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.5) Gecko/20060731 Ubuntu/dapper-security Firefox/1.5.0.5"',
    '192.168.0.3 - - [29/Aug/2006:00:37:41 +0100] "GET /blog/ HTTP/1.1" 200 31695 "http://www.google.co.uk/search?hl=en&q=hamish+++morgan&btnG=Google+Search&meta=" "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.5) Gecko/20060731 Ubuntu/dapper-security Firefox/1.5.0.5"',
    '192.168.0.4 - - [29/Aug/2006:00:38:27 +0100] "GET /blog/referer_plugin/change-log/ HTTP/1.1" 200 21227 "http://kitty0.org/blog/" "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.5) Gecko/20060731 Ubuntu/dapper-security Firefox/1.5.0.5"'
);

// The log format of the above data
$format = '%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"';

// Create an instance of our object
$alr = new ApacheLogRegex($format);

foreach($log as $i => $line) {
    $data = $alr->parse($line);
    if($data === null) {
        echo '<p><strong>Error:</strong> Parse failed for Test line #' . $i . ': <code>' . $line . '</code></p>';
    } else {
        $data['Time'] = date("F j, Y, g:i a", $alr->logtime_to_timestamp($data['Time']));
        echo '&lt;pre>Test Line #' . $i . ' = ' . print_r($data, true) . '&lt;/pre></br>';
    }
}