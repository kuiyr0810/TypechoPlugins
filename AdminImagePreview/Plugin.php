<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Typecho后台图片预览插件
 *
 * @package AdminImagePreview
 * @author 王叨叨
 * @version 1.0.0
 * @link https://wangdaodao.com
 */
class AdminImagePreview_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        // 在后台附件管理页面插入CSS和JS
        Typecho_Plugin::factory('admin/manage-medias.php')->bottom = array('AdminImagePreview_Plugin', 'insertFiles');

        // 在文章编辑页面插入CSS和JS
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AdminImagePreview_Plugin', 'insertFiles');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AdminImagePreview_Plugin', 'insertFiles');

        return _t('图片预览插件已激活，正在为附件管理页面和文章编辑页面添加图片预览功能。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        return _t('图片预览插件已被禁用。');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 无需配置选项
    }

    /**
     * 个人用户的配置面板（可留空）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 插入CSS和JS文件
     */
    public static function insertFiles()
    {
        $options = Helper::options();
        
        // 获取插件URL - 使用Typecho的标准方式
        $pluginUrl = $options->pluginUrl . '/AdminImagePreview/';
        
        // 输出CSS和JavaScript文件
        echo <<<HTML
<!-- AdminImagePreview Plugin CSS -->
<link rel="stylesheet" type="text/css" href="{$pluginUrl}assets/css/preview.css" />

<!-- AdminImagePreview Plugin JavaScript -->
<script type="text/javascript" src="{$pluginUrl}assets/js/preview.js"></script>
HTML;
    }
}