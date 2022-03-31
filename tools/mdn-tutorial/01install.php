<?php

require_once "webauto.php";

use Goutte\Client;

line_out("Installing Django on PythonAnywhere");

?>
<p>
<a href="../../assn/paw_install.md" target="_blank">
https://www.dj4e.com/assn/paw_install.md</a>
</a>
</p>
<?php

$url = getUrl('https://www.dj4e.com/assn/paw_install/index.htm');
if ( $url === false ) return;
$passed = 0;

$path = $url;
if ( strpos($url,'index.htm') !== false ) {
    $path = dirname($path);
}

$csspath = $path . '/static/admin/css/fonts.css';

error_log("MDNInstall".$url);
// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$client->setMaxRedirects(5);

$crawler = webauto_get_url($client, $url);
$response = $client->getResponse();
$status = $response->getStatus();
if ( $status == 404 ) {
    error_out("Could not load $url, status=$status");
    return;
}
if ( $crawler === false ) return;

$html = webauto_get_html($crawler);
if ( strpos($html, 'ALLOWED_HOSTS') !== false ) {
    error_out('It looks like you forgot to edit the ALLOWED_HOSTS setting');
?>
<p>
You will need to edit the file <b>mytestsite/settings.py</b> and change the following line:
<pre>
ALLOWED_HOSTS = ['*']
</pre>
</p>
<?php
    return;
}
webauto_search_for($html, 'The install worked successfully! Congratulations!');

if ( strpos($url,'dj4e.com') !== false ) {
    error_out("Not graded - sample solution");
    return;
}

// Make sure static is set up properly
$crawler = webauto_get_url($client, $csspath);
$response = $client->getResponse();
$status = $response->getStatus();
if ( $status != 200 ) {
    error_out("Could not load $csspath, make sure you are serving your static files status=$status");
    return;
} else {
    success_out("Loaded $csspath");
    $passed += 1;
}

if ( strpos($url,'mdntutorial.pythonanywhere.com') !== false ) {
    error_out("Not graded - sample solution");
    return;
}

if ( strpos($url,'dj4e.com') !== false ) {
    error_out("Not graded - sample solution");
    return;
}

// -------
line_out(' ');

$perfect = 2;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( $score < 1.0 ) autoToggle();

// Send grade
if ( $score > 0.0 ) webauto_test_passed($score, $url);

