<?php

// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.
//$DISABLE_RESTORE = true;
require_once('common.inc');
require_once('testStatus.inc');
set_time_limit(60 * 5);

$ret = array();
$ret['statusCode'] = 400;
$ret['statusText'] = 'Test not found';
if (isset($_REQUEST['r']) && strlen($_REQUEST['r'])) {
    $ret['requestId'] = $req_r;
}
//$position = false;
//if( $_REQUEST['pos'] )
    $position = true;

// see if we are dealing with multiple tests or a single test
if (isset($_REQUEST['tests']) && strlen($_REQUEST['tests'])) {
    $tests = explode(',', $_REQUEST['tests']);
    $ret['data'] = array('testsExpected' => 0, 'testsCompleted' => 0, 'tests' => array());
    foreach ($tests as $id) {
        if (ValidateTestId($id)) {
            $status = GetTestStatus($id, $position);
            $status['id'] = $id;
            $ret['tests'][] = $status;
            if ($status['statusCode'] < $ret['statusCode']) {
                $ret['statusCode'] = $status['statusCode'];
                $ret['statusText'] = $status['statusText'];
                if (array_key_exists('elapsed', $status)) {
                    $ret['data']['elapsed'] = $status['elapsed'];
                } elseif (array_key_exists('elapsed', $ret['data'])) {
                    unset($ret['data']['elapsed']);
                }
                if (array_key_exists('behindCount', $status)) {
                    $ret['data']['behindCount'] = $status['behindCount'];
                } elseif (array_key_exists('behindCount', $ret['data'])) {
                    unset($ret['data']['behindCount']);
                }
            } elseif ($status['statusCode'] < $ret['statusCode']) {
                if (
                    $status['statusCode'] == 100 &&
                    array_key_exists('elapsed', $status)
                ) {
                    if (
                        !array_key_exists('elapsed', $ret['data']) ||
                        $status['elapsed'] > $ret['data']['elapsed']
                    ) {
                        $ret['statusText'] = $status['statusText'];
                        $ret['data']['elapsed'] = $status['elapsed'];
                    }
                } elseif (
                    $status['statusCode'] == 101 &&
                          array_key_exists('behindCount', $status)
                ) {
                    if (
                        !array_key_exists('behindCount', $ret['data']) ||
                        $status['behindCount'] < $ret['data']['behindCount']
                    ) {
                        $ret['statusText'] = $status['statusText'];
                        $ret['data']['behindCount'] = $status['behindCount'];
                    }
                }
            }
            if (array_key_exists('testsExpected', $status)) {
                $ret['data']['testsExpected'] += $status['testsExpected'];
            }
            if (array_key_exists('testsCompleted', $status)) {
                $ret['data']['testsCompleted'] += $status['testsCompleted'];
            }
        }
    }
    if (
        $ret['statusCode'] >= 100 && $ret['statusCode'] < 200 &&
        $ret['data']['testsCompleted'] > 0 && $ret['data']['testsExpected'] > 1
    ) {
        $ret['statusCode'] = 100;
        $ret['statusText'] = "Completed {$ret['data']['testsCompleted']} of {$ret['data']['testsExpected']} tests";
    }
} elseif (isset($id) && strlen($id)) {
    $ret['data'] = GetTestStatus($id, $position);
    $ret['statusCode'] = $ret['data']['statusCode'];
    $ret['statusText'] = $ret['data']['statusText'];
}

// spit out the response in the correct format
if (isset($_REQUEST['f']) && $_REQUEST['f'] == 'xml') {
    header('Content-type: text/xml');
    print_r(array2xml($ret, false));
} else {
    json_response($ret);
}
