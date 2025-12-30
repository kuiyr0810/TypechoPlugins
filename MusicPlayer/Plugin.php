<?php
/**
 * 音乐播放器插件
 *
 * @package MusicPlayer
 * @author  王叨叨
 * @version 1.1.0
 * @link    https://wangdaodao.com
 * @description 将音乐链接自动转换为可嵌入的播放器，支持网易云音乐、QQ音乐、本地音乐
 */

!defined('__TYPECHO_ROOT_DIR__') && exit();

class MusicPlayer_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('MusicPlayer_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MusicPlayer_Plugin', 'replacePlayer');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('MusicPlayer_Plugin', 'replacePlayer');
        return _t('插件已激活，将在内容渲染时替换音乐链接为播放器');
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
        return _t('插件已禁用，音乐链接将恢复为原始链接');
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
        $auto = new Typecho_Widget_Helper_Form_Element_Radio(
            'auto',
            array('0' => '不自动播放', '1' => '自动播放'),
            '0',
            _t('自动播放设置'),
            _t('设置播放器是否自动播放')
        );
        $form->addInput($auto);
    }

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
        $cssFilePath = dirname(__FILE__) . '/assets/style.css';
        $pluginUrl = Helper::options()->pluginUrl . '/MusicPlayer/assets/style.css';
        $timestamp = file_exists($cssFilePath) ? filemtime($cssFilePath) : time();
        echo '<link rel="stylesheet" type="text/css" href="' . $pluginUrl . '?v=' . $timestamp . '" />';
    }

    /**
     * 替换音乐链接为播放器
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

        // 获取配置的auto参数
        $options = Helper::options()->plugin('MusicPlayer');
        $auto = isset($options->auto) ? $options->auto : '0';

        // 正则匹配Typecho转换后的a标签格式（歌曲）
        $neteaseSongPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/song\?id=(\d+)["\'][^>]*>.*?<\/a>|<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/song\/(\d+)["\'][^>]*>.*?<\/a>/i';

        // 处理歌曲链接
        $content = preg_replace_callback($neteaseSongPattern, function($matches) use ($auto) {
            // 获取歌曲ID，支持两种格式：?id=数字 和 /song/数字
            $songId = !empty($matches[1]) ? $matches[1] : $matches[2];

            // 生成iframe代码
            $iframe = '<div class="typecho-netease-player"><iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width=100% height=86 src="https://music.163.com/outchain/player?type=2&id=' . $songId . '&auto=' . $auto . '&height=66"></iframe></div>';

            return $iframe;
        }, $content);

        // 正则匹配Typecho转换后的a标签格式（歌单）
        $neteasePlaylistPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/playlist\?id=(\d+)["\'][^>]*>.*?<\/a>|<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/playlist\/(\d+)["\'][^>]*>.*?<\/a>/i';

        // 处理歌单链接
        $content = preg_replace_callback($neteasePlaylistPattern, function($matches) use ($auto) {
            // 获取歌单ID，支持两种格式：?id=数字 和 /playlist/数字
            $playlistId = !empty($matches[1]) ? $matches[1] : $matches[2];

            // 生成iframe代码
            $iframe = '<div class="typecho-netease-player"><iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width=100% height=480 src="https://music.163.com/outchain/player?type=0&id=' . $playlistId . '&auto=' . $auto . '&height=460"></iframe></div>';

            return $iframe;
        }, $content);

        // 正则匹配Typecho转换后的a标签格式（专辑）
        $neteaseAlbumPattern = '/<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/album\?id=(\d+)["\'][^>]*>.*?<\/a>|<a\s+[^>]*href=["\']https:\/\/music\.163\.com\/#\/album\/(\d+)["\'][^>]*>.*?<\/a>/i';

        // 处理专辑链接
        $content = preg_replace_callback($neteaseAlbumPattern, function($matches) use ($auto) {
            // 获取专辑ID，支持两种格式：?id=数字 和 /album/数字
            $albumId = !empty($matches[1]) ? $matches[1] : $matches[2];

            // 生成iframe代码
            $iframe = '<div class="typecho-netease-player"><iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width=100% height=450 src="https://music.163.com/outchain/player?type=1&id=' . $albumId . '&auto=' . $auto . '&height=430"></iframe></div>';

            return $iframe;
        }, $content);

        // 正则匹配Typecho转换后的a标签格式（QQ音乐）
        $qqSongPattern = '/<a\s+[^>]*href=["\']https:\/\/i\.y\.qq\.com\/v8\/playsong\.html\?[\s\S]*?songid=(\d+)[\s\S]*?["\'][^>]*>.*?<\/a>/i';

        // 处理歌曲链接
        $content = preg_replace_callback($qqSongPattern, function($matches) use ($auto) {
            // 获取歌曲ID
            $songId = $matches[1];

            // 生成iframe代码
            $iframe = '<div class="typecho-qq-player"><iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width=100% height=65 src="https://i.y.qq.com/n2/m/outchain/player/index.html?songid=' . $songId . '&songtype=0"></iframe></div>';

            return $iframe;
        }, $content);

        return $content;
    }
}
