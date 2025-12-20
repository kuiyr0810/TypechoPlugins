<?php
/**
 * 抖音视频播放插件
 *
 * @package DouYinPlayer
 * @author  王叨叨
 * @version 1.0.0
 * @link    https://wangdaodao.com
 * @description 通过抖音视频ID获取IFrame代码，实现抖音视频播放
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class DouYinPlayer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('DouYinPlayer_Plugin', 'replacePlayer');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('DouYinPlayer_Plugin', 'replacePlayer');
        return _t('插件已激活，将在内容渲染时替换抖音视频链接为播放器');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        return _t('插件已禁用，抖音视频链接将恢复为原始链接');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 视频宽度 */
        $width = new Typecho_Widget_Helper_Form_Element_Text(
            'width',
            null,
            '100%',
            _t('视频宽度'),
            _t('设置视频播放器的宽度，例如：100%')
        );
        $form->addInput($width);

        /** 视频高度 */
        $height = new Typecho_Widget_Helper_Form_Element_Text(
            'height',
            null,
            '720px',
            _t('视频高度'),
            _t('设置视频播放器的高度，例如：720px')
        );
        $form->addInput($height);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 个人用户配置，如果需要的话
    }

    /**
     * 替换抖音视频链接为播放器
     *
     * @access public
     * @param string $content 文章内容
     * @param Widget_Abstract_Contents $widget 内容对象
     * @param string $lastResult 上一次处理结果
     * @return string
     */
    public static function replacePlayer($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        
        // 获取插件配置
        $options = Helper::options();
        $config = $options->plugin('DouYinPlayer');

        if (!$config) {
            return $content;
        }

        // 获取配置参数
        $width = $config->width ?: '100%';
        $height = $config->height ?: '720px';

        // 正则匹配Typecho转换后的a标签格式
        $pattern = '/<a\s+[^>]*href=["\']https:\/\/www\.douyin\.com\/video\/(\d+)["\'][^>]*>.*?<\/a>/i';

        // 处理链接
        $content = preg_replace_callback($pattern, function($matches) use ($width, $height) {
            // 获取视频ID
            $videoId = $matches[1];
            
            // 生成iframe代码
            $iframe = '<p style="display: flex;justify-content: center;"><iframe width="' . $width . '" height="' . $height . '" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true" src="https://open.douyin.com/player/video?vid=' . $videoId . '&autoplay=0" referrerpolicy="unsafe-url"></iframe></p>';
            
            return $iframe;
        }, $content);

        return $content;
    }
}