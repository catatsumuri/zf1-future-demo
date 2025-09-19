<?php

class AboutController extends Zend_Controller_Action
{
    public function indexAction(): void
    {
        $this->view->highlights = [
            [
                'icon' => 'glyphicon-road',
                'title' => 'Project Vision',
                'body' => 'Provide a lightweight demo for running legacy ZF1 apps on modern PHP via Docker.'
            ],
            [
                'icon' => 'glyphicon-fire',
                'title' => 'Stack',
                'body' => 'Built on PHP 8.1 with Apache, Bootstrap 3 styling, and jQuery utilities.'
            ],
            [
                'icon' => 'glyphicon-random',
                'title' => 'Extensibility',
                'body' => 'Add controllers and modules quickly; Composer handles dependencies in containers.'
            ],
        ];
    }
}
