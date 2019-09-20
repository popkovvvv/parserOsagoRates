<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 15.08.2019
 * Time: 14:33
 */
include_once 'functions.php';
include_once 'baseFn.php';
class Parser
{

    public function getTxt()
    {
        $dir = 'in';
        $txtFiles = scandir($dir, 1);

        foreach ($txtFiles as $file) {
            if ($file != "." && $file != "..") {
                $ymls = [
                    'SOGAZWebservice.txt' => 'SOGAZWebservice.yml',
                    'ALPHAWebservice.txt' => 'ALPHAWebservice.yml',
                    'Guide.txt' => 'Guide.yml',
                    'KITWebservice.txt' => 'KITWebservice.yml',
                    'UgoriaWebservice.txt' => 'UgoriaWebservice.yml',
                    'OSKWebservice.txt' => 'OSKWebservice.yml',
                ];

                self::structuredFile($file,$ymls[$file]);
            }
        }

    }
    public function structuredFile($fileTxt,$fileToSave)
    {
        $fileCompany = file_get_contents('in/' . $fileTxt);
        $fileCompany = explode("\n", $fileCompany);
        $titles = array_shift($fileCompany);
        $titles = explode("\t", $titles);

        $newTitles = array_map('Functions::changeTitles', $titles);
        $newTitles = array_flip($newTitles);

        $region = '';
        $city = '';
        $data = [];
        $regionO = '';
        $regionId = isset($newTitles['region']) ? $newTitles['region'] : null;
        $cityId = isset($newTitles['city']) ? $newTitles['city'] : null;

        unset($newTitles['region']);
        unset($newTitles['city']);
        foreach ($fileCompany as $regionRates) {
            $regionRates = explode("\t", $regionRates);

            if (!empty($regionRates[1])
                || mb_strpos(mb_strtolower($regionRates[0]), 'иностранных') !== false
                || mb_strpos(mb_strtolower($regionRates[0]), 'техосмотра') !== false
                || mb_strpos(mb_strtolower($city), 'cледования') !== false
                || mb_strpos(mb_strtolower($region), 'cледующих') !== false
            ) {

                if (isset($regionId) && !empty($regionRates[$regionId])) {
                    $region = $regionRates[$regionId];
                } elseif (empty($region)) {
                    $region = $regionRates[$cityId];
                }

                if (mb_strpos(mb_strtolower($region), 'иностранных') !== false
                    || mb_strpos(mb_strtolower($region), 'осмотра') !== false
                    || mb_strpos(mb_strtolower($region), 'техосмотра') !== false
                    || mb_strpos(mb_strtolower($region), 'cледующих') !== false
                    || mb_strpos(mb_strtolower($city), 'cледования') !== false){

                    $region = Functions::changeAnotherCity($region);

                }

                //в $city находятся регионы и города в некоторых строховых компаниях
                if (isset($cityId)) {
                    $city = $regionRates[$cityId];
                    //Изменение города на иностранное гос и следует к месту регистрации ТС
                    if (mb_strpos(mb_strtolower($city), 'иностранных') !== false
                        || mb_strpos(mb_strtolower($city), 'осмотра') !== false
                        || mb_strpos(mb_strtolower($city), 'техосмотра') !== false
                        || mb_strpos(mb_strtolower($region), 'cледующих') !== false
                        || mb_strpos(mb_strtolower($city), 'cледования') !== false){
                        $city = Functions::changeAnotherCity($city);
                        $region = $city;


                    }
                    if ($city == 'Все города и населенные пункты'){
                        $city = $region;
                    }
                }
                $city = preg_replace('~\([^()]*\)~', '', $city);
                $city = trim($city);

                //ИСПРАВЛЕНИЯ ПО РЕГИОНАМ И ГОРОДАМ

                switch ($region){
                    case "Санкт-Петербург":
                        $city = str_replace('Город','', $city);
                        $city = trim($city);
                        break;
                    case "Республика Башкортостан":
                        $city = str_replace('Благовещенск','Благовещенск (Баш)', $city);
                        break;
                    case "Кемеровская область":
                        $city = str_replace('Березовский','Березовский (Кем. обл.)', $city);
                        break;
                    case "Красноярский край":
                        $city = str_replace('Железногорск','Железногорск (Красн.)', $city);
                        break;
                    case "Ханты-Мансийский автономный округ - Югра":
                        $region = "Ханты-Мансийский автономный округ";
                        break;
                    case "Ленинградская область":
                        $city = "Ленинградская область";
                        break;
                    case "Московсковская область":
                        $city = "Московсковская область";
                        break;
                }


                $regionRates = Functions::validateRegion($regionRates,$regionId,$regionO);
                //$checkOnRegion смотрит ситуацию когда город это и есть регион
                $checkOnRegion = Functions::checkCityOnRegion($city);
                Functions::currectTerritory($region, $city,$fileToSave);


                foreach ($newTitles as $title => $id) {
                   //$validateRates = Functions::minAndMax($regionRates[$id],$id);
                    //Формируем массив
                    if ($checkOnRegion == true){
                        $data[$title][trim($regionRates[$id])][$city][] = Functions::mb_ucfirst($city);
                    }else{
                        if ($city != 'прочие города и населенные пункты') {
                            file_put_contents('logs/notRegion.txt', var_export($city, 1)."\n", FILE_APPEND);
                        }
                            $data[$title][trim($regionRates[$id])][$region][] = Functions::mb_ucfirst($city);
                    }
                }
            } else {
                $region = $regionRates[0];
            }
        }

        self::toYaml($data,$fileToSave);
    }


