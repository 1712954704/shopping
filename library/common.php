<?php

class Common
{
    protected static $_module_configs = [];
    protected static $_exchange_config = [];
    protected static $_vip_config;
    protected static $_int_bitmap;
    static protected $_dataBody = null;
    protected static $_week_day_name_arr = array(
        'chinese' => array(
            0 => '星期天',
            1 => '星期一',
            2 => '星期二',
            3 => '星期三',
            4 => '星期四',
            5 => '星期五',
            6 => '星期六',
        ),
        'english' => array(
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Wednesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ),
    );

    protected static $_month_name_arr = array(
        'english' => array(
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        )
    );


    /**
     * Separator for nesting levels of configuration data identifiers.
     *
     * @var string
     */
    protected static $nestSeparator = '.';

    /**
     * Directory of the file to process.
     *
     * @var string
     */
    protected static $directory;


    /**
     * 获取客户端IP地址
     * 1.REMOTE_ADDR:浏览当前页面的用户计算机的ip地址.
     * 2.HTTP_X_FORWARDED_FOR: 浏览当前页面的用户计算机的网关.
     * 3.HTTP_CLIENT_IP:客户端的ip.
     *
     * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv 是否进行高级模式获取（有可能被伪装，）
     * @return mixed
     */
    static public function getIp($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) unset($arr[$pos]);
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    static public function get_ip2()
    {
        $ip = null;
        if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        $res = preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
        return $res;
    }

