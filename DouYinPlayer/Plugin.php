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
        Typecho_Plugin::factory('Widget_Archive')->header = array('DouYinPlayer_Plugin', 'header');
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
    public static function config(Typecho_Widget_Helper_Form $form) {}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 在页面头部添加CSS文件
     *
     * @access public
     * @return void
     */
    public static function header()
    {
        // 获取插件URL
        $pluginUrl = Helper::options()->pluginUrl . '/DouYinPlayer/assets/style.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $pluginUrl . '" />';
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

        // 使用固定参数
        $width = '100%';
        $height = '100%';

        // 正则匹配Typecho转换后的a标签格式
        $pattern = '/<a\s+[^>]*href=["\']https:\/\/www\.douyin\.com\/video\/(\d+)["\'][^>]*>.*?<\/a>/i';

        // 处理链接
        $content = preg_replace_callback($pattern, function($matches) use ($width, $height) {
            // 获取视频ID
            $videoId = $matches[1];

            // 生成iframe代码
            $iframe = '<div class="typecho-douyin-video"><div class="typecho-douyin-video-wrapper"><iframe width="' . $width . '" height="' . $height . '" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true" src="https://open.douyin.com/player/video?vid=' . $videoId . '&autoplay=0" referrerpolicy="unsafe-url"></iframe></div></div>';

            return $iframe;
        }, $content);

        return $content;
    }
}