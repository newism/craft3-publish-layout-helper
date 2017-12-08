<?php

namespace newism\publishlayouthelper;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\services\Plugins;
use craft\web\View;
use newism\publishlayouthelper\assetbundles\matrixindent\MatrixIndentAsset;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Leevi Graham
 * @package   NsmFields
 * @since     1.0.0
 */
class NsmPublishLayoutHelper extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * NsmFields::$plugin
     *
     * @var static
     */
    public static $plugin;


    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * NsmFields::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Do something after we're installed
        Event::on(
            Plugins::className(),
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
            }
        );

        Craft::$app->getView()->registerAssetBundle(MatrixIndentAsset::class);

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(View::class, View::EVENT_END_BODY, function (Event $event) {

                $js = <<<EOL

;(function ($, Craft, window, document, undefined) {

var indentMatrix = function (matrix) {

  var matrixBlocks = $(matrix).find('.matrixblock');
  var inPageSection = false;
  var children;

  matrixBlocks.removeClass('l-1 l-2');

  matrixBlocks.each(function () {

    var block = $(this);
    var blockType = block.data('type');

    switch (blockType) {
      case 'pageSection':
        inPageSection = true;
        children = block.nextUntil('[data-type=pageSection]');
        children.addClass('l-1');
        break;
      case 'gridSection':
        children = block.nextUntil('[data-type=pageSection], [data-type=gridSection]');
        children.addClass(inPageSection ? 'l-2' : 'l-1');
    }
  })
};

$('.matrix-field').each(function () {
  var matrixField = $(this)
  var matrix = matrixField.data('matrix');
  matrix.blockSort.on('sortChange', function () {
    indentMatrix(matrixField);
  });
  
  matrix.\$addBlockBtnGroupBtns.on('click', function(ev) {
    indentMatrix(matrixField);
  });
  
  matrix.blockSort.trigger('sortChange');
})

})(jQuery, Craft, window, document);

EOL;
                Craft::$app->getView()->js[View::POS_READY]['nsm-publish-layout-helper'] = $js;
            });
        }


        Craft::info(
            'NsmPublishLayoutHelper ' . Craft::t('nsm-publish-layout-helper', 'plugin loaded'),
            __METHOD__
        );
    }
}