    static public function response_error_header($code, $message = "", $extend_data = [])
    {
        $code_str = '';
        switch ($code) {
            case 400:
                $code_str = "Bad Request";
                break;
            case 401:
                $code_str = "Invalid Token";
                break;
            case 403:
                $code_str = "Forbidden";
                break;
            case 404:
                $code_str = "Not Found";
                break;
            case 409:
                $code_str = "Conflict";
                break;
            case 500:
                $code_str = "Internal Server Error";
                break;
            case 501:
                $code_str = "";
                break;
        }
        $_SERVER['RETURN_CODE'] = "HTTP/1.1 " . $code . " " . $code_str;
        // HTTP头要先于BODY先发送
        header("HTTP/1.1 " . $code . " " . $code_str);
        if ($message) {
            header("Content-Type: application/json");
            if ($code == 501) {
                //2020-03-23 Mark 2.9.7 兼容强制报错501
                echo $message;
            } elseif (is_array($message)) {
                echo json_encode($message, JSON_UNESCAPED_UNICODE);
            } else {
                $data = ['error_message' => $message];
                if ($extend_data) {
                    // 附加在后面，如果有重复的字段，以前面为准
                    $data += $extend_data;
                }
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    /**
     * Response Success Header
     * @param int $code 状态码
     * @param string $code_str msg提示
     * @param array|string $body 返回数据
     * @param bool $original_output 在$body为空的时候是否改变返回数据的结构
     */
    static public function response_success_header($code, $code_str = "", $body = array(), $original_output = false)
    {
        switch ($code) {
            case 200:
                $code_str = 'OK';
                break;
            case 201:
                if ($code_str == "") {
                    $code_str = 'Created';
                }
                break;
            case 204:
                $code_str = 'No Content';
                break;
        }

        $_SERVER['RETURN_CODE'] = "HTTP/1.1 " . $code . " " . $code_str;
        header("HTTP/1.1 " . $code . " " . $code_str);
        if ($code == 204) {
            header_remove("Content-Type");
        } else {
            header("Content-Type: application/json");
            if ($body) {
                echo is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : $body;
            } elseif ($original_output == true) {
                echo json_encode($body, JSON_UNESCAPED_UNICODE);
            } else {
                echo '{}';
            }
        }
        exit;
    }

    static public function getController()
    {
        $str        = request()->route()->getActionName();
        $num        = strripos($str, "\\") + 1;
        $str2       = substr($str, $num);
        $num2       = strpos($str2, '@');
        $controller = substr($str2, 0, $num2 - 10);
        return $controller;
    }


    //生成唯一id
    static public function guid()
    {
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid   = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $uuid;
        }
    }

    static function numToWord($num)
    {
        $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $chiUni = array('', '十', '百', '千', '万', '亿', '十', '百', '千');

        $chiStr = '';

        $num_str = (string)$num;

        $count     = strlen($num_str);
        $last_flag = true; //上一个 是否为0
        $zero_flag = true; //是否第一个
        $temp_num  = null; //临时数字

        $chiStr = '';//拼接结果
        if ($count == 2) {//两位数
            $temp_num = $num_str[0];
            $chiStr   = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num] . $chiUni[1];
            $temp_num = $num_str[1];
            $chiStr   .= $temp_num == 0 ? '' : $chiNum[$temp_num];
        } else if ($count > 2) {
            $index = 0;
            for ($i = $count - 1; $i >= 0; $i--) {
                $temp_num = $num_str[$i];
                if ($temp_num == 0) {
                    if (!$zero_flag && !$last_flag) {
                        $chiStr    = $chiNum[$temp_num] . $chiStr;
                        $last_flag = true;
                    }
                } else {
                    $chiStr = $chiNum[$temp_num] . $chiUni[$index % 9] . $chiStr;

                    $zero_flag = false;
                    $last_flag = false;
                }
                $index++;
            }
        } else {
            $chiStr = $chiNum[$num_str[0]];
        }
        return $chiStr;
    }

    /**
     * 格式化输出
     *
     * @param $msg
     * @param bool $show_date 是否显示日期
     * @param float $start_ms_time 开始时间-毫秒
     */
    public static function format_output($msg, $show_date = true, $start_ms_time = null)
    {
        static $LF = '';
        if ($LF === '') {
            $LF = '<br />';
            // 是CLI模式
            if (\Common::is_cli()) {
                $LF = PHP_EOL;
            }
        }
        if ($show_date) {

            //计算耗时
            if ($start_ms_time) {
                $elapsed_time = ' elapsed: ' . bcsub(microtime(true), $start_ms_time, 4);
            } else {
                $elapsed_time = '';
            }

            echo "[" . date("Y-m-d H:i:s") . $elapsed_time . "] ";
        }


        echo $msg . $LF;
    }

    /**
     * 是否是CLI模式
     *
     * @return bool
     */
    public static function is_cli()
    {
        return (PHP_SAPI === 'cli' or defined('STDIN'));
    }

    /**
     * 获取唯一Id
     * @return string
     */
    public static function get_unique_id()
    {
        return md5(microtime(true) . ':' . mt_rand(1, 1000000) . ':' . mt_rand(1, 1000000));
    }

    /**
     * 验证ipv4地址是否有效
     *
     * @param $ip
     *
     * @return bool
     */
    public static function verify_ipv4_is_valid($ip)
    {
        return preg_match('/^(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d?)(?:\.(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/', $ip) ? true : false;
    }

    /**
     * 比较日期相差的天数
     *
     * @param string $date_1
     * @param string $date_2
     * @param string $difference_format
     * @return string
     */
    public static function date_difference($date_1, $date_2, $difference_format = '%a')
    {
        $datetime1 = new \DateTime($date_1);
        $datetime2 = new \DateTime($date_2);
        $interval  = $datetime1->diff($datetime2);
        return $interval->format($difference_format);
    }

    /**
     * 验证日期是否有效
     *
     * @param $date_time
     *
     * @return bool false = 无效
     */
    public static function verify_date_time_valid($date_time): bool
    {
        return (($date_time = strtotime($date_time)) !== false && $date_time > -28800);
    }

    /**
     * 排序二维数组
     * @param array $arr //要排序的目标数组
     * @param $sort_index //排序的依据index
     * @param $second_index //二级排序索引
     * @param string $sort_type //asc => 从小到大排序 desc => 从大到小排序
     * @return array
     */
    public static function sort_array(array $arr, $sort_index, $sort_type = 'asc', $second_index = '')
    {
        if (count($arr) <= 1) {
            return $arr;
        }
        //取中间数
        $temp  = array_pop($arr);
        $left  = [];
        $right = [];
        foreach ($arr as $value) {
            if ($sort_type == 'asc') {
                //从小到大排序
                if ($value[$sort_index] < $temp[$sort_index]) {
                    $left[] = $value;
                } elseif ($value[$sort_index] == $temp[$sort_index]) {
                    if ($second_index && $value[$second_index] < $temp[$second_index]) {
                        $left[] = $value;
                    } else {
                        $right[] = $value;
                    }
                } else {
                    $right[] = $value;
                }
            } else {
                //从大到小排序
                if ($value[$sort_index] > $temp[$sort_index]) {
                    $left[] = $value;
                } elseif ($value[$sort_index] == $temp[$sort_index]) {
                    if ($second_index && $value[$second_index] > $temp[$second_index]) {
                        $left[] = $value;
                    } else {
                        $right[] = $value;
                    }
                } else {
                    $right[] = $value;
                }
            }
        }
        $left  = self::sort_array($left, $sort_index, $sort_type, $second_index);
        $right = self::sort_array($right, $sort_index, $sort_type, $second_index);
        return array_merge($left, [$temp], $right);
    }

    /**
     * 生成指定位数随机数，位数不够前补零
     *
     * @param int $len
     *
     * @return int|string
     */
    public static function generate_random_number($len = 4)
    {
        $mt_rand = mt_rand(0, intval(9 . str_repeat(9, $len - 1)));
        $str_len = strlen($mt_rand);
        if ($str_len < $len) {
            $mt_rand = str_repeat('0', $len - $str_len) . $mt_rand;
        }
        return $mt_rand;
    }

    /**
     * 整数型金额展现时转换成浮点型
     * @param int $int_val
     * @param int $scale 精度值，默认2位
     *
     * @return false|float
     */
    public static function amount_int_to_float($int_val, $scale = 2)
    {
        return floatval(bcdiv($int_val, 100, $scale));
    }

    /**
     * configExt
     * @param array $array
     * @return mixed
     */

    /**
     * 浮点型金额存储时转换成整数型
     * @param $float_val
     *
     * @return int
     */
    public static function amount_float_to_int($float_val)
    {
        return intval(bcmul($float_val, 100));
    }

    /**
     * 浮点型数值保留精确小数-不四舍五入
     * @param $float_val
     * @param int $scale 保留小数位数 默认2位
     * @return float
     */
    public static function amount_accurate_keep_decimals($float_val, $scale = 2)
    {
        $_p = pow(10, $scale); //得到10的n次方
        return floatval(bcdiv(bcmul($float_val, $_p), $_p, $scale));
    }

    /**
     * 检查数组内是否包含key
     *
     * @param array $check_array 被检测数组
     * @param array $check_field 检测的key
     *
     * @return bool
     */
    public static function check_array_key(&$check_array, $check_field)
    {
        $return      = true;
        $tmp_data    = $check_array;
        $check_array = [];
        foreach ($check_field as $value) {
            $return = array_key_exists($value, $tmp_data);
            if (!$return) {//检测到不包含 返回
                return $return;
            }
            $check_array[$value] = $tmp_data[$value];
        }
        return $return;//全部包含  返回
    }

    /**
     * 生成随机订单号 订单编号：类型(2)+时间戳(11)+毫秒数(3)+随机数(4)
     * @param string $type 'ST' => 实体商品订单  'XX' => 实体商品订单
     * @return string
     */
    public static function generate_order_sn($type = 'ST')
    {
        return $type . intval(microtime(true) * 1000) . mt_rand(1000, 9999);
    }

    /**
     * 生成充值(order_pay)随机单号
     * @param $order_id
     * @param string $type 支付单类型，第三方查订单时使用，只要4位超过截取
     * app - app
     * wap - 外链
     * img - 写真集
     * cmp - 点赞
     * bak - 后台手动给卡
     * bpay - 后台充值
     * @param int $len 长度
     * @return string
     */
    public static function generate_recharge_order_sn($order_id = 0, $type = 'app', $len = 30)
    {
        $time   = intval(microtime(TRUE) * 1000); //毫秒时间戳
        $suffix = 'L' . substr($type, 0, 4); //后缀参数 有些不允许用特殊所以使用L分割
        $len    = $len - strlen($suffix) - strlen($time);

        return $time . substr(md5($time . mt_rand(10000, 99999) . $order_id), 4, $len) . $suffix;
    }

    /**
     * 转换json img对象
     * @param $json_str
     * @return array|null
     */
    public static function conversion_json_img($json_str)
    {
        $res = json_decode($json_str, true);
        if (!$res || !isset($res['url'])) {
            return null;
        }
        return ['url' => $res['url'], 'height' => isset($res['height']) ? intval($res['height']) : 50, 'width' => isset($res['width']) ? intval($res['width']) : 200];
    }

    /**
     * 超过长度截取中间字符替换为$etc
     *
     * @param $string
     * @param int $limitLen
     * @param string $charset
     * @param bool $containerEtcLen 是否包含etc长度
     * @param string $etc
     * @return string
     */
    public static function cut_middle_str($string, $limitLen = 100, $containerEtcLen = false, $etc = '...', $charset = 'utf-8')
    {
        $strLen  = mb_strlen($string, $charset);
        $etcLen  = $etc !== '...' ? mb_strlen($etc, $charset) : 3;
        $boolean = $strLen > ($containerEtcLen ? ($limitLen + $etcLen) : $limitLen);
        // 如果超出了限制长度，从字符串中间截取一定的内容，满足长度限制
        if ($boolean) {
            // 计算出截取的开始位置
            $leftEnd = floor($limitLen / 2);

            // 起始位置大于等于0，符合截取条件
            if ($leftEnd >= 0) {
                $exceedLen = 0;
                if ($containerEtcLen) {
                    $leftEnd   -= floor($etcLen / 2);
                    $exceedLen += $etcLen;
                }
                // 需要截取的长度右边开始位置
                $exceedLen   += $strLen - $limitLen + $leftEnd;
                $stringLeft  = mb_substr($string, 0, $leftEnd, $charset);
                $stringRight = mb_substr($string, $exceedLen, $strLen, $charset);
                $string      = $stringLeft . $etc . $stringRight;
            }
        }
        return $string;
    }

    /**
     * 字符串截取
     *
     * @param string $string
     * @param int $limit
     * @param string $etc
     * @param string $charset
     *
     * @return string
     */
    public static function cut_substr($string, $limit, $etc = '...', $charset = 'utf-8')
    {
        if (mb_strlen($string, $charset) > $limit) {
            $string = mb_substr($string, 0, $limit, $charset) . $etc;
        }
        return $string;
    }

    /**
     * 手机号隐藏
     * @date 2021/9/18
     * @param int |string |array $value
     * @param int $start 替换开始位置
     * @param int $limit 替换长度
     * @param string $replacement
     * @return string|string[]
     */
    public static function mobile_hide($value, $start = 3, $limit = 4, $replacement = '****')
    {
        return substr_replace($value, $replacement, $start, $limit);
    }

    /**
     * 获取操作历史备注
     * @param $template //模板
     * @param $type_index //模板编号
     * @param array $variables
     *
     * @return string
     */
    public static function get_operate_history_note(array $template, $type_index, array $variables = []): string
    {
        if ($variables) {
            return sprintf($template[$type_index], ...$variables);
        }
        return $template[$type_index];
    }

    public static function env_test()
    {
        return PHP_ENV === 'test';
    }

    /**
     * 生成抽奖随机数组
     * @param int $num 生成的数量
     * @param int $min 最小值
     * @param int $max 最大值
     * @param int $piecewise 分段参数 数据会根据此值划分区间
     * @return array|null
     */
    public static function generate_unique_rand($num = 1, $min = 1, $max = 100, $piecewise = 2)
    {
        $return_arr = [];
        $val        = 1;

        //更改中奖几率
//        $min = 400;
//        $max = 100000;
        //礼物放到最后一个
//        $return_arr[$max] = 9999;

        switch (true) {
            //no break
            case ($max - $min) < $piecewise: //区间值不足以平均拆分
            case ($max - $min) < $num: //区间值不足以取到需求总量
            case $max < $min: //最大值小于最小值
                return [];
                break;
            default:
        }

        //容错平均区间要比总数量小
        if ($piecewise > $num) {
            $piecewise = $num;
        }

        //每一段数据的区间值
        $interval = intval(bcdiv(($max - $min), $piecewise));

        //每一段区间生成的随机个数，并向上取整(使得数量平均在前面，并且不会超过最大值)
        $segments_num = ceil(bcdiv($num, $piecewise, 2));

        //单个区间最大值
        $_max = $interval + $min;

        //计算区间值
        for ($i = 1; $i <= $piecewise; $i++) {

            $_min = $min;

            //不是第一次循环的时候+1 避免最小值是上一次的最大值
            if ($i != 1) $_min = $_min + 1;

            $_arr = [];
            //每次生成数量
            while (count($_arr) < $segments_num) {

                //数量满足跳出循环
                if (count($return_arr) >= $num) {
                    break;
                }

                $rand_n = mt_rand($_min, $_max);

                //用于单次区间值循环排重
                $_arr[$rand_n]       = $val++;
                $return_arr[$rand_n] = $val;
            }

            $min  = $min + $interval;
            $_max = $_max + $interval;
        }

        return array_keys($return_arr);
    }

    /**
     * 取分钟级时间戳
     *
     * @param int $unix_timestamp
     *
     * @return int
     */
    public static function get_minute_by_timestamp(int $unix_timestamp): int
    {
        return intval($unix_timestamp / 60) * 60;
    }

    /**
     * 过滤表情emoJi 支持数组批量
     * @param string | array $emo_data
     * @return string
     */
    public static function emoJi_filter_empty($emo_data)
    {
        $pattern = "/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}/";
        if (is_array($emo_data)) {
            $_string = json_encode($emo_data);
            $_string = preg_replace($pattern, "", $_string);
            $data    = json_decode($_string, true);
        } else {
            $data = preg_replace($pattern, "", $emo_data);
        }
        return $data;
    }

    /**
     * 获取两个数据的百分比
     * @param int|float $left_operand 除数
     * @param int|float $right_operand 被除数
     * @param bool $is_symbol 是否以带%格式返回
     * @return string
     */
    public static function get_calculate_proportion($left_operand, $right_operand, $is_symbol = true)
    {
        $value = 0;

        if ($right_operand > 0) {
            $value = bcdiv($left_operand, $right_operand, 2) * 100;
        }
        if ($is_symbol) {
            return $value . "%";
        }
        return $value;
    }

    /**
     * 红包随机分配金额递减算法
     * @param int $total 待分配总值
     * @param int $num 人数
     * @param int $proportion 平均值浮动倍数
     * @param int $minimum 保底礼券
     * @return array
     */
    public static function generate_random_rand($total = 100, $num = 10, $proportion = 2, $minimum = 1)
    {
        $total_minimum = $num * $minimum; //保底总额
        $_total        = $total - $total_minimum;

        $arr      = [];
        $rand_min = 0;

        //计算区间值
        for ($i = 0; $i < $num; $i++) {

            //总值除人数再乘倍数后取整
            $rand_max = intval(bcmul(bcdiv($_total, ($num - $i), 2), $proportion));

            $reduce = $rand_max > $rand_min ? mt_rand($rand_min, $rand_max) : 0;

            if (($i + 1) == $num) {
                $reduce = $_total;
            }

            $arr[] = $reduce + $minimum;

            $_total -= $reduce;
        }

        shuffle($arr); //可打乱顺序
        return $arr;
    }

    /**
     * 红包随机分配平均值浮动取值方法 最小单位1
     * 生成规则：每个人只能领取人均红包上下10%以内的金额。例如1000块10个人，平均每个人100块，则每个人分配的金额在90-110之间。
     * @date 2021/9/27
     * @param int $total 总值
     * @param int $num 分配数量
     * @param float $float_ratio 浮动比例小数值 0 到 1 之间 0.1代表10%
     * @return array
     */
    public static function generate_red_envelope($total, $num, $float_ratio = 0.1)
    {
        if ($float_ratio < 0 || $float_ratio > 1) {
            return [];
        }

        //计算余数，避免分配的时候有小数，这个数直接分配给第一个人
        $remainder = $total % $num;
        $total     = $total - $remainder;

        //生成数组
        $var_arr = array_pad([], $num, bcdiv($total, $num));

        $distribution = $total * $float_ratio; //待分配

        $change   = 0; //变动的值
        $rand_min = 0;
        $rand_max = $distribution / $num;

        //使用此方法确保循环的数组只循环到双数结尾，如有9个数据只循环到第8个
        $_num = intval($num / 2) * 2;

        //分配
        for ($i = 0; $i < $_num; $i++) {
            $reduce = array_shift($var_arr);
            if ($i % 2 == 0) {
                $change = mt_rand($rand_min, $rand_max);
                $val    = bcsub($reduce, $change);
            } else {
                $val = bcadd($reduce, $change);
            }

            array_push($var_arr, $val);
        }

        if ($remainder) {
            $var_arr[0] = $var_arr[0] + $remainder;
        }

        shuffle($var_arr); //可打乱顺序
        return $var_arr;
    }

    /**
     * 根据生日获取年龄
     *
     * @param $birthday
     *
     * @return int|string
     */
    public static function cal_age_by_birthday($birthday)
    {
        if (!$birthday || $birthday <= '1900-01-01' || $birthday > date('Y-m-d')) return '';
        $birthdayTime = strtotime($birthday);
        return (date('Y') - date('Y', $birthdayTime) - (date('m-d') < date('m-d', $birthdayTime) ? 1 : 0));
    }

    /**
     * 根据生日获取星座
     *
     * @param $birthday
     *
     * @return mixed|string
     */
    public static function get_zodiac_sign($birthday)
    {
        if (!$birthday || $birthday == '0000-00-00') {
            return '';
        }
        // 检查参数有效性
        $month = intval(date('m', strtotime($birthday)));
        $day   = intval(date('d', strtotime($birthday)));

        if (!$month || !$day) {
            return '';
        }

        // 星座名称以及开始日期
        $signs = array(
            array("20" => "水瓶座"),
            array("19" => "双鱼座"),
            array("21" => "白羊座"),
            array("20" => "金牛座"),
            array("21" => "双子座"),
            array("22" => "巨蟹座"),
            array("23" => "狮子座"),
            array("23" => "处女座"),
            array("23" => "天秤座"),
            array("24" => "天蝎座"),
            array("22" => "射手座"),
            array("22" => "摩羯座")
        );
        // 7.2 遗弃each
//        list($sign_start, $sign_name) = each($signs[(int)$month-1]);
//        if ($day < $sign_start)
//            list($sign_start, $sign_name) = each($signs[($month -2 < 0) ? $month = 11: $month -= 2]);
//        return $sign_name;
        $sign      = $signs[(int)$month - 1];
        $signStart = key($sign);
        if ($day < $signStart) {
            $sign = $signs[($month - 2 < 0) ? 11 : $month - 2];
        }
        return current($sign);
    }

    /**
     * 生成随机字符
     * @param $len
     * @return bool|string
     */
    public static function get_random_str($len)
    {
        $str  = '';
        $data = 'qsxazwevcfrdtgnbhyuijmkplo912348657';
        $len1 = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $fontcontent = substr($data, rand(0, $len1 - 1), 1);
            $str         .= $fontcontent;
        }
        return $str;
    }

