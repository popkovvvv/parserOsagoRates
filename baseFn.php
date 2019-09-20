<?php

function minLenStr($val = '', $len = 1, $add = '0') {
  $min = $len - mb_strlen($val);
  return $min > 0 ? str_repeat($add, $min) . $val : $val;
}

function toLower($content) {
  $content = strtr($content, "АБВГДЕЁЖЗИЙКЛМНОРПСТУФХЦЧШЩЪЬЫЭЮЯ", "абвгдеёжзийклмнорпстуфхцчшщъьыэюя");
  return strtolower($content);
}

function dateToTime($dt, $format='d.m.Y'){
  $date = date_create_from_format($format, trim($dt));
  return (string)mktime(0,0,0,(int)date_format($date, 'm'),(int)date_format($date, 'd'),min(max((int)date_format($date, 'Y'),1990), 2035)).'000';
}

function toYaml($file, $data, $text = ""){
  // if(extension_loaded(yaml)){
    // file_put_contents($file, $text);
    // file_put_contents($file, yaml_emit($data, YAML_UTF8_ENCODING), FILE_APPEND);
  // }else{
    file_put_contents($file, $text);
    file_put_contents($file, ArrayToYml($data), FILE_APPEND);
  // }
}

function yaml2array($data = null){
  $result = array();
  $levelLast=0;
  $levelSelect = 0;
  $value = '';
  $valueLast = '';
  $key = '';
  $keyLast = '';
  $data = preg_replace(array("/\r/", "/\n\n/", "/\t/"), array("\n", "\n", "  "), $data);
  $array = explode("\n", $data);
  
  foreach($array as $item){
    $p = explode(":", $item);
    (int)$levelSelect = (mb_strlen($p[0]) - mb_strlen(trim($p[0])))/2;
    $key = isset($p[0])?trim($p[0]):'';
    $value = isset($p[1])?trim($p[1]):'';
    unset($setObj);
    unset($getObj);
      
    $l_l = "level_".($levelSelect==0?$levelSelect:$levelSelect-1);
    $l_c = "level_".$levelSelect;
    $l_n = "level_".($levelSelect+1);
    
    // if($key[0]=="#") continue;
    if($value[0]=="#") $value = array();
    
    if($value[0]=="&"){ 
      $setObj = "GLOBAL_".mb_substr($value, 1);
      $value = array(); 
    }
    if($value[0]=="*"){ 
      $getObj = "GLOBAL_".mb_substr($value, 1);
      $value = array(); 
    }
        
    if(mb_strlen($key) == 0) continue;

    // echo "{$l_l} - {$l_c} - {$l_n} : {$key}:{$value}\n";
    
    if($levelSelect == 0){
    
      $result[$key]=array();
      ${$l_n} = &$result[$key];
      
      if(!empty($setObj)) ${$setObj} = &$result[$key];
      
    }else if($levelLast < $levelSelect){
    
      ${$l_c}[$key] = empty($getObj)?$value:${$getObj};
      ${$l_n} = &${$l_c}[$key];
      
      if(!empty($setObj)) ${$setObj} = &${$l_c}[$key];
      
    }else if($levelLast > $levelSelect){
      
      ${$l_c}[$key] = empty($getObj)?$value:${$getObj};
      ${$l_n} = &${$l_c}[$key];
      
      if(!empty($setObj)) ${$setObj} = &${$l_c}[$key];
      
    }else if($levelLast == $levelSelect){
    
      ${$l_c}[$key] = empty($getObj)?$value:${$getObj};
      ${$l_n} = &${$l_c}[$key];
      
      if(!empty($setObj)) ${$setObj} = &${$l_c}[$key];
      
    }
    
    $levelLast = $levelSelect;
    $valueLast = $value;
    $keyLast = $key;
  }
  return $result;
}

function toNum($e = "0,00", $eval = null, $value = ''){
  // return number_format(floatval(str_replace(array(","," "), array(".", ''), $e)), 6, ".", "");
  $e = preg_replace('/[^0-9\,\.\-\+]/ui', '', $e);
  $e = floatval(str_replace(array(","), array("."), $e));
  
  if(!empty($eval)){
  
    $eval = str_replace(array('$'), array($e), $eval);
    if(eval("return ({$eval});")){
      return $value;
    }
  }
  
  return $e;
}
function toStr($e = "0,00", $repl=''){
  $from = array(
            '/é/',
            '/ё/',
            '/і/',
            '/[^a-zA-Zа-яА-Я0-9\ \<\>\=\-\_\!\@\$\%\^\&\*\(\)\{\}\/\?\.\,\;\:\*\+\\\\]/ui'
          );
  $to = array(
            'e',
            'e',
            'i',
            $repl
        );
  $e = preg_replace($from, $to, $e);
  $e = trim($e);
  // $pos0 = mb_strpos($e, "0");
  // if($pos0 !== false && $pos == 0){
  // if($e[0]=='0'){
    // echo "{$e} \t\t\t {$e[0]}\n";
    // return "'".$e."'";
  // }
  return (strval(floatval($e))===$e)?"'".$e."'":$e;
}