    public function toYaml($data,$fileToSave)
    {

        $result = [];

        foreach ($data as $title => $arrayData){
            foreach ($arrayData as $rate => $cityAndRate){
                foreach ($cityAndRate as $region => $city){
                        $minAndMax = Functions::baseBankArray($title);
                    if ($rate >= $minAndMax['min'] && $rate < $minAndMax['max']
                        || $region == "Следует к месту регистрации ТС" || $region == "Иностранное государство"
                        || $city == "Следует к месту регистрации ТС" || $city == "Иностранное государство") {
                        if (!empty(mb_stripos($title, "юрики") !== false)) {
                            if ($region == $city[0]){
                                $cityData = '';
                            }else{
                                $cityData =  " && (" . implode(" || ", array_map('Functions::quotes',$city)) . ")";
                                $cityData = str_replace(', ','" || $4 == "',$cityData);

                            }
                            $titleAM = Functions::changeTitlesOutPut($title);

                            if ($region == "Следует к месту регистрации ТС" || $region == "Иностранное государство"){
                                $result[$titleAM]['expression']["($1 == \"company\" && $2 == " . '"'.$region.'"'.")"]['base'] = $rate;

                            }else{
                                $result[$titleAM]['expression']["($1 == \"company\" && $3 == " . '"'.$region.'"' .$cityData.")"]['base'] = $rate;
                            }
                            $result[$titleAM]['base']['company'] = $minAndMax['max'];
                            $result[$titleAM]['region'] = 1;
                            $result[$titleAM]['trailer']['company'] = 1.16;
                            $result[$titleAM]['power'] = "true";
                        } elseif (!empty(mb_stripos($title, "физики") !== false)) {
                            if ($region == $city[0]){
                                $cityData = '';
                            }else{
                                $cityData =  " && (" . implode(" || ", array_map('Functions::quotes',$city)) . ")";
                                $cityData = str_replace(', ','" || $4 == "',$cityData);
                            }
                            $titleAM = Functions::changeTitlesOutPut($title);
                            if ($region == "Следует к месту регистрации ТС" || $region == "Иностранное государство"){

                                $result[$titleAM]['expression']["($1 == \"person\" && $2 == " . '"'.$region.'"'.")"]['base'] = $rate;

                            }else{
                                $result[$titleAM]['expression']["($1 == \"person\" && $3 == " . '"'.$region.'"' .$cityData.")"]['base'] = $rate;
                            }
                            $result[$titleAM]['base']['person'] = $minAndMax['max'];
                            $result[$titleAM]['region'] = 1;
                            $result[$titleAM]['trailer']['person'] = 1;
                            $result[$titleAM]['power'] = "true";
                        } else {
                            if ($region == $city[0]){
                                $cityData = '';
                            }else{
                                $cityData =  " && (" . implode(" || ", array_map('Functions::quotes',$city)) . ")";
                                $cityData = str_replace(', ','" || $4 == "',$cityData);

                            }
                            if ($region == "Следует к месту регистрации ТС" || $region == "Иностранное государство") {
                                $result[$title]['expression']["($2 == " . '"'.$region.'"'.")"]['base'] = $rate;

                            }else{
                                $result[$title]['expression']["($3 == " . '"'.$region.'"' .$cityData.")"]['base'] = $rate;

                            }
                            $result[$title]['base'] = $minAndMax['max'];
                            $result[$title]['region'] = 1;
                            //определение коэфицента
                            if ($title == 'Мотоциклы и мотороллеры'){
                                $result[$title]['trailer'] = 1.16;
                            }elseif ($title == 'Грузовые а/м с разрешенной массой до 16 т вкл.'){
                                $result[$title]['trailer'] = 1.40;
                            }elseif ($title == 'Грузовые а/м с разрешенной массой свыше 16 т'){
                                $result[$title]['trailer'] = 1.25;
                            }elseif ($title == 'Тракторы, дорожно-строительные и иные машины'){
                                $result[$title]['trailer'] = 1.24;
                            }else{
                                $result[$title]['trailer'] = 1;
                            }
                        }
                    }else {
                        if ($title != "Легковые а/м, юрики" && $title != "Легковые а/м, физики"){
                            $result[$title]['base'] = $minAndMax['max'];
                            $result[$title]['region'] = 1;
                            //определение коэфицента
                            if ($title == 'Мотоциклы и мотороллеры'){
                                $result[$title]['trailer'] = 1.16;
                            }elseif ($title == 'Грузовые а/м с разрешенной массой до 16 т вкл.'){
                                $result[$title]['trailer'] = 1.40;
                            }elseif ($title == 'Грузовые а/м с разрешенной массой свыше 16 т'){
                                $result[$title]['trailer'] = 1.25;
                            }elseif ($title == 'Тракторы, дорожно-строительные и иные машины'){
                                $result[$title]['trailer'] = 1.24;
                            }else{
                                $result[$title]['trailer'] = 1;
                            }

                        }
                        if ($rate != $minAndMax['max']){
                            file_put_contents('logs/minAndMax.txt', var_export([$fileToSave,$rate], 1)."\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
       toYaml('out/'.$fileToSave,$result);
       // file_put_contents('$data.txt', var_export(json_encode($result,JSON_UNESCAPED_UNICODE), 1)."\n", FILE_APPEND);


    }


}
