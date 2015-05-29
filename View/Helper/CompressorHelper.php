<?php

App::uses('AppHelper', 'View/Helper');

/**
 * Compressor
 * Will compress HTML
 * Will combine and compress JS and CSS files
 *
 * @author Borg
 * @version 0.4
 */
class CompressorHelper extends AppHelper {
    // load html helper
    public $helpers = ['Html'];

    // default conf
    public $settings = [
        'html' => [
            'compression' => true
        ], 'css' => [
            'path' => '/cache-css', // without trailing slash
            'compression' => true
        ], 'js' => [
            'path' => '/cache-js', // without trailing slash
            'compression' => true,
            'async' => true
    ]];

    // the view object
    public $view;

    // container for css and js files
    private $css = ['intern' => [], 'extern' => []];
    private $js = ['intern' => [], 'extern' => []];

    // simulate live
    private $live = false;

    /**
     * Constructor
     * @param View $View
     * @param unknown $settings
     */
    public function __construct(View $View, $settings = []) {
        // set the view object and call parent constructor
        $this->view = $View;
        parent::__construct($View, $settings);

        // calculate file system route
        $this->settings['css']['route'] = rtrim(WWW_ROOT, DS) . str_replace('/', DS, $this->settings['css']['path']);
        $this->settings['js']['route'] = rtrim(WWW_ROOT, DS) . str_replace('/', DS, $this->settings['js']['path']);
    }

    /**
     * HTML compressor
     * @see Helper::afterLayout()
     */
    public function afterLayout($layoutFile) {
        $this->view->output = $this->_html($this->view->output);
    }

    /**
     * Add css files to list
     * @param array $files
     */
    public function style($files = []) {
        // add each file to group with www_root
        $group = [];
        foreach($files as $url)
            $group[] = rtrim(WWW_ROOT, DS) . str_replace([$this->request->webroot, '/'], DS, $this->assetUrl($url, array('pathPrefix' => Configure::read('App.cssBaseUrl'), 'ext' => '.css')));

        // array merge
        $this->css['intern'] = am($group, $this->css['intern']);
        $this->css['extern'] = am($files, $this->css['extern']);
    }

    /**
     * Add js files to list
     * @param array $files
     */
    public function script($files = []) {
        // add each file to group with www_root
        $group = [];
        foreach($files as $url)
            $group[] = rtrim(WWW_ROOT, DS) . str_replace([$this->request->webroot, '/'], DS, $this->assetUrl($url, array('pathPrefix' => Configure::read('App.jsBaseUrl'), 'ext' => '.js')));

        // array merge
        $this->js['intern'] = am($group, $this->js['intern']);
        $this->js['extern'] = am($files, $this->js['extern']);
    }

    /**
     * Fetch either combined css or js
     * @param string $what style | script
     * @throws CakeException
     */
    public function fetch($what = null, $live = false) {
        // not supported?
        if(!in_array($what, ['style', 'script']))
            throw new CakeException("{$what} not supported");

        // simulate live?
        $this->live = $live;

        // call private function
        $function = '_' . $what;
        $this->$function();
    }

    /**
     * Attempt to create the filename for the selected resources
     * @param string $what js | css
     * @throws CakeException
     * @return string
     */
    private function filename($what = null) {
        // not supported?
        if(!in_array($what, ['css', 'js']))
            throw new CakeException("{$what} not supported");

        $last = 0;
        $loop = $this->$what;
        foreach($loop['intern'] as $res)
            if(file_exists($res))
                $last = max($last, filemtime($res));

        return "cache-{$last}-" . md5(serialize($loop['intern'])) . ".{$what}";
    }

    /**
     * HTML compressor
     * @param string $content
     * @return string
     */
    private function _html($content) {
        // compress?
        if(Configure::read('debug') == 0 && $this->settings['html']['compression'])
            $content = trim(\Minify_HTML::minify($content));

        // return
        return $content;
    }

    /**
     * Create the cache file if it doesnt exist
     * Return the combined css either compressed or not (depending on the setting)
     */
    private function _style() {
        // only compress if we're in production
        if(Configure::read('debug') == 0 || $this->live == true) {
            // no cache file? write it
            $cache = $this->filename('css');
            if(!file_exists($this->settings['css']['route'] . DS . $cache)) {
                // get content
                $content = null;
                foreach($this->css['intern'] as $file)
                    $content .= "\n" . file_get_contents($file) . "\n";

                // replace relative paths to absolute paths
                $content = preg_replace('/(\.\.\/)+/i', Router::url('/', true), $content);

                // compress?
                if($this->settings['css']['compression'])
                    $content = trim(\Minify_CSS::minify($content, ['preserveComments' => false]));

                // write to file
                file_put_contents($this->settings['css']['route'] . DS . $cache, $content);
            }

            // output with the HTML helper
            echo $this->Html->css($this->settings['css']['path'] . '/' . $cache);

        // development mode, output separately with the HTML helper
        } else echo $this->Html->css($this->css['extern']);
    }

    /**
     * Create the cache file if it doesnt exist
     * Return the combined js either compressed or not (depending on the setting)
     */
    private function _script() {
        // only compress if we're in production
        if(Configure::read('debug') == 0 || $this->live == true) {
            // no cache file? write it
            $cache = $this->filename('js');
            if(!file_exists($this->settings['js']['route'] . DS . $cache)) {
                // get content
                $content = null;
                foreach($this->js['intern'] as $file)
                    $content .= "\n" . file_get_contents($file) . "\n";

                // compress?
                if($this->settings['js']['compression'])
                    $content = trim(\Minify_JS_ClosureCompiler::minify($content));

                // write to file
                file_put_contents($this->settings['js']['route'] . DS . $cache, $content);
            }

            // output with the HTML helper
            echo $this->Html->script($this->settings['js']['path'] . '/' . $cache, $this->settings['js']['async'] == true ? ['async' => 'async'] : []);

        // development mode, output separately with the HTML helper
        } else echo $this->Html->script($this->js['extern']);
    }
}