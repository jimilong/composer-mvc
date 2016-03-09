<?php 

namespace App\Common;

use App\Common\Exception;
use App\Common\Response;

// 模板继承待完善
class View
{
    //静态变量保存全局实例 --实现单例模式
    private static $_instance = null;

    // 配置信息
    private $recompile    = false, 
            $templatePath = 'app/Views',
            $compilePath  = 'data/compile',
            $suffix       = '.html';
    
    private $theme    = '', // 模板子目录
            $template = ''; // 当前模板
    
    // assign data
    private $assignData   = [],
            $sectionData  = [],
            $sectionStack = [];

    private $patternsLayout = [
                'extends'      => '/\{extends\s+[\'"](.+)[\'"]\s*?\}/i',
                'include'      => '/\{include\s+[\'"](.+)[\'"]\s*?\}/i',
                'yield'        => '/\{yield\s+[\'"]([\w\s]+)[\'"](\s*?,\s*?[\'"](.+)[\'"])?\s*?\}/i',
                'section'      => '/\{section\s+[\'"]([\w\s]+)[\'"]\s*?\}/i',
                'endsection'   => '/\{\/([section|stop|show]+)\s*?\}/i',
                'override'     => '/\{@override\s*?\}/i'
            ],
            $patternsSyntax = [
                'delimiter'    => '/\<\!\-\-\{(.+?)\}\-\-\>/s',
                'variable_1'   => '/\{(\\$[a-zA-Z_][\$\w]*(?:\[[\w\-\."\'\[\]\$]+\])*)\}/',
                'variable_2'   => '/\{(\\$[a-zA-Z_][a-zA-Z0-9_]*?\-\>[a-zA-Z_][a-zA-Z0-9_]*?)\}/',
                'constant'     => '/\{([A-Z_]+)\}/',
                'php'          => '/\{php\s+(.+?)\}/is',
                'if'           => '/\{if\s+(.+?)\}/is',
                'else'         => '/\{else\}/is',
                'elseif'       => '/\{elseif\s+(.+?)\}/is',
                'endif'        => '/\{\/if\}/is',
                'for'          => '/\{for\s+(.+?)\}/is',
                'endfor'       => '/\{\/for\}/is',
                'loop'         => '/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/i',
                'loop_nokey'   => '/\{loop\s+(\S+)\s+(\S+)\}/i',
                'endloop'      => '/\{\/loop\}/i',
                'function'     => '/\{([a-zA-Z_][a-zA-Z0-9_:\\\]*\(([^{}]*)\))\}/',
                'method'       => '/\{((\\$[a-zA-Z_][a-zA-Z0-9_\\\]*)\-\>([a-zA-Z_][a-zA-Z0-9_\\\]*)\(([^{}]*)\))\}/',
                'var_format_1' => '/(\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i',
                'var_format_2' => '/\{(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)\}/',
                'var_format_3' => '/\{(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\}/',
                'var_format_4' => '/\{(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)\}/',
                'var_format_5' => '/\[(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)\]/',
                // stripv_tag
                'var_format_6' => '/(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)/',
                'var_format_7' => '/(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.(\\$[a-zA-Z_][a-zA-Z0-9_]*?)/',
                'var_format_8' => '/(\\$[a-zA-Z_][a-zA-Z0-9_]*?)\.([a-zA-Z_][a-zA-Z0-9_]*?)/U',
                'phptag'       => '/\?>\s*?<\?php/s'
            ];

    /**
     * 实例化
     */
    public function __construct()
    {
        // 是否重新编译
        if (env('VIEW_DEBUG')) {
            $this->recompile = true;
        }
        
        // 模板文件存放路径
        $this->templatePath = BASE_PATH.'/'.env('TEMPLATE_PATH') ?: $this->templatePath;
        $this->templatePath = rtrim($this->templatePath, '/'). '/';

        // 编译文件存放路径
        $this->compilePath = BASE_PATH.'/'.env('COMPILE_PATH') ?: $this->compilePath;
        $this->compilePath = rtrim($this->compilePath, '/'). '/';

        // 模板后缀
        $this->suffix = env('TEMPLATE_SUFFIX') ?: $this->suffix;
    }

