<?php

class taiwanZip {

    private $_data;

    public function __construct($parsedFile = '../parser/parsed.json') {
        $this->_data = json_decode(file_get_contents($parsedFile), true);
    }

    public function addressChunker($str) {
        $normStr = $this->_normString($str);
        $data = $this->_getData($this->_data, $normStr);

        if ($data !== NULL) {
            return array(
                    'query' => $str,
                    'norm'  => $normStr,
                    'match' => $data['keyStr'],
                    'data'  => $data
                    );
        }
        return NULL;
    }

    private function _getData($data, $str, $keyStr = '', $level = 0) {
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

    private function _findKey($keys, $str) {
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

    private function _normString($str) {
    {
        $numbPattern = '/(?<numb>[○０-９零一二三四五六七八九十廿卅百之]+)(?<suffix>[室鄰巷弄段號樓]|$)/u';

        $match = array();
        preg_match_all($numbPattern, $str, $match);
        $matchCnt = count($match[0]);
        if ($matchCnt > 0) {
            for ($i = 0; $i < $matchCnt; $i++) {
                $target = $match[0][$i];
                $strFound = $match['numb'][$i];
                $suffix = $match['suffix'][$i];
                $replace = $this->_translateNumb($strFound) . $suffix;
                $replace = preg_replace('/(\d)之(\d)/u', '$1-$2', $replace);
                $str = str_replace($target, $replace, $str);
            }
        }

        // convert special name
        $str = preg_replace('/台?北縣/u', '新北市', $str);

        return $str;
    }

    private function _translateNumb($str) {
        $str = str_replace('○', '0', $str);
        $str = preg_replace('/百$/', '00', $str);
        $str = str_replace('百', '', $str);

        $pattern = str_split('０１２３４５６７８９零一二三四五六七八九廿卅', 3);
        $replace = str_split('0123456789012345678923', 1);
        $str = str_replace($pattern, $replace, $str);

        // handle 十
        $str = preg_replace('/^十/', '1', $str);
        $str = preg_replace('/十$/', '0', $str);
        $str = str_replace('十', '', $str);

        return $str;
    }
}
