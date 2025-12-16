<?php
/**
 * 基于 flowchart.js 封装的 Typecho 流程图插件
 *
 * @package FlowChart
 * @author 王叨叨
 * @version 1.0.0
 * @link https://wangdaodao.com
 */
class FlowChart_Plugin implements Typecho_Plugin_Interface {
     /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){

        $lang = new Typecho_Widget_Helper_Form_Element_Radio('lang', array(
            0   =>  _t('英文'),
            1   =>  _t('中文')
        ), 1, _t('语言'), _t('判断语句显示为Yes, No还是是, 否'));
        $form->addInput($lang->addRule('enum', _t('请选择一种语言'), array(0, 1)));

		$importJquery = new Typecho_Widget_Helper_Form_Element_Radio('importJquery', array(
            0   =>  _t('否'),
            1   =>  _t('是')
        ), 1, _t('引入jQuery'), _t('本插件需要jQuery, 如果你的网站没有自带jQuery, 请选择"是"'));
        $form->addInput($importJquery->addRule('enum', _t('请选择一个'), array(0, 1)));

        // 流程图样式配置
        $lineWidth = new Typecho_Widget_Helper_Form_Element_Text('lineWidth', null, '2', _t('线条宽度'), _t('流程图线条的宽度，单位为像素'));
        $form->addInput($lineWidth->addRule('isInteger', _t('请输入一个整数')));

        $lineLength = new Typecho_Widget_Helper_Form_Element_Text('lineLength', null, '50', _t('线条长度'), _t('流程图线条的长度，单位为像素'));
        $form->addInput($lineLength->addRule('isInteger', _t('请输入一个整数')));

        $textMargin = new Typecho_Widget_Helper_Form_Element_Text('textMargin', null, '12', _t('文本边距'), _t('流程图文本的边距，单位为像素'));
        $form->addInput($textMargin->addRule('isInteger', _t('请输入一个整数')));

        $fontSize = new Typecho_Widget_Helper_Form_Element_Text('fontSize', null, '14', _t('字体大小'), _t('流程图文本的字体大小，单位为像素'));
        $form->addInput($fontSize->addRule('isInteger', _t('请输入一个整数')));

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render() {

    }


    /**
     * 判断是否是内容页，避免主页加载插件
     */
    public static function is_content() {
        static $is_content = null;
        if($is_content === null) {
            $widget = Typecho_Widget::widget('Widget_Archive');
            // 使用更准确的判断方式，只在文章页面和独立页面加载插件
            $is_content = $widget->is('single');
        }
        return $is_content;
    }

    /**
     * 检查页面中是否包含流程图代码块
     */
    public static function has_flowchart() {
        // 获取当前页面内容
        $widget = Typecho_Widget::widget('Widget_Archive');
        $content = '';

        // 根据页面类型获取内容
        if ($widget->is('post')) {
            $content = $widget->content;
        } elseif ($widget->is('page')) {
            $content = $widget->content;
        }

        // 检查内容中是否包含流程图代码块
        return strpos($content, 'language-flow') !== false || strpos($content, 'lang-flow') !== false;
    }


    /**
     *为header添加css文件
     *@return void
     */
    public static function header() {
        if (!self::is_content() || !self::has_flowchart()) {
            return;
        }



        // 引入CSS文件
        $options = Helper::options();
        $cssUrl = $options->pluginUrl . '/FlowChart/style.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" media="screen" />' . "\n";

        $yesText = "是";
        $noText = "否";
        if (!Helper::options()->plugin('FlowChart')->lang) {
            $yesText = "Yes";
            $noText = "No";
        }

        // 获取流程图样式配置
        $lineWidth = Helper::options()->plugin('FlowChart')->lineWidth ?: 1;
        $lineLength = Helper::options()->plugin('FlowChart')->lineLength ?: 50;
        $textMargin = Helper::options()->plugin('FlowChart')->textMargin ?: 12;
        $fontSize = Helper::options()->plugin('FlowChart')->fontSize ?: 14;
        // 从文件头部注释中动态提取版本号
        $fileContent = file_get_contents(__FILE__);
        if (preg_match('/\*\s*@version\s+([\d\.]+)/', $fileContent, $matches)) {
            $version = $matches[1];
        } else {
            $version = '1.0.0'; // 默认版本号作为后备
        }

		if (Helper::options()->plugin('FlowChart')->importJquery) {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
        }
            echo <<<HTML

<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowchart/1.18.0/flowchart.min.js"></script>
<script>
        $(function () {
            console.info("%cFlowChart v{$version}%c https://wangdaodao.com/ ", "color: #013821; background: #43bb88; padding:5px; font-size: 12px; font-weight: bold;","color: #fadfa3; background: #030307; padding:5px; font-size: 12px; font-weight: bold;");
            var flow_elements = $('code.language-flow,code.lang-flow');
			for(var i =0; i<flow_elements.length; i++)
			{
				var flow_element = flow_elements[i];
				var container = document.createElement("div");
				container.className = "flow-chart";
				flow_element.parentNode.parentNode.insertBefore(container,flow_element.parentNode);
				var code = flow_element.innerText;
				chart = flowchart.parse(code);
				flow_element.parentNode.remove();
				chart.drawSVG(container, {
                              'x': 0,
                              'y': 0,
                              'line-width': {$lineWidth},
                              'line-length': {$lineLength},
                              'text-margin': {$textMargin},
                              'font-size': {$fontSize},
                              'font-color': 'black',
                              'line-color': 'black',
                              'element-color': 'black',
                              'fill': 'white',
                              'yes-text': '$yesText',
                              'no-text': '$noText',
                              'arrow-end': 'block',
                              'scale': 1
                              ,
                            });
			}

        });

    </script>


HTML;

    }


}