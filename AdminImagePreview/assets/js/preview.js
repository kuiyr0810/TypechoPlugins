/**
 * AdminImagePreview 插件 JavaScript
 * 为 Typecho 后台附件管理页面添加图片预览功能
 */

(function () {
  // 等待DOM加载完成
  document.addEventListener('DOMContentLoaded', function () {
    // 初始化图片预览
    initImagePreview();

    // 监听文件上传完成事件，动态添加新上传图片的预览
    observeFileList();

    // 监听文件上传完成的自定义事件
    if (window.jQuery) {
      window
        .jQuery(document)
        .on('fileUploadComplete', function (event, id, url, data) {
          // 延迟执行，确保DOM已经更新
          setTimeout(function () {
            var li = document.getElementById(id);
            if (li) {
              // 检查是否为图片
              var isImage =
                data.isImage ||
                window.jQuery(li).data('image') === true ||
                window.jQuery(li).data('image') === 1;

              if (isImage) {
                addImagePreview(li);
              }
            }
          }, 100);
        });

      // 添加额外的DOM变化监听，确保不遗漏任何图片
      window.jQuery(document).on('DOMNodeInserted', function (event) {
        var node = event.target;
        if (
          node &&
          node.nodeType === Node.ELEMENT_NODE &&
          node.tagName === 'LI'
        ) {
          // 延迟检查，确保jQuery数据已设置
          setTimeout(function () {
            checkAndAddPreview(node);
          }, 50);
        }
      });
    }
  });

  /**
   * 初始化图片预览功能
   */
  function initImagePreview() {
    // 检测当前页面类型
    var isMediaPage = document.getElementById('file-list') !== null;
    var isWritePage = document.getElementById('text') !== null;

    if (isMediaPage) {
      // 附件管理页面
      initMediaPagePreview();
    } else if (isWritePage) {
      // 文章编辑页面
      initWritePagePreview();
    }
  }

  /**
   * 初始化附件管理页面的图片预览功能
   */
  function initMediaPagePreview() {
    // 获取所有图片附件
    var imageItems = document.querySelectorAll('#file-list li[data-image="1"]');

    // 为每个图片附件添加预览图
    imageItems.forEach(function (item) {
      addImagePreview(item);
    });

    // 如果使用jQuery，也检查jQuery数据对象中的图片
    if (window.jQuery) {
      window.jQuery('#file-list li').each(function () {
        var $this = window.jQuery(this);
        if ($this.data('image') === true || $this.data('image') === 1) {
          addImagePreview(this);
        }
      });
    }
  }

  /**
   * 初始化文章编辑页面的图片预览功能
   */
  function initWritePagePreview() {
    // 文章编辑页面的图片预览逻辑
    // 这里可以根据实际需求实现
  }

  /**
   * 为单个图片附件添加预览
   * @param {HTMLElement} item - 图片附件的li元素
   */
  function addImagePreview(item) {
    var imageUrl = item.getAttribute('data-url');
    var insertLink = item.querySelector('.insert');

    // 检查是否已经添加了预览图，避免重复添加
    if (item.querySelector('.image-preview')) {
      return;
    }

    // 如果没有从属性中获取到URL，尝试从jQuery数据对象中获取
    if (!imageUrl && window.jQuery) {
      var $item = window.jQuery(item);
      imageUrl = $item.data('url');
    }

    // 如果仍然没有URL，尝试从插入链接中提取
    if (!imageUrl && insertLink) {
      // 尝试从插入链接的href属性中提取URL
      var href = insertLink.getAttribute('href');
      if (href && href !== '###') {
        imageUrl = href;
      }
    }

    // 如果仍然没有URL，尝试从图片链接中获取
    if (!imageUrl) {
      // 查找可能包含图片URL的链接
      var fileLink = item.querySelector('.file');
      if (fileLink) {
        var href = fileLink.getAttribute('href');
        if (href) {
          imageUrl = href;
        }
      }
    }

    if (imageUrl && insertLink) {
      // 添加has-image-preview类，以便CSS样式生效
      item.classList.add('has-image-preview');

      var img = document.createElement('img');
      img.className = 'image-preview';
      img.src = imageUrl;
      img.alt = insertLink.textContent;
      img.loading = 'lazy'; // 添加lazy属性

      // 将图片插入到li元素的开头
      item.insertBefore(img, item.firstChild);
    }
  }

  /**
   * 监听文件列表变化，为新上传的图片添加预览
   */
  function observeFileList() {
    // 只在附件管理页面执行
    var fileList = document.getElementById('file-list');

    if (!fileList) {
      return;
    }

    // 创建一个观察器实例
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === 'childList') {
          // 检查新增的节点
          if (mutation.addedNodes.length > 0) {
            mutation.addedNodes.forEach(function (node) {
              // 检查新增的节点是否是图片附件
              if (node.nodeType === Node.ELEMENT_NODE) {
                // 直接检查新增的LI元素
                if (node.tagName === 'LI') {
                  checkAndAddPreview(node);
                }
                // 检查新增节点下的LI元素
                else if (node.querySelectorAll) {
                  var liElements = node.querySelectorAll('li');
                  liElements.forEach(function (li) {
                    checkAndAddPreview(li);
                  });
                }
              }
            });
          }

          // 检查修改的节点
          if (mutation.target && mutation.target.tagName === 'LI') {
            checkAndAddPreview(mutation.target);
          }
        }
      });
    });

    // 配置观察选项
    var config = {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['data-image', 'class'],
    };

    // 开始观察目标节点
    observer.observe(fileList, config);

    // 返回观察器实例，便于后续可能的取消观察
    return observer;
  }

  /**
   * 检查节点是否为图片并添加预览
   * @param {HTMLElement} node - 要检查的节点
   */
  function checkAndAddPreview(node) {
    if (!node || node.tagName !== 'LI') {
      return;
    }

    // 检查是否有 data-image 属性或者 jQuery 数据对象中的 image 属性
    var isImage = node.getAttribute('data-image') === '1';

    // 如果没有 data-image 属性，检查 jQuery 数据对象
    if (!isImage && window.jQuery) {
      var $node = window.jQuery(node);
      isImage = $node.data('image') === true || $node.data('image') === 1;
    }

    // 如果仍然不是图片，尝试通过文件扩展名判断
    if (!isImage) {
      var insertLink = node.querySelector('.insert');
      if (insertLink) {
        var fileName = insertLink.textContent;
        if (fileName) {
          var imageExtensions = /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i;
          isImage = imageExtensions.test(fileName);
        }
      }
    }

    if (isImage) {
      addImagePreview(node);
    }
  }
})();
