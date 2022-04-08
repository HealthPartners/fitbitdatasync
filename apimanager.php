<?php
/**
 * This is API endpoint manager for the module to handle REST based requests, This should never expose any data.
 */
 
$apiStatus = FALSE;
$showInfo = FALSE;
if (isset($_GET['pid']) && isset($_GET['action']) && $_GET['action'] == "redirect" &&  isset($_GET['state']) && isset($_GET['code'] )) {
    $apiStatus = $module->getAccessToken($_GET['code'], $_GET['state']);
    if ($apiStatus == TRUE) {
        echo "Thank you - now we have connected with your fitbit account";
    } else {
        echo "oops!, something went wrong";
    }
} else if (isset($_GET['pid']) && isset($_GET['action']) && $_GET['action'] == "geninviteurl") { 
    $apiStatus = $module->generateInviteURL($_GET['code'], $_GET['state']);
    if ($apiStatus == TRUE) {
        echo "Great!, Inivte URL generated successfully";
    } else {
        echo "oops!, something went wrong";
    }
} else if (isset($_GET['pid']) && isset($_GET['action']) && $_GET['action'] == "getlastnightsleeplog") { 
    $apiStatus = $module->getLastNightSleepLog();
    if ($apiStatus == TRUE) {
        echo "Great!, getlastnightsleeplog job has been successfully completed";
    } else {
        echo "oops!, something went wrong";
    }
} else if (isset($_GET['pid']) && isset($_GET['action']) && $_GET['action'] == "getlastnightactivitylog") { 
    $apiStatus = $module->getLastNightActivityLog();
    if ($apiStatus == TRUE) {
        echo "Great!, getlastnightactivitylog job has been successfully completed";
    } else {
        echo "oops!, something went wrong";
    }
} else if (isset($_GET['pid']) && isset($_GET['action']) && $_GET['action'] == "getlastdayheartratelog") { 
    $apiStatus = $module->getLastDayHeartRateLog();
    if ($apiStatus == TRUE) {
        echo "Great!, getlastdayheartratelog job has been successfully completed";
    } else {
        echo "oops!, something went wrong";
    }
} else {
    echo "Invalid request to fitbitsync apimanager </br> </br>";
    $showInfo = TRUE;
}
?>
<?php if($showInfo) { ?>
<br/><br/>  <h1>Batch Job Support Documentation </h1>
<h1 style="text-align: left;"><span style="font-size: 11px;">This REDCap Project API Web Service Endpoints:&nbsp;</span></h1>
<p><span style="font-size: 11px;">1. To run the batch job generate the unique Fitbit sync invite URL for each participants&nbsp;:</span></p>
<p><span style="font-size: 11px;"><?php echo $module->getUrl("apimanager.php", $noAuth=true, $useApiEndpoint=true) ?>&amp;action=<span style="font-weight: bold;">geninviteurl</span><br />
</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">2. To run get last night sleep log which pulls all the participant&nbsp;sleeplog from Fitbit cloud server if authorized&nbsp;&nbsp;:</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">&nbsp;<?php echo $module->getUrl("apimanager.php", $noAuth=true, $useApiEndpoint=true) ?>&amp;action=<span style="font-weight: bold;">getlastnightsleeplog</span></span>&nbsp;</p>
</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">3. To run get last night activity log which pulls all the participant&nbsp;activitylog from Fitbit cloud server if authorized&nbsp;&nbsp;:</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">&nbsp;<?php echo $module->getUrl("apimanager.php", $noAuth=true, $useApiEndpoint=true) ?>&amp;action=<span style="font-weight: bold;">getlastnightactivitylog</span></span>&nbsp;</p>
</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">4. To run get last day heart rate log which pulls all the participant&nbsp;heart rate log from Fitbit cloud server if authorized&nbsp;&nbsp;:</span></p>
<p style="text-align: left;"><span style="font-size: 11px;">&nbsp;<?php echo $module->getUrl("apimanager.php", $noAuth=true, $useApiEndpoint=true) ?>&amp;action=<span style="font-weight: bold;">getlastdayheartratelog</span></span>&nbsp;</p>

<?php } ?>