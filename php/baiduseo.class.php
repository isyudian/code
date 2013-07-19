<?php
/**
 * 百度SEO工具类 
 * @author: liyi qiaotouhe#gmail.com
 * @version 1.0
 *
 * @example
 * $result = BaiduSeo::getInstance()->rank('桥头河', 'www.qiaotouhe.cn');
 * print_r($result);
 * $result = BaiduSeo::getInstance()->ping('桥头河生活', 'www.qiaotouhe.cn');
 * print_r($result);
 *
 */
class BaiduSeo
{
    private static $instance;
    private static $px = 0;

    private function __construct(){}

    private function __clone(){}

    public static function getInstance()
    {
        if (is_null(self::$instance) || !(self::$instance instanceof BaiduSeo))
        {
            $c = __CLASS__;
            self::$instance = new $c();
        }

        return self::$instance;
    }

    /**
     * 获取特定网址关键词的百度排名
     * @name rank
     * @param keyword, url
     * @return array(
     *  'page'   => '第几页',
     *  'number' => '第X页的第几位',
     *  'rank'   => '排名',
     *  'title'  => '标题',
     *  'url'    => '百度搜索页地址',
     * )
     */
    public function rank($keyword, $url, $page=1)
    {
        $rsState = false;

        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 10)
        {
            return false;
        }	

        $contents = $this->requestGet("http://www.baidu.com/s?wd=$enKeyword&&pn=$firstRow");
        preg_match_all('/<table[^>]*?class="result(-op)?"[^>]*>[\s\S]*?<\/table>/i', $contents, $rs);

        foreach($rs[0] as $k=>$v)
        {
            self::$px++;
            if(strpos($v, $url))
            {
                $rsState = true;
                preg_match_all('/<h3[\s\S]*?(<a[\s\S]*?<\/a>)/', $v, $rs_t);

                $url = "http://www.baidu.com/s?wd={$enKeyword}&pn={$firstRow}";
                $rs_t[1][0] = strip_tags($rs_t[1][0]);

                return array('page'=>$page,'num'=>++$k, 'rank'=>self::$px, 'title'=>$rs_t[1][0], 'url'=>$url);

                //echo '当前 "' . $url . '" 在百度关键字 "' . $keyword . '" 中的排名为：' . self::$px;
                //echo '<br>';
                //echo '第' . $page . '页;第' . ++$k . "个<a target='_blank' href='http://www.baidu.com/s?wd=$enKeyword&&pn=$firstRow'>进入百度</a>"; 
                //echo '<br>';
                //echo $rs_t[1][0];
                break;
            }
        }

        unset($contents);

