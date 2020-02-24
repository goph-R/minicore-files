<?php

class FilesModule extends Module {
    
    protected $id = 'minicore-files';
    
    public function __construct(Framework $framework) {
        parent::__construct($framework);
        $framework->add([
            'files' => 'Files'
        ]);      
    }
    
    public function init() {
        parent::init();
        /** @var Translation $translation */
        $translation = $this->framework->get('translation');
        $translation->add('files', 'modules/minicore-files/translations');
        /** @var View $view */
        $view = $this->framework->get('view');
        $view->addFolder(':files', 'modules/minicore-files/templates');
    }
    
}