    //静态方法，单例统一访问入口
    static public function getInstance() {
        if (is_null ( self::$_instance ) || isset ( self::$_instance )) {
            self::$_instance = new self ();
        }
        return self::$_instance;
    }

    /**
     * 显示页面或返回页面内容
     *
     * @param  string $__template
     * @param  boolean $__output
     * @param  integer $__status
     * @return string|void
     * @throws Exception
     */
    public function display($__template, $__output = true, $__status = 200)
    {
        if ( ! $__template) {
            throw new Exception('未设置显示模板', 10501);
        }

        $this->template(ltrim(str_replace($this->suffix, '', $__template), '/'));

        ob_start();
        @extract($this->assignData);

        include $this->template;
        $__content = trim(ob_get_clean());

        if ($__output === 'include') {
            echo $__content;
            return;
        }
        elseif ($__output === true) {
            Response::output($__content, 'html', $__status);
            //我加的
            Response::send();
            return;
        }

        return $__content;
    }
    
    /**
     * 赋值到模板
     * 
     * @param  string $key
     * @param  string $val
     * @return object
     */
    public function assign($key = '', $val = null)
    {
        if ($key == '') return $this;

        if (is_array($key)) {
            $this->assignData = array_merge($this->assignData, $key);
        }
        else {
            $key and ($this->assignData[$key] = $val);
        }

        return $this;
    }
    
    /**
     * 获取已赋值的变量
     * 
     * @param  string $var
     * @return array|string|null
     */
    public function assigned($var = null)
    {
        return ($var === null) 
                ? $this->assignData 
                : (isset($this->assignData[$var]) ? $this->assignData[$var] : null);
    }
    
    /**
     * 设置子目录
     * 
     * @param  string $theme
     * @return object
     */
    public function theme($theme = '')
    {
        $this->theme = $theme ? rtrim($theme, '/'). '/' : '';

        return $this;
    }

    /**
     * 设置模板
     *
     * @param  string $template
     * @return object
     * @throws Exception
     */
    public function template($template)
    {
        $template = str_replace('.', '/', $template);
        $filepath = $this->templatePath. $this->theme. $template. $this->suffix;

        if ( ! file_exists($filepath)) {
            throw new Exception('模板文件不存在：'. $filepath, 10502);
        }

        $compiled = $this->compilePath. $this->theme. $template.'.compile.php';
        if ($this->recompile or !file_exists($compiled) or (@filemtime($compiled) <= @filemtime($filepath))) {
            $this->_compile($filepath, $compiled);
        }

        $this->template = $compiled;

        return $this;
    }

    /**
     * 清空数据
     * 
     * @return void
     */
    public function flushData()
    {
        $this->assignData = $this->sectionData = $this->sectionStack = [];
    }

    /**
     * 模板编译
     *
     * @param  string $filepath
     * @param $compiled
     * @return string
     * @throws Exception
     * @internal param string $cache_file
     */
    private function _compile($filepath, $compiled) 
    {
        $path = dirname($compiled);
        if ( ! is_dir($path) and ! mkdir($path, 0777, true)) {
            throw new Exception('无法创建模板编译缓存目录',  10503);
        }

        $template = file_get_contents($filepath);
        $template = $this->_parseLayout($template);
        $template = $this->_parseSyntax($template);

        $result = file_put_contents($compiled, $template);
        @chmod($compiled, 0777);
    }

