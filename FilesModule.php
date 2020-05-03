<?php

class FilesModule extends Module {
    
    protected $id = 'minicore-files';
    
    public function __construct() {
        $framework = Framework::instance();
        $framework->add([
            'files' => 'Files'
        ]);      
    }
    
    public function init() {
        parent::init();
        $framework = Framework::instance();
        /** @var Translation $translation */
        $translation = $framework->get('translation');
        $translation->add('files', 'modules/minicore-files/translations');
        /** @var View $view */
        $view = $framework->get('view');
        $view->addFolder(':files', 'modules/minicore-files/templates');
    }
    
}
