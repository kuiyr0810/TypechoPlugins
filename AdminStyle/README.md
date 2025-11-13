# AdminStyle 插件

这是一个用于修改Typecho后台管理界面样式的插件。

## 功能特点

- 可以通过CSS文件修改后台样式
- 支持多种预设主题
- 支持自定义CSS代码
- 简单易用的配置界面

## 安装方法

1. 将整个AdminStyle文件夹上传到Typecho的`usr/plugins/`目录下
2. 登录Typecho后台，进入"控制台" -> "插件"
3. 找到"AdminStyle"插件，点击"启用"

## 使用方法

### 选择预设样式

1. 在插件配置页面，从"选择样式文件"下拉菜单中选择一个CSS文件
2. 点击"保存设置"

### 添加自定义CSS

1. 在插件配置页面的"自定义CSS"文本框中输入CSS代码
2. 点击"保存设置"

## 添加新样式

1. 在插件的`assets/css/`目录下创建新的CSS文件
2. 在插件配置页面刷新，新文件会自动出现在"选择样式文件"下拉菜单中

## 预设样式

- `default.css`: 默认样式空

## 示例CSS代码

以下是一些常用的CSS修改示例：

```css
/* 修改顶部导航栏背景色 */
.typecho-head-nav {
    background-color: #2c3e50 !important;
}

/* 修改侧边栏背景色 */
.typecho-side-nav {
    background-color: #34495e !important;
}

/* 修改主要按钮颜色 */
.btn-primary {
    background-color: #3498db !important;
    border-color: #2980b9 !important;
}
```

## 注意事项

- 修改CSS时请使用`!important`标记以确保样式能够覆盖原有样式
- 如果修改后没有生效，请尝试清除浏览器缓存
- 建议在修改前备份原有样式