    /**
     * 布局解析
     *
     * @param $template
     * @return mixed|string
     * @throws Exception
     */
    private function _parseLayout($template)
    {
        // {extends 'module.directory?.name'}
        preg_match_all($this->patternsLayout['extends'], $template, $matches);
        $count = count($matches[0]);

        if ($count > 1) {
            throw new Exception('模板编译错误：存在多个继承', 10504);
        }

        if ($count > 0) {
            $template  = str_replace($matches[0][0]. "\n", '', $template);
            $template .= "\n\n<?php \$this->display('{$matches[1][0]}', 'include'); ?>";
        }

        // {include 'module.directory?.name'}
        $template = preg_replace($this->patternsLayout['include'], '<?php $this->display("$1", "include"); ?>', $template);

        // {yield 'name', 'default'}
        $template = preg_replace($this->patternsLayout['yield'], '<?php $this->_yield("$1", "$3"); ?>', $template);

        // 用于判断section标签是否对称
        $sectionNum = 0;

        // {section 'name'}
        $template = preg_replace_callback($this->patternsLayout['section'], function ($m) use (&$sectionNum)
        {
            $sectionNum ++;
            return "<?php \$this->_sectionStart('{$m[1]}'); ?>";
        }, $template);

        // {/section|stop|show}
        $template = preg_replace_callback($this->patternsLayout['endsection'], function ($m) use (&$sectionNum)
        {
            $sectionNum --;
            $m[1] = $m[1] == 'section' ? 'Stop' : ucfirst($m[1]);
            return "<?php \$this->_section{$m[1]}(); ?>";
        }, $template);

        if ($sectionNum !== 0) {
            throw new Exception('模板编译错误：section 标签不匹配', 10505);
        }

        return $template;
    }

    /**
     * 语法解析
     *
     * @param  string $template
     * @return string
     * @throws Exception
     */
    private function _parseSyntax($template)
    {
        // 去除html注释符号<!---->
        $template = preg_replace($this->patternsSyntax['delimiter'], '{$1}', $template);

        // 替换变量
        $template = preg_replace($this->patternsSyntax['variable_1'], '<?php echo $1; ?>', $template);
        $template = preg_replace($this->patternsSyntax['variable_2'], '<?php echo $1; ?>', $template);
    
        // 替换常量
        $template = preg_replace($this->patternsSyntax['constant'], '<?php echo $1; ?>', $template);
        
        // php
        $template = preg_replace_callback($this->patternsSyntax['php'], function ($m)
        {
            $code = rtrim(trim($m[1]), ';');
            return "<?php {$code}; ?>";
        }, $template);

        // 用于判断标签对称
        $tagNum = 0;

        // if
        $template = preg_replace_callback($this->patternsSyntax['if'], function ($m) use (&$tagNum)
        {
            $tagNum ++;
            return $this->_stripvTag("<?php if ({$m[1]}) { ?>");
        }, $template);

        // else
        $template = preg_replace($this->patternsSyntax['else'], "<?php } else { ?>", $template);

        // elseif
        $template = preg_replace_callback($this->patternsSyntax['elseif'], function ($m)
        {
            return $this->_stripvTag("<?php } elseif ({$m[1]}) { ?>");
        }, $template);

        // end if
        $template = preg_replace_callback($this->patternsSyntax['endif'], function ($m) use (&$tagNum)
        {
            $tagNum --;
            return "<?php } ?>";    
        }, $template);

        // if 标签不对称
        if ($tagNum !== 0) {
            throw new Exception('模板编译错误：if 标签不匹配', 10506);
        }
        
        // for
        $template = preg_replace_callback($this->patternsSyntax['for'], function ($m) use (&$tagNum) {
            $tagNum ++;
            return $this->_stripvTag("<?php for ({$m[1]}) {?>");
        }, $template);

        // end for
        $template = preg_replace_callback($this->patternsSyntax['endfor'], function ($m) use (&$tagNum) 
        {
            $tagNum --;
            return "<?php } ?>";
        }, $template);

        // for 标签不对称
        if ($tagNum !== 0) {
            throw new Exception('模板编译错误：for 标签不匹配', 10507);
        }

        // loop
        $template = preg_replace_callback($this->patternsSyntax['loop_nokey'], function ($m) use (&$tagNum)
        {
            $tagNum ++;
            return $this->_stripvTag("<?php if (is_array({$m[1]}) or is_object({$m[1]})) { foreach({$m[1]} as {$m[2]}) { ?>");
        }, $template);
        $template = preg_replace_callback($this->patternsSyntax['loop'], function ($m) use (&$tagNum)
        {
            $tagNum ++;
            return $this->_stripvTag("<?php if (is_array({$m[1]}) or is_object({$m[1]})) { foreach({$m[1]} as {$m[2]} => {$m[3]}) { ?>");
        }, $template);
        $template = preg_replace_callback($this->patternsSyntax['endloop'], function ($m) use (&$tagNum)
        {
            $tagNum --;
            return "<?php } } ?>";
        }, $template);

        // loop 标签不对称
        if ($tagNum !== 0) {
            throw new Exception('模板编译错误：loop 标签不匹配', 10508);
        }

        // function
        $template = preg_replace_callback($this->patternsSyntax['function'], function($m) {
            return "<?php echo ". $this->_stripvTag($m[1]). "; ?>";
        }, $template);
        // method
        $template = preg_replace_callback($this->patternsSyntax['method'], function($m) {
            return "<?php if (is_object($m[2])) {echo ". $this->_stripvTag($m[1]). ";} ?>";
        }, $template);
        
        // 将二维数组替换成带单引号的标准模式
        $template = preg_replace($this->patternsSyntax['var_format_1'], '$1\'$2\']', $template);
        $template = preg_replace($this->patternsSyntax['var_format_2'], '<?php echo $1[\'$2\'][\'$3\']; ?>', $template);
        $template = preg_replace($this->patternsSyntax['var_format_3'], '<?php echo $1[$2]; ?>', $template);
        $template = preg_replace($this->patternsSyntax['var_format_4'], '<?php echo $1[\'$2\']; ?>', $template);
        $template = preg_replace($this->patternsSyntax['var_format_5'], '[$1[\'$2\']]', $template);
        
        // 删除多余PHP闭合符
        // $template = preg_replace($this->patternsSyntax['phptag'], '', $template);
        
        return trim($template);
    }

