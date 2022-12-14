<?php

// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.
include 'common.inc';
set_time_limit(30000);

if (isset($test['test'])) {
    if ($test['test']['batch']) {
        $count = 0;
        $tests = null;
        if (gz_is_file("$testPath/tests.json")) {
            $legacyData = json_decode(gz_file_get_contents("$testPath/tests.json"), true);
            $tests = array();
            $tests['variations'] = array();
            $tests['urls'] = array();
            foreach ($legacyData as &$legacyTest) {
                $tests['urls'][] = array('u' => $legacyTest['url'], 'id' => $legacyTest['id']);
            }
        } elseif (gz_is_file("$testPath/bulk.json")) {
            $tests = json_decode(gz_file_get_contents("$testPath/bulk.json"), true);
        }
        if (isset($tests)) {
            foreach ($tests['urls'] as &$testData) {
                if (CancelTest($testData['id'])) {
                    $count++;
                }
                foreach ($testData['v'] as $variationIndex => $variationId) {
                    if (CancelTest($variationId)) {
                        $count++;
                    }
                }
            }
        }
        echo "<h3 align=\"center\">$count Test(s) cancelled!</h3>";
    } else {
        if (CancelTest($id)) {
            echo '<h3 align="center">Test cancelled!</h3>';
        } else {
            if (isset($_SERVER["HTTP_REFERER"])) {
                header("Location: " . $_SERVER["HTTP_REFERER"]);
            }
        }
    }
    echo '<form><input type="button" value="Back" onClick="history.go(-1);return true;"> </form>';
}

/**
 * Cancel and individual test
 *
 * @param mixed $id
 * @return bool
 */
function CancelTest($id)
{
    $cancelled = false;
    $lock = LockTest($id);
    if ($lock) {
        $testInfo = GetTestInfo($id);
        if ($testInfo && !array_key_exists('started', $testInfo)) {
            $testInfo['cancelled'] = time();
            SaveTestInfo($id, $testInfo);

            // delete the actual test file.
            if (array_key_exists('workdir', $testInfo)) {
                $ext = 'url';
                if ($testInfo['priority']) {
                    $ext = "p{$testInfo['priority']}";
                }
                $file_to_search = $testInfo['workdir'] . "/*.$id.$ext";
                $found_files = glob($file_to_search);
                if (1 === count($found_files)) {
                    $cancelled = @unlink($found_files[0]);
                }
            }
            $testInfo['id'] = $id;
            SendCallback($testInfo);
        }
        UnlockTest($lock);
    }
    return $cancelled;
}
