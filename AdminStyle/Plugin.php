<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Typecho后台样式修改插件
 *
 * @package AdminStyle
 * @author 王叨叨
 * @version 1.0.0
 * @link https://wangdaodao.com/
 */
class AdminStyle_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        // 在后台头部插入CSS，使用admin/footer.php钩子确保在页面底部加载
        Typecho_Plugin::factory('admin/footer.php')->end = array('AdminStyle_Plugin', 'insertStyles');
        return _t('后台样式修改插件已激活，正在应用自定义样式。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        return _t('后台样式修改插件已被禁用。');
    }

    /**
     * 插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 获取assets/css目录下的所有CSS文件
        $cssFiles = array();
        $cssDir = dirname(__FILE__) . '/assets/css';

        if (is_dir($cssDir)) {
            $files = scandir($cssDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                    $cssFiles[$file] = $file;
                }
            }
        }

        // 如果没有CSS文件，添加默认选项
        if (empty($cssFiles)) {
            $cssFiles['default.css'] = 'default.css';
        }

        $styleFile = new Typecho_Widget_Helper_Form_Element_Select(
            'styleFile',
            $cssFiles,
            'default.css',
            _t('选择样式文件'),
            _t('请选择要应用的CSS样式文件。将assets/css目录下的CSS文件放入列表中。')
        );
        $form->addInput($styleFile);

        $customCSS = new Typecho_Widget_Helper_Form_Element_Textarea(
            'customCSS',
            null,
            '',
            _t('自定义CSS'),
            _t('在这里输入自定义CSS代码，将直接应用到后台。')
        );
        $form->addInput($customCSS);
    }

    /**
     * 个人用户的配置面板（可留空）
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 插入样式文件
     */
    public static function insertStyles()
    {
        $options = Helper::options();
        $plugin = $options->plugin('AdminStyle');

        // 输出选中的CSS文件，确保在原有CSS之后加载
        if (isset($plugin->styleFile)) {
            $cssUrl = $options->pluginUrl . '/AdminStyle/assets/css/' . $plugin->styleFile;
            echo <<<HTML
<!-- AdminStyle Plugin CSS -->
<link rel="stylesheet" type="text/css" href="{$cssUrl}" media="screen" />

HTML;
        }

        // 输出自定义CSS
        if (!empty($plugin->customCSS)) {
            echo <<<HTML
<!-- AdminStyle Plugin Custom CSS -->
<style type="text/css" media="screen">
/* AdminStyle Plugin Custom CSS */
{$plugin->customCSS}
</style>

HTML;
        }
    }
}