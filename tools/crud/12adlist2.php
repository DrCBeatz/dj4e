<?php

require_once "../crud/webauto.php";
require_once "../crud/names.php";

$code = $USER->id+$CONTEXT->id;

$check = webauto_get_check_full();

$meta = '<meta name="dj4e" content="'.$check.'">';

$user1account = 'dj4e_user1';
$user1pw = "Meow_" . substr(getMD5(),1,6). '_41';
$user2account = 'dj4e_user2';
$user2pw = "Meow_42_" . substr(getMD5(),1,6);

$now = date('H:i:s');

line_out("Building Classified Ad Site #2");

?>
Specification:
<a href="../../assn/dj4e_ads2.md" class="btn btn-info" target="_blank">
https://www.dj4e.com/assn/dj4e_ads2.md</a>
</a>
<p>
You should already have two users and a <b>meta</b> tag.
<pre>
<?= htmlentities($user1account) ?> / <?= htmlentities($user1pw) ?>  
<?= htmlentities($user2account) ?> / <?= htmlentities($user2pw) ?> 
<?= htmlentities($meta) ?>
</pre>
Your application should either be at the '/' path or the '/ads' path.
Note that your application should not be at the '/m2' path and should not
have a "Versions" drop-down.  That is <b>just how the sample implementation</b> is written
to support more than one variant of the code at the same time.
</p>
<?php

$url = getUrl('https://chucklist.dj4e.com/m2');
if ( $url === false ) return;

webauto_check_test();
$passed = 0;

webauto_setup();

// Load the Favicon
// https://en.wikipedia.org/wiki/ICO_(file_format)

$content = get_favicon($client, $base_url_path);
if ( $content === false ) {
    line_out("Favicon is coming in the next assignment...");
}

// Start the actual test
$crawler = webauto_get_url($client, $url);
if ( $crawler === false ) return;
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

require("meta_check.php");

$login_url = webauto_get_url_from_href($crawler,'Login');


$crawler = webauto_get_url($client, $login_url, "Logging in as $user1account");
$html = webauto_get_html($crawler);

// Use the log_in form
$form = webauto_get_form_with_button($crawler,'Login', 'Login Locally');
webauto_change_form($form, 'username', $user1account);
webauto_change_form($form, 'password', $user1pw);

$crawler = webauto_submit_form($client, $form);
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

if ( webauto_dont_want($html, "Your username and password didn't match. Please try again.") ) return;

// Cleanup old ads
$saved = $passed;
preg_match_all("'\"([a-z0-9/]*/[0-9]+/delete[^\"]*)\"'",$html,$matches);
// echo("\n<pre>\n");var_dump($matches);echo("\n</pre>\n");

if ( is_array($matches) && isset($matches[1]) && is_array($matches[1]) ) {
    foreach($matches[1] as $match ) {
        $crawler = webauto_get_url($client, $match, "Loading delete page for old record");
        $html = webauto_get_html($crawler);
        $form = webauto_get_form_with_button($crawler,'Yes, delete.');
		$crawler = webauto_submit_form($client, $form);
        $html = webauto_get_html($crawler);
    } 
}
$passed = $saved;

$create_ad_url = webauto_get_url_from_href($crawler,"Create Ad");
$crawler = webauto_get_url($client, $create_ad_url, "Retrieving create ad page...");
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

if ( ! webauto_search_for_not($html, "owner") ) {
    error_out('The owner field is not supposed to appear in the create form.');
    return;
}

if ( ! webauto_search_for($html, "price") ) {
    error_out('The price field is missing on the create form - check the field_list in views.py');
    return;
}

if ( ! webauto_search_for_not($html, "comment") ) {
    error_out('The comments field is not supposed to appear in the create form.');
    return;
}

// Sanity check the new ad page
if ( strpos($html, 'type="file"') > 1 ) {
    markTestPassed("Found upload button on Create Ad form");
} else {
    error_out("Create Ad form cannot upload a file");
}

if ( strpos($html, 'window.File') > 1 ) {
    markTestPassed("Found JavaScript to check the size of the uploaded file on Create Ad form");
} else {
    error_out("Create Ad page appears to be missing JavaScript to check the size of the uploaded file");
}

if ( strpos($html, 'multipart/form-data') > 1 ) {
    markTestPassed('Found enctype="multipart/form-data" on Create Ad form');
} else {
    error_out('Create Ad form requires enctype="multipart/form-data"');
}

// Add a record
$title = 'HHGTTG_41 '.$now;
$form = webauto_get_form_with_button($crawler,'Submit');
webauto_change_form($form, 'title', $title);
webauto_change_form($form, 'price', '0.41');
webauto_change_form($form, 'text', 'Low cost Vogon poetry.');
$picturepath = dirname(__FILE__) . "/Sakaiger.png";
webauto_change_form($form, 'picture', $picturepath);

