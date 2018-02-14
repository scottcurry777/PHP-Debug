<?php

    function formatArgs($args, $type) {
        foreach ($args as $k => $v) {
            if (is_array($v)) {
                $args[$k]   = formatArgs($v, $type);
            } elseif (is_bool($v)) {
                ob_start();
                var_dump($v);
                $args[$k]   = preg_replace("/[\n\r]/","",ob_get_clean());
                if ($type == 'display') {
                        $args[$k]	= '<span style="color: #ff00ff;">'.$args[$k].'</span>';
                }
            } elseif (is_null($v)) {
                ob_start();
                var_dump($v);
                $args[$k]   = preg_replace("/[\n\r]/","",ob_get_clean());
                if ($type == 'display') {
                    $args[$k]	= '<span style="color: #ff00ff;">empty(NULL)</span>';
                } else {
                    $args[$k]	= 'empty(NULL)';
                }
            } elseif ($v === '') {
                ob_start();
                var_dump($v);
                $args[$k]   = preg_replace("/[\n\r]/","",ob_get_clean());
                if ($type == 'display') {
                    $args[$k]	= '<span style="color: #ff00ff;">empty('.$args[$k].')</span>';
                } else {
                    $args[$k]	= 'empty('.$args[$k].')';
                }
            }
        }
        return $args;
    }

    function parseP($debug, $args, $type) {
        $errors		= '';
        $pFunction	= $debug['function'];
        $explodeFile    = explode('$\\', $debug['file']);
        $pFileName	= end($explodeFile);
        $pFile		= file($pFileName);
        $pLine		= '';

        // Get arguments passed to d() / c()
        $args		= formatArgs($args, $type);

        // Get all lines of code calling d() / c()
        $endingLineNumber 	= $debug['line'];
        $reverseLineNumber	= count($pFile) - $endingLineNumber;
        $getNextLine		= false;
        $pLineComplete		= false;

        foreach (array_reverse($pFile) as $lineNumber => $lineCode) {
            if ($lineNumber >= $reverseLineNumber) {
                if (strpos(strtoupper(preg_replace('/\s+/', '', $lineCode.$pLine)), strtoupper($pFunction).'(') === 0) {
                    $lineCodeStart	= 0;
                } elseif (strpos(strtoupper($lineCode.$pLine), ' '.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), ' '.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '	'.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '	'.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '('.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '('.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '.'.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '.'.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '='.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '='.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '{'.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '{'.strtoupper($pFunction).'(');
                } elseif (strpos(strtoupper($lineCode.$pLine), '}'.strtoupper($pFunction).'(') > -1) {
                    $lineCodeStart	= 1 + strpos(strtoupper($lineCode), '}'.strtoupper($pFunction).'(');
                } else {
                    $lineCodeStart	= -1;
                }
                if (
                    (!$getNextLine)
                    and
                    (
                        (
                            ($lineCodeStart > -1)
                            and ($lineCodeStart > strpos(strtoupper($lineCode.$pLine), ';'))
                        )
                        or
                        (
                            ($lineCodeStart == -1)
                            and (strpos(strtoupper($lineCode), ';') > -1)
                        )
                    )
                ) {
                    $getNextLine	= true;
                    if (($lineCodeStart > -1) and ($lineCodeStart > strpos(strtoupper($lineCode.$pLine), ';'))) {
                        $pLineTest		= substr($lineCode.$pLine, $lineCodeStart);
                        $parenthesisCount	= 0;
                        $pLineTestParts		= str_split($pLineTest);
                        foreach ($pLineTestParts as $pLineTestKey => $pLineTestValue) {
                            if ($pLineTestValue == '(') {
                                $parenthesisCount++;
                            }
                            if ($parenthesisCount > 0) {
                                if ($pLineTestValue == ')') {
                                    $parenthesisCount--;
                                }
                                if ($parenthesisCount == 0) {
                                    $startingLineNumber = ((isset($startingLineNumber)) ? $startingLineNumber : (count($pFile) - $lineNumber));
                                    break;
                                }
                            }
                        }
                    }
                } elseif (
                    (
                        (!$getNextLine)
                        and ($lineCodeStart > -1)
                    )
                    or
                    (
                        ($getNextLine)
                        and (strpos(strtoupper($lineCode), ';') > -1)
                    )
                ) {
                    if ($getNextLine) {
                        $nextLineCode	= substr(strrchr($lineCode.'a', ';'), 1, (strlen(strrchr($lineCode.'a', ';')) - 2));
                        $pLineTest	= $nextLineCode.$pLine;
                    } else {
                        $pLineTest	= substr($lineCode.$pLine, $lineCodeStart);
                    }
                    $pLineCompleteKeys	= array();
                    $parenthesisCount	= 0;
                    $pLineTestParts	= str_split($pLineTest);
                    foreach ($pLineTestParts as $pLineTestKey => $pLineTestValue) {
                        if ($pLineTestValue == '(') {
                            $parenthesisCount++;
                        }
                        if ($parenthesisCount > 0) {
                            if ($pLineTestValue == ')') {
                                $parenthesisCount--;
                            }
                            if ($parenthesisCount == 0) {
                                $startingLineNumber     = ((isset($startingLineNumber)) ? $startingLineNumber : (count($pFile) - $lineNumber));
                                $pLineComplete		= true;
                                $pLineCompleteKeys[]	= $pLineTestKey + 1;
                            }
                        }
                    }
                } elseif ($getNextLine) {
                    $pLineTest		= substr($lineCode.$pLine, $lineCodeStart);
                    $parenthesisCount	= 0;
                    $pLineTestParts	= str_split($pLineTest);
                    foreach ($pLineTestParts as $pLineTestKey => $pLineTestValue) {
                        if ($pLineTestValue == '(') {
                            $parenthesisCount++;
                        }
                        if ($parenthesisCount > 0) {
                            if ($pLineTestValue == ')') {
                                $parenthesisCount--;
                            }
                            if ($parenthesisCount == 0) {
                                $startingLineNumber = ((isset($startingLineNumber)) ? $startingLineNumber : (count($pFile) - $lineNumber));
                                $errorStartingLineNumber	= ((isset($errorStartingLineNumber)) ? $errorStartingLineNumber : (count($pFile) - $lineNumber));
                                break;
                            }
                        } elseif ($pLineTestValue == ')') {
                            $parenthesisCount--;
                        }
                    }
                }
                if ($pLineComplete) {
                        $lineCodeParts	= array();
                        foreach ($pLineCompleteKeys as $pK => $pLineCompleteKey) {
                            if ($getNextLine) {
                                $lineCodeParts[]    = substr($nextLineCode, (($pK == 0) ? 0 : $pLineCompleteKeys[($pK - 1)]), $pLineCompleteKey);
                            } else {
                                $lineCodeParts[]    = substr(substr($lineCode, $lineCodeStart), (($pK == 0) ? 0 : $pLineCompleteKeys[($pK - 1)]), $pLineCompleteKey);
                            }
                    }
                    foreach ($lineCodeParts as $lineCodePartKey => $lineCodePartValue) {
                            if ($lineCodePartKey == 0) {
                                $pLine	= $lineCodePartValue.$pLine;
                            } else {
                                $pLine	= $pLine.';'.$lineCodePartValue;
                            }
                        }

                        $pLineSplit	= explode(';', $pLine);
                        $pLine		= '';
                        foreach ($pLineSplit as $pLineSplitKey => $pLineSplitValue) {
                            if (strpos(strtoupper(preg_replace('/\s+/', '', $pLineSplitValue)), strtoupper($pFunction).'(') === 0) {
                                $pLineSplitStart    = 0;
                            } elseif (strpos(strtoupper($pLineSplitValue), ' '.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), ' '.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '	'.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '	'.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '('.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '('.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '.'.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '.'.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '='.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '='.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '{'.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '{'.strtoupper($pFunction).'(');
                            } elseif (strpos(strtoupper($pLineSplitValue), '}'.strtoupper($pFunction).'(') > -1) {
                                $pLineSplitStart    = 1 + strpos(strtoupper($pLineSplitValue), '}'.strtoupper($pFunction).'(');
                            } else {
                                $pLineSplitStart    = 0;
                            }

                            $pLineSplitTest	= strtoupper(preg_replace('/\s+/', '', substr($pLineSplitValue, $pLineSplitStart)));
                            if (strpos($pLineSplitTest, strtoupper($pFunction).'(') === 0) {
                                $parenthesisSplitCount	= 0;
                                $pLineSplitTestParts	= str_split($pLineSplitTest);
                                foreach ($pLineSplitTestParts as $pLineSplitTestKey => $pLineSplitTestValue) {
                                    if ($pLineSplitTestValue == '(') {
                                        $parenthesisSplitCount++;
                                    }
                                    if ($parenthesisSplitCount > 0) {
                                        if ($pLineSplitTestValue == ')') {
                                            $parenthesisSplitCount--;
                                        }
                                        if ($parenthesisSplitCount == 0) {
                                            $pLine	= $pLine.substr($pLineSplitValue, $pLineSplitStart).';';
                                        }
                                    } elseif ($pLineSplitTestValue == ')') {
                                        $parenthesisSplitCount--;
                                    }
                                }
                            }
                        }

                        if (
                            (substr_count(strtoupper(preg_replace('/\s+/', '', $pLine)), strtoupper($pFunction).'(') > 1)
                            and (strpos(strtoupper(preg_replace('/\s+/', '', $pLine)), ';'.strtoupper($pFunction).'(') > -1)
                        ) {
                            $errors	.= 'You can only call the '.$pFunction.'() function once from each line of code!  ';
                            $startingLineNumber = ((isset($errorStartingLineNumber)) ? $errorStartingLineNumber : (count($pFile) - $lineNumber));
                        };

                        break;
                } else {
                    $pLine	= trim($lineCode).$pLine;
                }
            }
        }
        if ($startingLineNumber != $endingLineNumber) {
            $echoLines = $startingLineNumber.' - '.$endingLineNumber;
        }
        else {
            $echoLines = $startingLineNumber;
        }
        $pLineError	= str_replace('	', ' ', trim($pLine));

        $pLineParts	= explode(';', $pLine);
        $pLine		= '';
        foreach ($pLineParts as $k => $v) {
            if (strpos(strtoupper(preg_replace('/\s+/', '', $v)), strtoupper($pFunction).'(') > -1) {
                $pLine .= $v;
            }
        }

        // Remove 'd(' or 'c(' from the beginning of the line
        $pLine = substr($pLine, (strpos($pLine, '(') + 1));

        // Remove ');' from the end of the line.
        // The 'a' fixes a glitch where strrchr ignores the last character in the haystack if the last character in the haystack is the needle.
        $pLine = $pLine.'a';
        $pLine = substr($pLine, 0, strpos($pLine, strrchr($pLine, ')')));
        // Split lines of code to get all variable names passed
        $pLine            = explode(',', $pLine);
        $temp             = array();
        $parenthesisFound = 0;
        foreach ($pLine as $k => $v) {
            if ($parenthesisFound > 0) {
                $parenthesisFound = $parenthesisFound + substr_count($v, '(');
                $parenthesisFound = $parenthesisFound - substr_count($v, ')');
                $temp[count($temp) - 1] = $temp[count($temp) - 1].','.$v;
            }
            else {
                $parenthesisFound = $parenthesisFound + substr_count($v, '(');
                $parenthesisFound = $parenthesisFound - substr_count($v, ')');
                $temp[] = $v;
            }
        }
        $pLine = $temp;

        // Trim spaces in each variable, but only if variable is one line
        $temp = array();
        foreach ($pLine as $k => $v) {
            $temp[] = explode("\n", $v);
        }
        $pLine = $temp;
        $temp  = array();
        foreach ($pLine as $k => $v) {
            foreach ($v as $key => $value) {
                if (trim($value) != '') {
                    $temp[$k][] = $value;
                }
            }
        }
        $pLine = $temp;
        foreach ($pLine as $k => $v) {
            if (count($v) == 1) {
                $pLine[$k] = trim($v[0]);
            }
            else {
                $trimV = strpos($v[0], substr(trim($v[0]), 0, 1));
                foreach ($v as $key => $value) {
                    $v[$key] = substr($value, $trimV, strlen($value));
                }
                $pLine[$k] = implode('', $v);
            }
        }

        return array(
            'type'		=> $type,
            'pFileName' => $pFileName,
            'values'    => $pLine,
            'args'      => $args,
            'echoLines' => $echoLines,
            'errors'	=> $errors,
            'errorLine'	=> $pLineError,
        );
    }


    function returnP($parseP) {

        // add testing for you logged in
        // if (is_object($core)) if (!$core->getdevmode()) return '';

        switch($parseP['type']) {
            case 'display':
                $key	= rand();
                $return = '<pre style="background: #fff; padding: 12px; border: 1px solid #d0d0d0; border-radius: 5px; font-family: monospace; font-size: 12px;">';
                if (empty($parseP['errors'])) {
                    if (count($parseP['args'])) {
                        foreach ($parseP['args'] as $k => $v) {
                            $return .= '<div style="width: 100%;">';
                            $return .= '<div style="float: right; width: 100px; text-align: right;"><a href="#" onclick=\'document.getElementById("values'.$key.$k.'").style.display="block"; document.getElementById("fileName'.$key.$k.'").style.display="block"; document.getElementById("pregLine'.$key.$k.'").style.display="none"; return false;\' style="text-decoration: none; color: #008000;">'.$parseP['echoLines'].'</a></div>';
                            $return .= '<div style="'.(($k != '0') ? 'margin-top: 24px; '
                                            : '').'color: #979797; overflow: hidden; text-overflow: ellipsis;" id="pregLine'.$key.$k.'">'.trim(preg_replace('/\s+/', ' ', $parseP['values'][$k])).'</div>';
                            $return .= '<div style="'.(($k != '0') ? 'margin-top: 24px; '
                                            : '').'color: #979797; display: none;" id="values'.$key.$k.'">'.$parseP['values'][$k].'</div>';
                            $return .= '<div style="margin-top: 8px; padding: 0px 8px; background-color: #fff; clear: both; border-left:1px solid #ccc; color: #0000ff; font-size: 12px;">'.print_r($v, 1).'</div>';
                            $return .= '<div style="margin-top: 8px; color: #979797; display: none;" id="fileName'.$key.$k.'">'.$parseP['pFileName'].'</div>';
                            $return .= '</div>';
                        }
                    } else {
                        $return .= '<div style="width: 100%;">';
                        $return .= '<div style="float: right; width: 100px; text-align: right;"><a href="#" onclick=\'document.getElementById("values'.$key.'0").style.display="block"; document.getElementById("fileName'.$key.'0").style.display="block"; document.getElementById("pregLine'.$key.'0").style.display="none"; return false;\' style="text-decoration: none; color: #008000;">'.$parseP['echoLines'].'</a></div>';
                        $return .= '<div style="color: #979797; overflow: hidden; text-overflow: ellipsis;" id="pregLine'.$key.'0"></div>';
                        $return .= '<div style="color: #979797; display: none;" id="values'.$key.'0"></div>';
                        $return .= '<div style="margin-top: 8px; padding: 0px 8px; background-color: #fff; clear: both; border-left:1px solid #ccc; color: #0000ff; font-size: 12px;"></div>';
                        $return .= '<div style="margin-top: 8px; color: #979797; display: none;" id="fileName'.$key.'0">'.$parseP['pFileName'].'</div>';
                        $return .= '</div>';
                    }
                } else {
                    $return .= '<div style="width: 100%;">';
                    $return .= '<div style="float: right; width: 100px; text-align: right;"><a href="#" onclick=\'document.getElementById("values'.$key.'0").style.display="block"; document.getElementById("fileName'.$key.'0").style.display="block"; document.getElementById("pregLine'.$key.'0").style.display="none"; return false;\' style="text-decoration: none; color: #008000;">'.$parseP['echoLines'].'</a></div>';
                    $return .= '<div style="color: #979797; overflow: hidden; text-overflow: ellipsis;" id="pregLine'.$key.'0">'.$parseP['errorLine'].'</div>';
                    $return .= '<div style="color: #979797; display: none;" id="values'.$key.'0">'.$parseP['errorLine'].'</div>';
                    $return .= '<div style="margin-top: 8px; padding: 0px 8px; background-color: #fff; clear: both; border-left:1px solid #ccc; color: #ff00ff; font-size: 12px;">'.$parseP['errors'].'</div>';
                    $return .= '<div style="margin-top: 8px; color: #979797; display: none;" id="fileName'.$key.'0">'.$parseP['pFileName'].'</div>';
                    $return .= '</div>';
                }
                $return .= '</pre>';
                break;
            case 'console':
                $maxLength = 15;
                $return = "\n".$parseP['pFileName'].' ('.$parseP['echoLines'].')';
                if (empty($parseP['errors'])) {
                    foreach ($parseP['args'] as $k => $v) {
                        if ((strpos(print_r($v, 1), "\n") == false) and (strlen(trim(preg_replace('/\s+/', ' ', $parseP['values'][$k]))) < $maxLength)) {
                            $return .= "\n".str_pad(trim(preg_replace('/\s+/', ' ', $parseP['values'][$k])).':', $maxLength, ' ', STR_PAD_RIGHT).str_replace("\n", "\n\t", print_r($v, 1));
                        }
                        else {
                            $return .=
                                    "\n".trim(preg_replace('/\s+/', ' ', $parseP['values'][$k])).":".
                                    "\n\t".str_replace("\n", "\n\t", trim(print_r($v, 1)));
                        }
                    }
                } else {
                    $return .= "\n".$parseP['errors'];
                }
                $return .= "\n";
                break;
            case 'consoleLog':
                $maxLength = 15;
                $return = '<script>console.log("'.$parseP['pFileName'].' ('.$parseP['echoLines'].')';
                if (empty($parseP['errors'])) {
                    foreach ($parseP['args'] as $k => $v) {
                        if ((strpos(print_r($v, 1), "\n") == false) and (strlen(trim(preg_replace('/\s+/', ' ', $parseP['values'][$k]))) < $maxLength)) {
                            $return .= '#n " +"'.str_pad(trim(preg_replace('/\s+/', ' ', $parseP['values'][$k])).':', $maxLength, ' ', STR_PAD_RIGHT).str_replace("\n", '#n " +"', print_r($v, 1));
                        }
                        else {
                            $return .=
                                    '#n " +"'.trim(preg_replace('/\s+/', ' ', $parseP['values'][$k])).":".
                                    '#n " +"'.str_replace("\n", '#n " +"', trim(print_r($v, 1)));
                        }
                    }
                } else {
                    $return .= '#n " +"'.$parseP['errors'];
                }
                $return .= '")</script>';
                $return	= str_replace('\\', '\\\\', $return);
                $return = str_replace('#n', '\\n', $return);
                break;
        }
        return $return;
    }

    function d() {
        $debug      = debug_backtrace();
        $debug      = reset($debug);
        $args       = func_get_args();
        $parseP     = parseP($debug, $args, 'display');
        echo returnP($parseP);
    }

    function c() {
        $debug      = debug_backtrace();
        $debug      = reset($debug);
        $args       = func_get_args();
        $parseP     = parseP($debug, $args, 'console');
        echo returnP($parseP);
    }

    function dr() {
        $debug      = debug_backtrace();
        $debug      = reset($debug);
        $args       = func_get_args();
        $parseP     = parseP($debug, $args, 'display');
        return returnP($parseP);
    }

    function cr() {
        $debug      = debug_backtrace();
        $debug      = reset($debug);
        $args       = func_get_args();
        $parseP     = parseP($debug, $args, 'console');
        return returnP($parseP);
    }

    function cl() {
        $debug      = debug_backtrace();
        $debug      = reset($debug);
        $args       = func_get_args();
        $parseP     = parseP($debug, $args, 'consoleLog');
        echo returnP($parseP);
    }

?>