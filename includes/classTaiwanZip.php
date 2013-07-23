<?php

class taiwanZip
{
    private $_data;

    public function __construct($parsedFile = '../parser/parsed.json')
    {
        $this->_data = json_decode(file_get_contents($parsedFile), true);
    }

    public function addressChunker($str)
    {
        $normStr = $this->_normString($str);
        $data = $this->_getData($this->_data, $normStr);

        if ($data !== NULL) {
            echo "Query string: {$str}\n";
            echo "Normalized Query string: {$normStr}\n";
            echo "Most match: {$data['keyStr']}\n";
            echo "Data:\n";
            print_r($data['data']);
        }
    }

    private function _getData($data, $str, $keyStr = '', $level = 0)
    {
        if (3 === $level) {
            if (strlen($keyStr) > strlen($str)) {
                return NULL;
            } else {
                return array('data' => $data, 'keyStr' => $keyStr);
            }
        }
        $key = $this->_findKey(array_keys($data), $str);
        if (NULL !== $key) {
            $keyStr .= $key;
            return $this->_getData($data[$key], $str, $keyStr, $level + 1);
        }
    }

    private function _findKey($keys, $str)
    {
        foreach ($keys as $key) {
            // full match
            if (false !== strstr($str, $key)) {
                return $key;
            }
        }

        // use levenshtein to retrieve posiible keys

        $score = array();
        foreach ($keys as $key) {
            $score[$key] = levenshtein($key, $str);
        }

        asort($score);
        $sortedKeys = array_keys($score);

        return $sortedKeys[0];
    }

    private function _normString($str)
    {
        $numbPattern = '/(?<numb>[○０-９一二三四五六七八九十廿卅百]+)(?<suffix>[鄰巷段號樓])/u';

        $match = array();
        preg_match_all($numbPattern, $str, $match);
        if (count($match['numb']) > 0) {
            $combine = array_combine($match['numb'], $match['suffix']);
            foreach ($combine as $strFound => $suffix) {
                $target = "{$strFound}{$suffix}";
                $replace = $this->_translateNumb($strFound) . $suffix;
                $str = str_replace($target, $replace, $str);
            }
        }

        // convert special name
        $str = preg_replace('/台?北縣/u', '新北市', $str);

        return $str;
    }

    private function _translateNumb($str)
    {
        $str = str_replace('○', '0', $str);
        $str = str_replace('百', '', $str);

        $pattern = str_split('０１２３４５６７８９一二三四五六七八九廿卅', 3);
        $replace = str_split('012345678912345678923', 1);
        $str = str_replace($pattern, $replace, $str);

        // handle 十
        $str = preg_replace('/^十/', '1', $str);
        $str = preg_replace('/十$/', '0', $str);
        $str = str_replace('十', '', $str);

        return $str;
    }
}