    public static function JSON($result)
    {
        $array = $result;
        self::arrayRecursive($array);//先将类型为字符串的数据进行 urlencode
        $json = json_encode($array);//再将数组转成JSON
        return urldecode($json);//最后将JSON字符串进行urldecode
    }

    public static function arrayRecursive(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::arrayRecursive($array[$key]);//如果是数组就进行递归操作
            } else {
                if (is_string($value)) {
                    $temp1       = addslashes($value);
                    $array[$key] = urlencode($temp1);//如果是字符串就urlencode
                } else {
                    $array[$key] = $value;
                }
            }
        }
    }

    public static function time_diff_days($date1, $date2)
    {
        $diff_info = date_diff(date_create($date2), date_create($date1));
        if ($diff_info->invert) {
            return -1 * $diff_info->days;
        } else {
            return $diff_info->days;
        }
    }

    /**
     * 验证长度是否有效
     * 2个英文字母算1个汉字; 最多8个汉字
     *
     * @param $str
     * @param int $limitLen 限定长度
     * @param bool $support_space 是否支持空格
     * @param bool $support_number 是否支持数字
     *
     * @return bool
     */
    public static function validate_str_len($str, $limitLen = 8, $support_space = false, $support_number = false)
    {
        // 最大长度，避免过多字符到下一步计算长度里面
        $maxLen         = $limitLen * 2;
        $attach_pattern = $support_number ? '\w' : 'a-zA-Z';
        $attach_pattern .= $support_space ? ' ' : ''; // 是否支持空格-普通的
        $attach_pattern .= $support_space ? ' ' : ''; // 是否支持空格-ios空格
        $bool           = preg_match('/^[\x{4e00}-\x{9fa5}' . $attach_pattern . ']{1,' . $maxLen . '}$/u', $str);
        if ($bool && (self::cal_str_len($str) / 2) <= $limitLen) {
            return true;
        }
        return false;
    }

    /**
     * 计算字符长度
     * 中文算2个字符 单字节算1个字符 ASCII(1-126)
     * @param $str
     *
     * @return int
     */
    public static function cal_str_len($str)
    {
        $len = 0;
        // 把字符分隔成单个字符的数组
        foreach (preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY) as $s) {
            $c = ord($s);
            // 单字节+1
            // 0xff60-0xff9f是日本片假名字符集 0x0001-0x007e(1~126) ASCII码
            if (($c >= 0x0001 && $c <= 0x007e) /*|| ($c >= 0xff60 && $c <= 0xff9f)*/) {
                $len++;
            } else {
                $len += 2;
            }
        }
        return $len;
    }

    /**
     * 验证生日
     *
     * @param $birthday
     *
     * @return bool
     */
    public static function validate_birthday($birthday)
    {
        return date_parse($birthday) !== false && $birthday > '1900-01-01';
    }

    /**
     * 获取选性别时分配的默认昵称
     *
     * @param string $number 世界号
     * @param int $user_id 用户id
     *
     * @return string
     */
    public static function get_default_nickname($number, $user_id = 0)
    {
        // 如果要改此前缀，记得变更FriendController::indexAction处的分组符号
        $name = '新用户';
        if ($number) {
            $name .= substr($number, -4, 4);
        } else {
            $suffix = substr($user_id, -4, 4);
            if (mb_strlen($suffix) < 4) {
                $suffix = str_pad($suffix, 4, '0', STR_PAD_LEFT);
            }
            $name .= $suffix;
        }
        return $name;
    }

    /**
     * 获取服务器当时时间戳毫秒
     * @date 2021/7/7
     * @return string
     */
    public static function get_micro_time()
    {
        return intval(microtime(TRUE) * 1000);
    }

    /**
     * 毫秒转秒级
     *
     * @param int $micro_time 毫秒
     *
     * @return int
     */
    public static function micro_time_2_time($micro_time)
    {
        return intval($micro_time / 1000);
    }

    /**
     * 获取token
     */
    public static function get_token()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) {
            $a = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
            if (isset($a[1]) && $a[1]) {
                $token = $a[1];
                return \Common::format_return_result(StatusConstants::SUCCESS, 'Success', $token);
            } else {
                return \Common::format_return_result(StatusConstants::ERROR_FORBIDDEN, 'Token Verify Error');
            }
        }
        return \Common::format_return_result(StatusConstants::ERROR_FORBIDDEN, 'Token Verify Error');
    }

    /**
     * 格式化返回结果
     *
     * @param int $status 状态码 0=成功(表示0个错误) 0以上都是错误，不允许使用负数
     * @param string $msg 提示信息
     * @param mixed $data 数据
     * @param array $extend_data 扩展数组
     *
     * @return array
     */
    public static function format_return_result(int $status = \library\Constants\StatusConstants::SUCCESS, string $msg = '', $data = [], array $extend_data = []): array
    {
        //如果为空则使用全局msg提示
        if ($msg == '') {
            $msg = \library\Constants\StatusConstants::ERROR_TO_MSG_COPY[$status] ?? '';
        }

        $result              = ['status' => $status, 'data' => $data, 'msg' => $msg, 'code' => 200];
        $status_to_code_maps = \library\Constants\StatusConstants::STATUS_TO_CODE_MAPS;
        // Status转Code
        if (isset($status_to_code_maps[$status])) {
            $result['code'] = $status_to_code_maps[$status];
        }
        if ($extend_data && is_array($result['data'])) {
            $result['data'] = array_merge($result['data'], $extend_data);
        }
        return $result;
    }

    /**
     * 清空文件夹函数和清空文件夹后删除空文件夹函数的处理
     *
     * @param string $path
     *
     * @return bool
     */
    public static function del_dir(string $path)
    {
        $path = rtrim($path, '/') . '/';
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            if ($p) {
                foreach ($p as $val) {
                    //排除目录中的.和..
                    if ($val != '.' && $val != '..') {
                        //如果是目录则递归子目录，继续操作
                        if (is_dir($path . $val)) {
                            //子目录中操作删除文件夹和文件
                            self::del_dir($path . $val);
                            //目录清空后删除空文件夹
                            @rmdir($path . $val . '/');
                        } else {
                            //如果是文件直接删除
                            unlink($path . $val);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取随机手机号，且唯一
     *
     * @param int $number 用户世界号
     *
     * @return string
     */
    public static function get_random_mobile($number)
    {
        // 1 + 10
        return 1 . str_pad($number, 10, 0, STR_PAD_LEFT);
    }

    /**
     * 生成token
     *
     * @param $user_id
     *
     * @return string
     */
    public static function gen_token($user_id): string
    {
        return md5(microtime(true) . mt_rand(0, 99999999) . $user_id);
    }

    /**
     * 把medoo update参数数组key先处理一下，去除特殊符号
     * 注意：如果medoo升级，此处正则也要跟着medoo的正则更新
     * @param array $data
     *
     * @return array
     * @see Medoo::update
     *
     */
    public static function process_medoo_update_data(array $data)
    {
        $new_data = [];
        foreach ($data as $k => $v) {
            $k            = preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $k);
            $new_data[$k] = $v;
        }
        return $new_data;
    }

    /**
     * 生成after_callback_hook参数
     *
     * 本来应该定义在BaseModel中，但是复制起来太麻烦，就放公共类库里面了
     *
     * @param callable $callable 被调用的回调函数 array(类名, "需要被回调的方法名")
     *
     * 方法命名规则
     * 插入时钩子命名（after_hook_insert_表名）例：after_hook_insert_user_info
     * 更新时钩子命名（after_hook_update_表名）例：after_hook_update_user_info
     * @param array $params
     *
     * @return array
     */
    public static function gen_callback_params(callable $callable, array $params = []): array
    {
        return ['callback' => $callable, 'params' => $params];
    }

    /**
     * 生成签名
     *
     * @param array|string $data
     * @param string $sign_key
     *
     * @return string
     */
    public static function gen_sign($data, $sign_key)
    {
        $sign = '';
        if (is_array($data)) {
            ksort($data);
            $items = array();
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    ksort($value); // 因为golang json解析会默认按字母升序
                    $value = json_encode($value);
                }
                $items[] = $key . "=" . $value;
            }
            $sign = md5(implode("&", $items) . $sign_key);
        } else if (is_string($data)) {
            $sign = md5($data . $sign_key);
        }
        return $sign;
    }

    /**
     * 获取位图中所有值为1的偏移量
     *
     * 优点: 减少8位中那些为0的位置处理
     * 缺点: 需要额外的空间读取配置
     *
     * @param string $bitmap_str
     *
     * @return array
     */
    public static function get_bitmap_offsets($bitmap_str)
    {
        $data = [];
        if ($bitmap_str) {
            $bitmap = unpack('C*', $bitmap_str);
            unset($bitmap_str);
            // 读取8位255所有为1的位置数组
            if (!isset(self::$_int_bitmap)) {

                if (!file_exists(ROOT_PATH . 'config/bitmap.php')) {
                    throw new Exception('not fond config bitmap');
                }
                self::$_int_bitmap = require ROOT_PATH . 'config/bitmap.php';
            }
            foreach ($bitmap as $k => $number) {
                if ($number) {
                    $offset = ($k - 1) * 8;
                    // 获取数字对应的位置数组
                    foreach (self::$_int_bitmap[$number] as $pos) {
                        $data[] = $offset + $pos;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取某个时间的周一 or 周日日期
     * @date 2021/8/5
     * @param null|int $time
     * @param int $type 1获取周一 2获取周日
     * @return false|string
     */
    public static function get_date_monday($time = null, $type = 1)
    {
        if (!$time) $time = time();

        $date = date('Y-m-d', strtotime('this week', $time));

        if ($type == 2) $date = date('Y-m-d', strtotime('this week +6 days', $time));

        return $date;
    }

    /**
     * 获取vip配置
     * @date 2021/10/19
     * @param null $item
     * @param null $default
     * @return mixed|null
     * @throws Exception
     */
    public static function get_vip_config($item = null, $default = null)
    {
        if (!isset(self::$_vip_config)) {
            //配置
            if (file_exists(CONFIG_PATH . 'vip_config.php')) {
                $vip_config = include(CONFIG_PATH . 'vip_config.php');
            }

            if (empty($vip_config)) {
                throw new \Exception('Not Found vip_config');
            }
            self::$_vip_config = $vip_config;
        }
        if ($item) {
            return self::$_vip_config[$item] ?? $default;
        }
        return self::$_vip_config;
    }

    /**
     * @param $time
     *
     * @return mixed
     */
    public static function chinese_week_day_name($time)
    {
        return self::$_week_day_name_arr['chinese'][date('w', $time)];
    }

    /**
     * @param $time
     *
     * @return mixed
     */
    public static function english_week_day_name($time)
    {
        return self::$_week_day_name_arr['english'][date('w', $time)];
    }

    /**
     * @param $time
     *
     * @return mixed
     */
    public static function chinese_month_name($time)
    {
        return self::$_month_name_arr['chinese'][date('m', $time)];
    }

    /**
     * @param $time
     *
     * @return mixed
     */
    public static function english_month_name($time)
    {
        return self::$_month_name_arr['english'][date('m', $time)];
    }

    /**
     * 是否是关联数组
     * @param array $array
     *
     * @return bool
     */
    public static function is_assoc(array $array)
    {
        return $array && array_keys($array)[0] !== 0;
    }

    /**
     * 敏感信息加密
     *
     * @param array $content_arr
     * @param Encryption|null $encrypt
     *
     * @return mixed
     */
    public static function user_encryption(array $content_arr, Encryption $encrypt = null)
    {
        if (!$encrypt) {
            $encrypt = self::get_encrypt_object();
        }
        foreach ($content_arr as $k => &$v) {
            if ($v) {
                $v = $encrypt->aes128cbcEncrypt($v);
            }
        }
        return $content_arr;
    }

    /**
     * 获取敏感信息加密对象
     *
     * @param null|string $key 配置里的key名
     * @param null|string $iv 配置里的key名
     *
     * @return Encryption
     */
    public static function get_encrypt_object($key = null, $iv = null)
    {
        $key = $key ?: 'encryption_key';
        $iv  = $iv ?: 'encryption_iv';
        return Encryption::instance(self::get_config($key), self::get_config($iv));
    }

    /**
     * 获取配置, 写成静态方法方便全局调用
     *
     * @param string $item
     * @param mixed $default 默认值
     * @param string $module_name
     *
     * @return mixed
     */
    public static function get_config($item = null, $default = null, $module_name = 'default')
    {
        if (!isset(self::$_module_configs[$module_name])) {
            //配置
            $config = self::fromFile(CONFIG_PATH . 'my_config.ini');
            if (file_exists(CONFIG_PATH . 'config.php')) {
                // 追加配置，以my_config.ini为准
                $config += include(CONFIG_PATH . 'config.php');
            }
            // 保证只会有一份配置文件，而不是每初始化一个类，都复制一份; 如Model中的$this->my_config
            self::$_module_configs[$module_name] = self::ConfigExt($config);
        }
        if ($item) {
            return self::$_module_configs[$module_name][$item] ?? $default;
        }
        return self::$_module_configs[$module_name];
    }

    /**
     * fromFile(): defined by Reader interface.
     *
     * @param string $filename
     * @return array
     * @throws Exception\RuntimeException
     * @see    ReaderInterface::fromFile()
     */
    public static function fromFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new Error(sprintf(
                "File '%s' doesn't exist or not readable ", $filename
            ));

        }

        self::$directory = dirname($filename);

        set_error_handler(
            function ($error, $message = '') use ($filename) {
                throw new Error(sprintf('Error reading INI file "%s": %s', $filename, $message)
                );
            },
            E_WARNING
        );
        $ini = parse_ini_file($filename, true);
        restore_error_handler();

        return self::process($ini);
    }

    /**
     * Process data from the parsed ini file.
     *
     * @param array $data
     * @return array
     */
    protected static function process(array $data)
    {
        $config = [];

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                if (strpos($section, self::$nestSeparator) !== false) {
                    $sections = explode(self::$nestSeparator, $section);
                    $config   = array_merge_recursive($config, self::buildNestedSection($sections, $value));
                } else {
                    $config[$section] = self::processSection($value);
                }
            } else {
                self::processKey($section, $value, $config);
            }
        }

        return $config;
    }

    /**
     * Process a nested section
     *
     * @param array $sections
     * @param mixed $value
     * @return array
     */
    private static function buildNestedSection($sections, $value)
    {
        if (count($sections) == 0) {
            return self::processSection($value);
        }

        $nestedSection = [];

        $first                 = array_shift($sections);
        $nestedSection[$first] = self::buildNestedSection($sections, $value);

        return $nestedSection;
    }

    /**
     * Process a section.
     *
     * @param array $section
     * @return array
     */
    protected static function processSection(array $section)
    {
        $config = [];

        foreach ($section as $key => $value) {
            self::processKey($key, $value, $config);
        }

        return $config;
    }

    /**
     * Process a key.
     *
     * @param string $key
     * @param string $value
     * @param array $config
     * @return array
     * @throws Exception\RuntimeException
     */
    protected static function processKey($key, $value, array &$config)
    {
        if (strpos($key, self::$nestSeparator) !== false) {
            $pieces = explode(self::$nestSeparator, $key, 2);

            if (!strlen($pieces[0]) || !strlen($pieces[1])) {
                throw new Exception\RuntimeException(sprintf('Invalid key "%s"', $key));
            } elseif (!isset($config[$pieces[0]])) {
                if ($pieces[0] === '0' && !empty($config)) {
                    $config = [$pieces[0] => $config];
                } else {
                    $config[$pieces[0]] = [];
                }
            } elseif (!is_array($config[$pieces[0]])) {
                throw new Exception\RuntimeException(
                    sprintf('Cannot create sub-key for "%s", as key already exists', $pieces[0])
                );
            }

            self::processKey($pieces[1], $value, $config[$pieces[0]]);
        } else {
            if ($key === '@include') {
                if (self::$directory === null) {
                    throw new Exception\RuntimeException('Cannot process @include statement for a string config');
                }

                $include = self::fromFile(self::directory . '/' . $value);
                $config  = array_replace_recursive($config, $include);
            } else {
                $config[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public static function configExt(array $array, $allowModifications = false): array
    {
        $data = [];
        foreach ($array as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * 敏感信息解密
     *
     * @param array $content_arr
     * @param Encryption|null $encrypt
     *
     * @return array
     */
    public static function user_decrypt(array $content_arr, Encryption $encrypt = null)
    {
        if (!$encrypt) {
            $encrypt = self::get_encrypt_object();
        }
        foreach ($content_arr as $k => &$v) {
            if ($v) {
                $v = $encrypt->aes128cbcHexDecrypt($v);
            }
        }
        return $content_arr;
    }

    /**
     * 网页直播相关敏感信息解密
     *
     * @param array $content_arr
     * @param Encryption|null $encrypt
     *
     * @return array
     */
    public static function web_live_user_decrypt(array $content_arr, Encryption $encrypt = null)
    {
        if (!$encrypt) {
            $encrypt = self::get_web_live_encrypt_object();
        }
        foreach ($content_arr as $k => &$v) {
            if ($v) {
                $v = $encrypt->aes128cbcHexDecryptWebLive($v);
            }
        }
        return $content_arr;
    }

    /**
     * 判断是否是线上环境
     * @date 2021/12/28
     * @return bool
     */
    public static function env_prod()
    {
        return PHP_ENV == 'prod';
    }

    /**
     * 获取两个日期直接的所有日期
     * @date 2022/1/27
     * @param string $start_date 开始日期 "xxxx-xx-xx"
     * @param string $end_date 结束日期
     * @param bool $res_time_stamp true=返回时间戳格式
     * @return array
     */
    public static function get_start_end_all_date($start_date, $end_date, $res_time_stamp = false)
    {

        $dt_start = strtotime(date('Y-m-d', strtotime($start_date)));
        $dt_end   = strtotime(date('Y-m-d', strtotime($end_date)));

        $day[] = $res_time_stamp === false ? date('Y-m-d', $dt_start) : $dt_start;

        while ($dt_start < $dt_end) {
            $dt_start = strtotime('+1 day', $dt_start);

            $day[] = $res_time_stamp === false ? date('Y-m-d', $dt_start) : $dt_start;
        }
        return $day;
    }

    /**
     * 将秒转化为：时分秒
     * @param $seconds
     * @return string
     */
    public static function change_time_type($seconds)
    {
        if ($seconds > 3600) {
            $hours = intval($seconds / 3600);
            $time  = $hours . ":" . gmstrftime('%M:%S', $seconds);
        } else {
            $time = gmstrftime('%H:%M:%S', $seconds);
        }
        return $time;
    }

    /**
     * 数组转换
     * @param object $object
     * @return array
     */
    public static function laravel_to_array($object)
    {
        if ($object) {
            return $object->toArray();
        }
        return [];
    }


    /**
     * 获取参数
     * 2022/12/31
     * @param $is_original_data
     * @return array|null
     */
    public static function getBodyParams($is_original_data = false)
    {
        //兼容已经拿过值了就直接返回
        if (!empty(self::$_dataBody) && $is_original_data == false) {
            return self::$_dataBody;
        }

        $rawContentType = self::getContentType();
        if (($pos = strpos($rawContentType, ';')) !== false) {
            $contentType = strtolower(substr($rawContentType, 0, $pos));
        } else {
            $contentType = strtolower($rawContentType);
        }
        $_bodyParams = [];
        $_bodyParams = array_merge($_bodyParams, $_GET); //路由是在地址里发过来的

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        if ('application/json' === $contentType) {
            $_body       = json_decode(file_get_contents('php://input'), true) ?: [];
            $_bodyParams = array_merge($_bodyParams, $_body);
        } else if ($method === 'POST') {
            $_body       = $_POST ? $_POST : $GLOBALS['HTTP_RAW_POST_DATA'];
            $_bodyParams = array_merge($_bodyParams, $_body ?: []);
        } else if ($method === 'GET') {
            $_bodyParams = array_merge($_bodyParams, $_GET ?: []);
        } else if ($method === 'REQUEST') {
            $_bodyParams = array_merge($_bodyParams, $_REQUEST ?: []);
        } else {
            mb_parse_str(file_get_contents('php://input'), $_bodyParams);
        }

        $original_data = $_bodyParams; //原始数据
        if ($is_original_data == true) {
            return $original_data;
        }

        //过滤特殊字符
        self::_remove_chars($_bodyParams);

        self::$_dataBody = $_bodyParams;

        return $_bodyParams;
    }

    /**
     * 过滤非法字符
     * 2022/11/29
     * @param $data
     * @return void
     */
    public static function _remove_chars(&$data)
    {
        /**
         * \@\. 邮箱
         * \_方法
         * \- 文件id
         * \/\?\&\= 路由地址
         */
        //todo
        $reg = '/[^0-9a-zA-Z\@\.\-\_\/\?\&\=\x{4e00}-\x{9fa5}]/u'; //允许的字符
//        $reg = '/[^\da-zA-Z\@\.\-\_\/\?\&\x{4e00}-\x{9fa5}]/u/'; //允许的字符

        foreach ($data as $k => &$y) {

            if (is_array($y)) {
                //数组解析
                self::_remove_chars($y);
            } else {
                switch ($y) {
                    case (strtotime($y) !== false): //日期格式
                        break;
                    default: //其他格式
                        $y = preg_replace($reg, '', $y);
                }
            }
        }
    }

    /**
     * 获取请求类型
     * @time 2019-06-03 10:55
     * @return |null
     */
    public static function getControllerName()
    {
        $action = \Route::current()->getActionName();
        list($class, $method) = explode('@', $action);
        $class = substr(strrchr($class, '\\'), 1);
        $class = substr($class, 0, 10);

        return $class;
        return ['controller' => $class, 'method' => $method];
    }

    public static function getModelPath()
    {
        $action = \Route::current()->getActionName();
        list($class, $method) = explode('@', $action);
        $class = substr($class, 20);
        $class = str_replace('Controller', '', $class);
        return "App\Models" . $class;
    }

    /**
     * 导出excel csv格式
     * @param string $filename 文件名
     * @param array $tileArray 表头标题列表 格式一维数组 [标题1,标题2,标题3,标题n]
     * @param array $dataArray 数据列表数组 格式二维数组 [[1,2,3,n],[1,2,3,n]]
     */
    public static function export_to_excel($filename='file', $tileArray=[], $dataArray=[]){
        ini_set('memory_limit','512M');
        ini_set('max_execution_time',0);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=".$filename);
        $fp=fopen('php://output','w');
        $bom = chr(0xEF).chr(0xBB).chr(0xBF);
        fwrite($fp, $bom);//转码 防止乱码(比如微信昵称(乱七八糟的))
        if (isset($tileArray[0])) {
            $tileArray[0] = $bom . $tileArray[0];
        }
        fputcsv($fp,$tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if($index==1000){
                $index=0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp,$item);
        }

        ob_flush();
        flush();
        ob_end_clean();
    }

    /**
     * @param $val
     * @param int $return_type 为空时的返回类型 0=null 1='' 2=0
     * @return mixed|null
     */
    public static function check_empty($val,$return_type = 0)
    {
        // 判断是否设置并且是否为空
        if (empty($val)){
            switch ($return_type){
                case 1:
                    return '';
                case 2:
                    return 0;
                default:
                    return null;
            }
        }else{
            return $val;
        }
    }

}