line_out("Submitting the create form, expecting the list of items...");
$crawler = webauto_submit_form($client, $form);
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

if ( ! webauto_search_for($html, $title) ) {
    error_out('Tried to create a record and cannot find the record in the list view');
    return;
}

// Check the detail page
$detail_url = webauto_get_url_from_href($crawler,$title, "(Could not link to the detail page on the list view)");
$crawler_detail = webauto_get_url($client, $detail_url, "Loading detail page");
$html_detail = webauto_get_html($crawler_detail);
if ( ! webauto_search_for($html_detail, $title) ) {
    error_out("Did not find '$title' on detail page");
    return;
}
if ( ! webauto_search_for($html_detail, 'Price', true) ) {
    error_out("Did not find price on detail page");
    return;
}
if ( ! webauto_search_for_not($html_detail, "owner") ) {
    error_out('The owner field is not supposed to appear in the detail form.');
    return;
}

$crawler = webauto_get_url($client, $url, "Going from the detail page to the ad list view to update the ad that User 1 just created");
if ( $crawler === false ) return;
$html = webauto_get_html($crawler);

// Look for the edit entry
preg_match_all("'\"([a-z0-9/]*/[0-9]+/update[^\"]*)\"'",$html,$matches);
if ( is_array($matches) && isset($matches[1]) && is_array($matches[1]) ) {
    if ( count($matches[1]) < 1 ) {
        error_out("Could not find an Edit link with a url like /ad/nnn/update");
        return;
    } else if ( count($matches[1]) > 1 ) {
        error_out("Expecting only one Edit link with a url like /ad/nnn/update");
        return;
    }
    $match = $matches[1][0];
    $crawler = webauto_get_url($client, $match, "Loading edit page for old record");
    $html = webauto_get_html($crawler);
    $form = webauto_get_form_with_button($crawler,'Submit');
    webauto_change_form($form, 'title', $title."_updated");
    $picturepath = dirname(__FILE__) . "/Sakaiger.png";
    webauto_change_form($form, 'picture', $picturepath);
	$crawler = webauto_submit_form($client, $form);
    $html = webauto_get_html($crawler);
    webauto_search_for_menu($html);
    webauto_search_for($html,$title."_updated");
} else {
    error_out("Could not find Edit link with a url of the form /ad/nnn/update");
    return;
}

// Lets add a comment form
line_out('Looking for the detail page so we can add a comment');
$detail_url = webauto_get_url_from_href($crawler,$title."_updated");
$crawler = webauto_get_url($client, $detail_url, "Loading detail page...");
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

// Use the comment form
line_out('Looking for comment form and submit button.');
$form = webauto_get_form_with_button($crawler,'Submit');
webauto_change_form($form, 'comment', $title."_comment");

line_out('Submitting the comment form');
$crawler = webauto_submit_form($client, $form);
$html = webauto_get_html($crawler);

if ( ! webauto_search_for($html, $title."_comment") ) {
    error_out('Added a comment but could not find it on the next screen');
    return;
}


line_out('Deleteing that comment..');
// comment/3/delete

preg_match_all("'\"([a-z0-9/]*comment*/[0-9]+/delete[^\"]*)\"'",$html,$matches);
// echo("\n<pre>\n");var_dump($matches);echo("\n</pre>\n");

if ( is_array($matches) && isset($matches[1]) && is_array($matches[1]) ) {
    foreach($matches[1] as $match ) {
        $crawler = webauto_get_url($client, $match, "Loading delete page for comment");
        $html = webauto_get_html($crawler);
        $form = webauto_get_form_with_button($crawler,'Yes, delete.');
        $crawler = webauto_submit_form($client, $form);
        $html = webauto_get_html($crawler);
        if ( ! webauto_search_for_not($html, $title."_comment") ) {
            error('It appears that the comment was not deleted.');
            return;
        }
        break;
    } 
} else {
    error_out('Could not find link to delete comment comment/nnn/delete');
    return;
}


line_out('');
line_out('Test completed... Logging out.');
$logout_url = webauto_get_url_from_href($crawler,'Logout');
$crawler = webauto_get_url($client, $logout_url, "Logging out...");
$html = webauto_get_html($crawler);
webauto_search_for_menu($html);

// -------
line_out(' ');
echo("<!-- Raw score $passed -->\n");
// echo("  -- Raw score $passed \n");
$perfect = 26;
if ( $passed < 0 ) $passed = 0;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $meta_good ) {
    error_out("Not graded - missing meta tag");
    return;
}
if ( webauto_testrun($url) ) {
    error_out("Not graded - sample solution");
    return;
}

// Send grade
if ( $score > 0.0 ) webauto_test_passed($score, $url);