        if($rsState === false)
        {
            return $this->getRank($keyword, $url, ++$page);
        }
    }

    /**
     * 百度ping工具
     * @name ping
     * @param title, url
     * @return boolean true | false
     */
    public function ping($title='', $url='')
    {
        if(empty($title) || empty($url))
        {
            return false;
        }

        $baiduXML = "<?xml version=\"1.0\" encoding=\"gb2312\"?>
            <methodCall>
            <methodName>weblogUpdates.extendedPing</methodName>
            <params>
            <param><value><string>{$title}</string></value></param>
            <param><value><string>{$url}</string></value></param>
            <param><value><string>{$url}</string></value></param>
            </params>
            </methodCall>";

        $res = $this->requestPost("http://ping.baidu.com/ping/RPC2", $baiduXML);
        print_r($res);
        if(strpos($res, "<int>0</int>"))
        {
            return true;
        }

        return false;
    }

    /**
     * 获取网站百度收录总数 
     * @name count
     * @param  keyword
     * @return int
     */
    public function countSite($keyword)
    {
        $url = 'http://www.baidu.com/s?wd=site%3A'.$keyword;

        $site = $this->requestGet($url);

        preg_match("/找到相关结果数(.*)个。/", $site, $count);

        $re = array('找到相关结果数','个',',');
        $count = str_replace($re,'',$count);

        return $count[1];
    }

    /**
     * 获取百度关键词查询结果总数 
     * @param str $keyword 
     * @param str 整型字符
     */
    public function countKeyword($keyword) {
        if(empty($keyword))
        {
            return false;
        }

        $html = $this->requestGet('http://www.baidu.com/s?wd='.$keyword);
        $search = '/<span class="nums"[^>]*?>[^<]*?([0-9,]*?)</i'; 
        preg_match($search,$html,$match);
        $match = str_replace(',','',$match); 
        preg_match('/<span[^>]*>.*?(\d+)/',$match[0],$r);

        return $r[1];
    }

    /*
     * 获取网站的快照时间 
     * @param str $url 一级域名
     * @param str 时间格式
     */
    public function kuaiZhao($text) {
        $html = $this->requestGet('http://www.baidu.com/s?word='.$text);
        $text = str_replace('.','\.',addslashes($text));
        $search = '/<b>'.$text.'<\/b>[^<]*((?:19|20)[0-9]{2}-(?:1[012]|[1-9])-(?:[12][0-9]|3[01]|[1-9]))/';
        preg_match($search, $html, $r);

        return $r[1];
    }

    /**
     * 获取百度相关关键词
     *
     */
    public function relative($keyword)
    {
        $url = "http://unionsug.baidu.com/su?callback=window.bdsug.sug&wd={$keyword}&cb=window.bdsug.sug&from=superpage&t=768&_=1347766725419";
        return $url;
    }

    /**
     * 刷新百度关键词
     */
    public function flashKeyword($keyword, $url='', $times=5)
    {
        if(empty($keyword))
        {
            return false;
        }

        $baidu = 'http://www.baidu.com/s?f=8&rsv_bp=1';
        for($i=1;$i<=$times;$i++)
        {
            $allurl[] = $baidu.'&bs='.$url.'&wd='.urlencode($keyword).'&inputT='.rand(500,3000);
            $allurl[] = $baidu.'&bs='.urlencode($url).'&wd='.urlencode($keyword).'&inputT='.rand(500,3000);
            $allurl[] = $baidu.'&bs='.$url.'&wd='.$keyword.'&inputT='.rand(500,3000);
            $allurl[] = $baidu.'&bs='.urlencode($url).'&wd='.$keyword.'&inputT='.rand(500,3000);

            foreach($allurl as $item)
            {
                $this->requestGet($item);
            }
        }

        return true;
    }

    /**
     * 获取百度的热词
     * @return array  返回百度的热词数据(数组返回)
     */
    public function hotKeyWord()
    {
        $templateRss = $this->requestGet('http://top.baidu.com/rss_xml.php?p=top10');
        if(preg_match('/<table>(.*)<\/table>/is', $templateRss, $_description)) 
        {
            $templateRss = $_description [0];
            $templateRss = str_replace("&", "&amp;", $templateRss);
        }

        $templateRss = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $templateRss;
        $xml = simplexml_load_String($templateRss);
        foreach ($xml->tbody->tr as $temp) 
        {
            if (!empty ($temp->td->a)) 
            {
                $keyArray [] = trim(($temp->td->a));
            }
        }

        return $keyArray;
    }

    /**
     * HTTP CALL GET
     */
    private  function requestGet($url, $timeout = 15)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Expect:'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);
        return trim($result);
    }

    private function getRealIP(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
        {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) 
        {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) 
            {
                array_unshift($ips, $ip); $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }


    /**
     * HTTP CALL POST
     */
    private function requestPost($url, $str)
    {

        $header[]="Content-Type: text/xml; charset=utf-8";
        $header[]="User-Agent: Apache/1.3.26 (Unix)";
        //$header[]="User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
        //$header[]="Host: ".$this->getRealIP();
        $header[]="Host: 127.0.0.1";

        $header[]="Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2";
        $header[]="Connection: keep-alive";
        $header[]="Content-Length: ".strlen($str);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)");
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //    'Accept-Language: zh-cn', 
        //    'Connection: Keep-Alive', 
        //    'Cache-Control: no-cache'
        //));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        $result = curl_exec($ch);

        curl_close($ch);
        return trim($result);
    }
}
