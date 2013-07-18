<?php

class taiwanZip
{
    private $_data;

    public function __construct()
    {
        $this->_data = json_decode(file_get_contents('../parser/parsed.json'), true);
    }

    public function addressChunker($str)
    {
        // 縣市/鄉鎮市區/路街村里/巷/弄/號/樓
        $str = $this->_normString($str);
        $data = $this->_getData($this->_data, $str);

        if ($data !== NULL) {
            echo "Query string: {$str}\n";
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
        $keys = array_keys($data);
        $key = $this->_findKey($keys, $str, $level);
        $keyStr .= $key;
        if (NULL !== $key) {
            return $this->_getData($data[$key], $str, $keyStr, $level+1);
        }
    }

    private function _findKey($keys, $str)
    {
        foreach ($keys as $key) {
            if (false !== strstr($str, $key)) {
                return $key;
            }
        }

        // use levenshtein to retrieve posiible keys
        $str = str_replace('台北縣', '新北市', $str);

        $score = array();
        foreach ($keys as $key) {
            $score[$key] = levenshtein($key, $str);
        }

        asort($score);
        $keys = array_keys($score);

        return $keys[0];
    }

    private function _normString($str)
    {
        $patterns = array(
                        '/(?<numb>[○０-９一二三四五六七八九十廿卅百]+)(?<suffix>[鄰巷段號樓])/u'
                    );

        foreach ($patterns as $pattern) {
            $match = array();
            preg_match_all($pattern, $str, $match);
            if (count($match['numb']) > 0) {
                $combine = array_combine($match['numb'], $match['suffix']);
                foreach ($combine as $strFound => $suffix) {
                    $target = "{$strFound}{$suffix}";
                    $replace = $this->_translateNumb($strFound) . $suffix;
                    $str = str_replace($target, $replace, $str);
                }
            }
        }

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
