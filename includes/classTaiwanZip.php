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

        $cityKeys = array_keys($this->_data);
        $cityKey = $this->_findKey($cityKeys, $str);

        $distKeys = array_keys($this->_data[$cityKey]);
        $distKey = $this->_findKey($distKeys, $str);

        $roadKeys = array_keys($this->_data[$cityKey][$distKey]);
        $roadKey = $this->_findKey($roadKeys, $str);

        var_dump($this->_data[$cityKey][$distKey][$roadKey]);
    }

    private function _findKey($keys, $str)
    {
        foreach ($keys as $key) {
            if (false !== strstr($str, $key)) {
                return $key;
            }
        }
        return NULL;
    }

    private function _normString($str)
    {
        $patterns = array(
                        '/(?<numb>[０-９一二三四五六七八九十廿卅百]+)[段號]/u'
                    );

        foreach ($patterns as $pattern) {
            $match = array();
            preg_match_all($pattern, $str, $match);
            if (count($match['numb']) > 0) {
                foreach ($match['numb'] as $strFound) {
                    $str = str_replace($strFound, $this->_translateNumb($strFound), $str);
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
        $str = str_replace('十', '', $str);

        return $str;
    }
}