function ArrayToYml($res, $data=""){
  ############################## 0
  foreach ($res as $k1=>$v1){
    if(is_array($v1)){
      $data.=str_repeat("  ", 0).$k1.":\n";
      ############################## 1
      foreach ($v1 as $k2=>$v2){
        if(is_array($v2)){
          $data.=str_repeat("  ", 1).$k2.":\n";
          ############################## 2
          foreach ($v2 as $k3=>$v3){
            if(is_array($v3)){
              $data.=str_repeat("  ", 2).$k3.":\n";
              ############################## 3
              foreach ($v3 as $k4=>$v4){
                if(is_array($v4)){
                  $data.=str_repeat("  ", 3).$k4.":\n";
                  ############################## 4
                  foreach ($v4 as $k5=>$v5){
                    if(is_array($v5)){
                      $data.=str_repeat("  ", 4).$k5.":\n";
                      ############################## 5
                      foreach ($v5 as $k6=>$v6){
                        if(is_array($v6)){
                          $data.=str_repeat("  ", 5).$k6.":\n";
                          ############################## 6
                          foreach ($v6 as $k7=>$v7){
                            if(is_array($v7)){
                              $data.=str_repeat("  ", 6).$k7.":\n";
                              ############################## 7
                              foreach ($v7 as $k8=>$v8){
                                if(is_array($v8)){
                                  $data.=str_repeat("  ", 7).$k8.":\n";
                                  foreach ($v8 as $k9=>$v9){
                                    if(is_array($v9)){
                                      $data.=str_repeat("  ", 8).$k9.":\n";
                                      ############################## 7
                                      foreach ($v9 as $k10=>$v10){
                                        if(is_array($v10)){
                                          $data.=str_repeat("  ", 9).$k10.":\n";
                                          $data.='...!!!...!!!...';
                                        }else{
                                          $data.=str_repeat("  ", 9).$k10.": ".$v10."\n";
                                        }
                                      }
                                      ############################## 7
                                    }else{
                                      $data.=str_repeat("  ", 8).$k9.": ".$v9."\n";
                                    }
                                  }
                                }else{
                                  $data.=str_repeat("  ", 7).$k8.": ".$v8."\n";
                                }
                              }
                              ############################## 7
                            }else{
                              $data.=str_repeat("  ", 6).$k7.": ".$v7."\n";
                            }
                          }
                          ############################## 6
                        }else{
                          $data.=str_repeat("  ", 5).$k6.": ".$v6."\n";
                        }
                      }
                      ############################## 5
                    }else{
                      $data.=str_repeat("  ", 4).$k5.": ".$v5."\n";
                    }
                  }
                  ############################## 4
                }else{
                  $data.=str_repeat("  ", 3).$k4.": ".$v4."\n";
                }
              }
              ############################## 3
            }else{
              $data.=str_repeat("  ", 2).$k3.": ".$v3."\n";
            }
          }
          ############################## 2
        }else{
          $data.=str_repeat("  ", 1).$k2.": ".$v2."\n";
        }
      }
      ############################## 1
    }else{
      $data.=str_repeat("  ", 0).$k1.": ".$v1."\n";
    }
  }
  ############################## 0
  return $data;
}
/**
 * Функция находит косячные русские буквы внутри английских слов и заменяет на английские
 * @param string $word Английское слово с русскими буквами
 */
function RusToEngLetters($word = "") {
    $rusPatterns = array ('а','о','е','у','и','с','х','р','Т','Х','P','А','E','O','H','K','С','B','M');
    $engPatterns = array ('a','o','e','y','u','c','x','p','T','X','P','A','E','O','H','K','C','B','M');
    if(preg_match('#(\s*[a-z]{2,}\s*)#i', $word) === 1) {
        $arrForGrep=[$word];
        $engWord=preg_grep('#(\s*[a-z]{2,}\s*)#i',$arrForGrep);
        $fixedEngWord=str_replace($rusPatterns, $engPatterns, $engWord);
        $word=str_replace($fixedEngWord, $engWord, $word);
    }
    if(preg_match('/([а-яё]{2,})/iu', $word) != 1 || preg_match('/(\d*[а-яё]{2,}\d)/iu', $word) === 1) {
        $word=str_replace($rusPatterns, $engPatterns, $word);
        // file_put_contents('log.txt', var_export($make, 1)."\n", FILE_APPEND);
    }
    return $word;
}

// <html>
// <body>
// <script type='text/javascript'>

// function saveTextAsFile(text, fileNameToSaveAs)
// {
	// var textToWrite = document.getElementById(text).value;
	// var textFileAsBlob = new Blob([textToWrite], {type:'text/plain'});

	// var downloadLink = document.createElement("a");
	// downloadLink.download = fileNameToSaveAs;
	// downloadLink.innerHTML = "Download File " + fileNameToSaveAs;
	// if (window.webkitURL != null)
	// {
		// Chrome allows the link to be clicked
		// without actually adding it to the DOM.
		// downloadLink.href = window.webkitURL.createObjectURL(textFileAsBlob);
	// }
	// else
	// {
		// Firefox requires the link to be added to the DOM
		// before it can be clicked.
		// downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
		// downloadLink.onclick = destroyClickedElement;
		// downloadLink.style.display = "none";
		// document.body.appendChild(downloadLink);
	// }

	// downloadLink.click();
// }

// function destroyClickedElement(event)
// {
	// document.body.removeChild(event.target);
// }

// </script>
// echo "<textarea id='id".hash("md5", $val)."' style='width:500px;height:200px;'>".ArrayToYml($res, $data)."</textarea>";
// echo "<a href=\"javascript:saveTextAsFile('id".hash("md5", $val)."', '".$val."')\">Сохранить ".$val."</a><hr>";