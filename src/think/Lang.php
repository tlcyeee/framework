<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

class Lang
{
    /**
     * 多语言信息
     * @var array
     */
    private $lang = [];

    /**
     * 当前语言
     * @var string
     */
    private $range = 'zh-cn';

    /**
     * 多语言自动侦测变量名
     * @var string
     */
    protected $langDetectVar = 'lang';

    /**
     * 多语言cookie变量
     * @var string
     */
    protected $langCookieVar = 'think_var';

    /**
     * 允许的多语言列表
     * @var array
     */
    protected $allowLangList = [];

    /**
     * Accept-Language转义为对应语言包名称 系统默认配置
     * @var array
     */
    protected $acceptLanguage = [
        'zh-hans-cn' => 'zh-cn',
    ];

    // 设定当前的语言
    public function setLangSet(string $lang): void
    {
        $this->range = $lang;
    }

    public function getLangSet(): string
    {
        return $this->range;
    }

    /**
     * 设置语言定义(不区分大小写)
     * @access public
     * @param  string|array  $name 语言变量
     * @param  string        $value 语言值
     * @param  string        $range 语言作用域
     * @return mixed
     */
    public function set($name, $value = null, $range = '')
    {
        $range = $range ?: $this->range;
        // 批量定义
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        if (is_array($name)) {
            return $this->lang[$range] = array_change_key_case($name) + $this->lang[$range];
        }

        return $this->lang[$range][strtolower($name)] = $value;
    }

    /**
     * 加载语言定义(不区分大小写)
     * @access public
     * @param  string|array  $file   语言文件
     * @param  string        $range  语言作用域
     * @return array
     */
    public function load($file, $range = ''): array
    {
        $range = $range ?: $this->range;
        if (!isset($this->lang[$range])) {
            $this->lang[$range] = [];
        }

        // 批量定义
        if (is_string($file)) {
            $file = [$file];
        }

        $lang = [];

        foreach ($file as $_file) {
            if (is_file($_file)) {
                // 记录加载信息
                $_lang = include $_file;
                if (is_array($_lang)) {
                    $lang = array_change_key_case($_lang) + $lang;
                }
            }
        }

        if (!empty($lang)) {
            $this->lang[$range] = $lang + $this->lang[$range];
        }

        return $this->lang[$range];
    }

    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param  string|null   $name 语言变量
     * @param  string        $range 语言作用域
     * @return bool
     */
    public function has(string $name, string $range = ''): bool
    {
        $range = $range ?: $this->range;

        return isset($this->lang[$range][strtolower($name)]);
    }

    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param  string|null   $name 语言变量
     * @param  array         $vars 变量替换
     * @param  string        $range 语言作用域
     * @return mixed
     */
    public function get(string $name = null, array $vars = [], string $range = '')
    {
        $range = $range ?: $this->range;

        // 空参数返回所有定义
        if (is_null($name)) {
            return $this->lang[$range] ?? [];
        }

        $key   = strtolower($name);
        $value = $this->lang[$range][$key] ?? $name;

        // 变量解析
        if (!empty($vars) && is_array($vars)) {
            /**
             * Notes:
             * 为了检测的方便，数字索引的判断仅仅是参数数组的第一个元素的key为数字0
             * 数字索引采用的是系统的 sprintf 函数替换，用法请参考 sprintf 函数
             */
            if (key($vars) === 0) {
                // 数字索引解析
                array_unshift($vars, $value);
                $value = call_user_func_array('sprintf', $vars);
            } else {
                // 关联索引解析
                $replace = array_keys($vars);
                foreach ($replace as &$v) {
                    $v = "{:{$v}}";
                }
                $value = str_replace($replace, $vars, $value);
            }
        }

        return $value;
    }

    /**
     * 自动侦测设置获取语言选择
     * @access public
     * @param  Request $request Request对象
     * @return string
     */
    public function detect(Request $request): string
    {
        // 自动侦测设置获取语言选择
        $langSet = '';

        if ($request->get($this->langDetectVar)) {
            // url中设置了语言变量
            $langSet = strtolower($request->get($this->langDetectVar));
        } elseif ($request->cookie($this->langCookieVar)) {
            // Cookie中设置了语言变量
            $langSet = strtolower($request->cookie($this->langCookieVar));
        } elseif ($request->server('HTTP_ACCEPT_LANGUAGE')) {
            // 自动侦测浏览器语言
            preg_match('/^([a-z\d\-]+)/i', $request->server('HTTP_ACCEPT_LANGUAGE'), $matches);
            $langSet = strtolower($matches[1]);
            if (isset($this->acceptLanguage[$langSet])) {
                $langSet = $this->acceptLanguage[$langSet];
            }
        }

        if (empty($this->allowLangList) || in_array($langSet, $this->allowLangList)) {
            // 合法的语言
            $this->range = $langSet ?: $this->range;
        }

        return $this->range;
    }

    /**
     * 设置语言自动侦测的变量
     * @access public
     * @param  string $var 变量名称
     * @return void
     */
    public function setLangDetectVar(string $var): void
    {
        $this->langDetectVar = $var;
    }

    /**
     * 获取语言自动侦测的变量
     * @access public
     * @return string
     */
    public function getLangDetectVar(): string
    {
        return $this->langDetectVar;
    }

    /**
     * 设置语言的cookie保存变量
     * @access public
     * @param  string $var 变量名称
     * @return void
     */
    public function setLangCookieVar(string $var): void
    {
        $this->langCookieVar = $var;
    }

    /**
     * 获取语言的cookie保存变量
     * @access public
     * @return string
     */
    public function getLangCookieVar(): string
    {
        return $this->langCookieVar;
    }

    /**
     * 设置允许的语言列表
     * @access public
     * @param  array $list 语言列表
     * @return void
     */
    public function setAllowLangList(array $list): void
    {
        $this->allowLangList = $list;
    }

    /**
     * 设置转义的语言列表
     * @access public
     * @param  array $list 语言列表
     * @return void
     */
    public function setAcceptLanguage(array $list): void
    {
        $this->acceptLanguage = array_merge($this->acceptLanguage, $list);
    }
}