    /**
     * 把 $data.aa, $data.aa.bb, $data.$aa 替换成 $data['aa'], $data['aa']['bb'], $data[$aa]
     * 
     * @param  string $string
     * @return string
     */
    private function _stripvTag($string) 
    {
        $string = preg_replace($this->patternsSyntax['var_format_6'], '$1[\'$2\'][\'$3\']', $string);
        $string = preg_replace($this->patternsSyntax['var_format_7'], '$1[$2]', $string);
        $string = preg_replace($this->patternsSyntax['var_format_8'], '$1[\'$2\']', $string);
        $string = preg_replace($this->patternsSyntax['var_format_5'], '[$1[\'$2\']]', $string);

        return preg_replace(
            "/\<\?php echo (\@?\\\$[a-zA-Z_][\\\$\w]*(?:\[[\w\-\.\"\'\[\]\$]+\])*)\; \?\>/is", 
            "$1", 
            str_replace("\\\"", '"', $string)
        );
    }

    /**
     * 区块开始
     *
     * @param $section
     */
    private function _sectionStart($section) 
    {
        ob_start();
        $this->sectionStack[] = $section;
    }

    /**
     * 区块结束
     * 
     * @return void
     */
    private function _sectionStop() 
    {
        $last = array_pop($this->sectionStack);
        $this->sectionData[$last] = ob_get_clean();
    }

    /**
     * 可以显示的区块结束
     * 
     * @return void
     */
    private function _sectionShow() 
    {
        $last = array_pop($this->sectionStack);

        $content = ob_get_clean();
        if (isset($this->sectionData[$last])) {
            $content = $this->sectionData[$last];
        }

        echo $content;
    }
    
    /**
     * 内容
     * 
     * @param  string $section 
     * @param  string $default 
     * @return void
     */
    private function _yield($section, $default = '')
    {
        $content = $default;

        if (isset($this->sectionData[$section])) {
            $content = $this->sectionData[$section];
        }

        echo $content;
    }

}
