<?php
/**
 * 调用PHP GD/imagemagick/ffmpeg库<br>上传图片并转换成webp/avif
 *
 * @package SwitchImges
 * @author 苏晓晴
 * @version 1.4
 * @link https://www.toubiec.cn/
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class SwitchImges_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('SwitchImges_Plugin', 'uploadHandle');
        return _t('插件已启用!');
    }
  
    /**
     * 禁用插件方法
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        // 删除插件目录中的文件和目录
        self::clearBackupDirectory();
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
        // 图片质量设置
        $quality = new Typecho_Widget_Helper_Form_Element_Radio(
            'quality', 
            array('60' => _t('60'),'65'=>_t('65'),'70'=>_t('70'),'75'=>_t('75'),'80'=>_t('80'),'85'=>_t('85'),'90'=>_t('90'),'95'=>_t('95'),'100'=>_t('100')),
            '80','图片质量','设置转换后的图片格式质量（0-100）最佳80');
        $form->addInput($quality);

        // 最大宽度设置
        $maxWidth = new Typecho_Widget_Helper_Form_Element_Text('maxWidth', null, '', _t('最大宽度'), _t('设置上传图片的最大宽度，留空表示无限制'));
        $form->addInput($maxWidth);

        // 最大高度设置
        $maxHeight = new Typecho_Widget_Helper_Form_Element_Text('maxHeight', null, '', _t('最大高度'), _t('设置上传图片的最大高度，留空表示无限制'));
        $form->addInput($maxHeight);

        // 压缩格式选择：Webp、avif 或 tiff
        $compressext = new Typecho_Widget_Helper_Form_Element_Radio(
            'compressext',
            array('webp' => _t('Webp'), 'avif' => _t('Avif')),
            'webp',
            _t('压缩格式'),
            _t('选择使用 Webp、Avif 格式。')
        );
        $form->addInput($compressext);

        // 压缩方法选择：GD、ImageMagick、cwebp、ffmpeg
        $compressionMethod = new Typecho_Widget_Helper_Form_Element_Radio(
            'compressionMethod',
            array('gd' => _t('GD'), 'imagemagick' => _t('ImageMagick'), 'ffmpeg' => _t('ffmpeg')),
            'gd',
            _t('压缩方法'),
            _t('选择使用 GD(静态)、ImageMagick(静态)、ffmpeg(动态/静态) 工具来进行图像压缩。')
        );
        $form->addInput($compressionMethod);

        // GIF 处理方式选择
        $gifHandling = new Typecho_Widget_Helper_Form_Element_Radio(
            'gifHandling',
            array('none' => _t('不处理'), 'static' => _t('静态处理'), 'animated' => _t('动态处理')),
            'static',
            _t('GIF 处理方式'),
            _t('选择如何处理 GIF 文件，不处理 - 直接上传原文件，静态格式 适用于非动态 JPG|PNG|JPGE，动态格式 适用于包含动画的 GIF。')
        );
        $form->addInput($gifHandling);

        // 备份源文件开关
        $backupOriginal = new Typecho_Widget_Helper_Form_Element_Radio(
            'backupOriginal',
            array('enable' => _t('启用'), 'disable' => _t('禁用')),
            'disable',
            _t('备份源文件'),
            _t('启用此选项将会在转换为你所选择的压缩格式时在/usr/uploads/年份/月份/目录下创建backup目录并且生成源文件')
        );
        $form->addInput($backupOriginal);
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
     * 获取插件设置
     *
     * @access private
     * @return array
     */
    private static function getSettings()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $maxWidth = $options->plugin('SwitchImges')->maxWidth;
        $maxHeight = $options->plugin('SwitchImges')->maxHeight;

        return array(
            'quality' => (int)$options->plugin('SwitchImges')->quality,
            'maxWidth' => $maxWidth === '' ? null : (int)$maxWidth,
            'maxHeight' => $maxHeight === '' ? null : (int)$maxHeight,
            'compressionMethod' => $options->plugin('SwitchImges')->compressionMethod,
            'compressext' => $options->plugin('SwitchImges')->compressext,
            'gifHandling' => $options->plugin('SwitchImges')->gifHandling,
            'backupOriginal' => $options->plugin('SwitchImges')->backupOriginal
        );
    }

    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['tmp_name'])) {
            return false;
        }

        // 处理文件名和扩展名
        $fileName = pathinfo($file['name'], PATHINFO_BASENAME);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!self::checkFileType($ext)) {
            return self::defaultUploadHandle($file);
        }

        $settings = self::getSettings();
        
        // 如果是 GIF 文件且设置为不处理，则直接上传
        if ($ext === 'gif' && $settings['gifHandling'] === 'none') {
            return self::defaultUploadHandle($file);
        }
        $uploadDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/';
        $dateDir = date('Y/m/');
        $fullUploadDir = $uploadDir . $dateDir;
        $webpFileName = uniqid() . '.' . $settings['compressext'];
        $webpFilePath = $fullUploadDir . $webpFileName;

        // 确保目录存在
        if (!file_exists($fullUploadDir)) {
            mkdir($fullUploadDir, 0755, true);
        }

        // 临时文件路径
        $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;

        // 移动文件到临时路径
        if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            return false;
        }

        // 备份原始文件
        if ($settings['backupOriginal'] === 'enable') {
            // 确保 backup 目录存在，如果不存在则创建
            $backupDir = $fullUploadDir . 'backup/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true); // 创建备份目录，确保父目录也一并创建
            }

            // 备份文件路径
            $backupFilePath = $backupDir . uniqid() . '_backup.' . $ext;

            // 复制文件到备份目录
            copy($tempFilePath, $backupFilePath);
        }

        // 根据设置选择使用 GD、ImageMagick 或 cwebp 进行转换
        $conversionResult = false;
        if ($settings['compressionMethod'] === 'imagemagick') {
            $conversionResult = self::convertToWebPWithImageMagick($tempFilePath, $webpFilePath);
        } elseif ($settings['compressionMethod'] === 'ffmpeg') {
            $conversionResult = self::convertToWebPWithFFmpeg($tempFilePath, $webpFilePath);
        } else {
            $conversionResult = self::convertToWebPWithGD($tempFilePath, $webpFilePath);
        }

        // 如果转换失败
        if (!$conversionResult) {
            unlink($tempFilePath); // 删除临时文件
            return false;
        }

        // 删除临时文件
        unlink($tempFilePath);

        // 返回 WebP 文件信息
        return array(
            'name' => $fileName,
            'path' => str_replace(__TYPECHO_ROOT_DIR__, '', $webpFilePath),
            'size' => filesize($webpFilePath),
            'type' => $settings['compressext'],
            'mime' => 'image/'.$settings['compressext']
        );
    }

    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function defaultUploadHandle($file)
    {

        if (empty($file['tmp_name'])) {
            return false;
        }
        // 处理文件名和扩展名
        $fileName = pathinfo($file['name'], PATHINFO_BASENAME);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $uploadDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/';
        $dateDir = date('Y/m/');
        $fullUploadDir = $uploadDir . $dateDir;
        $fileName = uniqid() . '.'.$ext;
        $filePath = $fullUploadDir . $fileName;
        // 确保目录存在
        if (!file_exists($fullUploadDir)) {
            mkdir($fullUploadDir, 0755, true);
        }

        // 移动文件到目标路径
        move_uploaded_file($file['tmp_name'], $filePath);
        
        // 返回文件信息
        return array(
            'name' => $fileName,
            'path' => str_replace(__TYPECHO_ROOT_DIR__, '', $filePath),
            'size' => filesize($filePath),
            'type' => $ext,
            'mime' => $file['type']
        );
    }


    /**
     * 检查文件类型
     *
     * @access private
     * @param string $ext 文件扩展名
     * @return bool
     */
    private static function checkFileType($ext)
    {
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
        return in_array($ext, $allowedExtensions);
    }

    public static function clearBackupDirectory()
    {
        $uploadDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/';
        self::deleteBackupDirectory($uploadDir);  // 递归删除备份目录
    }

    /**
     * 递归遍历并删除 backup 目录
     */
    private static function deleteBackupDirectory($dir)
    {
        // 获取当前目录下的所有子目录和文件
        $items = array_diff(scandir($dir), array('.', '..'));

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            // 如果是目录，进一步检查是否为 backup 目录
            if (is_dir($path)) {
                if (basename($path) === 'backup') {
                    // 如果是 backup 目录，递归删除其内容
                    self::deleteDirectory($path);
                    rmdir($path); // 删除空的 backup 目录
                } else {
                    // 如果不是 backup 目录，继续递归查找
                    self::deleteBackupDirectory($path);
                }
            }
        }
    }

    /**
     * 递归删除目录及其内容
     */
    private static function deleteDirectory($dir)
    {
        $items = array_diff(scandir($dir), array('.', '..'));

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                self::deleteDirectory($path);  // 递归删除子目录
            } else {
                unlink($path);  // 删除文件
            }
        }

        rmdir($dir);  // 删除空目录
    }

    /**
     * 使用 FFmpeg 将图像转换为对应格式
     *
     * @access private
     * @param string $sourceFile 源文件路径
     * @param string $outputFile 输出文件路径
     * @return bool 转换是否成功
     */
    private static function convertToWebPWithFFmpeg($sourceFile, $outputFile)
    {
        // 获取设置，如最大宽度、高度及质量
        $settings = self::getSettings();

        // 获取源图像的宽度和高度
        list($width, $height) = getimagesize($sourceFile);

        // 计算等比缩放后的宽度和高度，确保不会超出最大宽高
        $maxWidth = $settings['maxWidth'] ?? $width;
        $maxHeight = $settings['maxHeight'] ?? $height;
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;

        // 使用 FFmpeg 命令行工具处理 GIF 动画转换为 WebP 动画，并保持动画循环
        $cmd = sprintf(
            'ffmpeg -i %s -vf "scale=%d:%d" -q:v %d -loop 0 %s',
            escapeshellarg($sourceFile),   // 输入文件
            $newWidth,                    // 缩放后的宽度
            $newHeight,                   // 缩放后的高度
            $settings['quality'],         // 输出质量
            escapeshellarg($outputFile)    // 输出文件路径
        );

        // 执行 FFmpeg 命令
        exec($cmd, $output, $returnVar);

        // 返回 FFmpeg 命令执行结果
        return $returnVar === 0;
    }

    /**
     * 使用 GD 库转换为对应格式
     *
     * @access private
     * @param string $sourceFile 源文件路径
     * @param string $outputFile 输出文件路径
     * @return bool 转换是否成功
     */
    private static function convertToWebPWithGD($sourceFile, $outputFile)
    {
        // 获取设置，如最大宽度、高度及质量
        $settings = self::getSettings();

        // 获取源图像的宽度、高度和类型
        list($width, $height, $type) = getimagesize($sourceFile);

        // 计算等比缩放后的宽度和高度，确保不会超出最大宽高
        $maxWidth = $settings['maxWidth'] ?? $width;
        $maxHeight = $settings['maxHeight'] ?? $height;
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;

        // 根据图像类型选择相应的创建函数
        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourceFile);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourceFile);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourceFile);
                break;
            default:
                return false; // 不支持的图像类型
        }

        if (!$image) {
            return false; // 图像创建失败
        }

        // 使用 GD 库对图像进行等比缩放
        $resizedImage = imagescale($image, $newWidth, $newHeight);

        // 生成 WebP 文件，使用指定的质量参数
        $result = imagewebp($resizedImage, $outputFile, $settings['quality']);

        // 销毁图像资源，释放内存
        imagedestroy($image);
        imagedestroy($resizedImage);

        return $result;
    }

    /**
     * 使用 ImageMagick 转换为对应格式
     *
     * @access private
     * @param string $sourceFile 源文件路径
     * @param string $outputFile 输出文件路径
     * @return bool 转换是否成功
     */
    private static function convertToWebPWithImageMagick($sourceFile, $outputFile)
    {
        // 获取设置，如最大宽度、高度及质量
        $settings = self::getSettings();

        // 获取源图像的宽度和高度
        list($width, $height) = getimagesize($sourceFile);

        // 计算等比缩放后的宽度和高度，确保不会超出最大宽高
        $maxWidth = $settings['maxWidth'] ?? $width;
        $maxHeight = $settings['maxHeight'] ?? $height;
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;

        // 使用 ImageMagick 命令行工具进行转换并缩放
        $cmd = sprintf(
            'convert %s -resize %dx%d -compress lzw -quality %d %s',
            escapeshellarg($sourceFile),
            $newWidth,
            $newHeight,
            $settings['quality'],
            escapeshellarg($outputFile)
        );

        // 执行命令
        exec($cmd, $output, $returnVar);

        return $returnVar === 0;
    }
}
?>