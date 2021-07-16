<?php

namespace Dcc\Space;

use Exception;
use \REDCap as REDCap;

class Space extends \ExternalModules\AbstractExternalModule
{
    private $stepSize;
    private $js;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function redcap_data_entry_form(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        $this->stepSize = (int)$this->getProjectSetting('indent-size');
        if ($this->stepSize < 1 || $this->stepSize > 60 || is_null($this->stepSize)) {
            $this->stepSize = 30;
        }
        $this->setJS($project_id, $instrument);
        echo $this->js;
    }

    function redcap_survey_page(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        $this->stepSize = (int)$this->getProjectSetting('indent-size');
        if ($this->stepSize < 1 || $this->stepSize > 60 || is_null($this->stepSize)) {
            $this->stepSize = 30;
        }
        $this->setJS($instrument);
        echo $this->js;
    }

    private function setJS($project_id, $instrument)
    {
        $this->js = 'Greg Was Here' .
            '<script type="text/javascript">' .
            'var DCCIndentSpace = {};' .
            'DCCIndentSpace.initialize = ' .
            $this->setElementsJs($project_id, $instrument) .
            'DCCIndentSpace();' .
            '</script>';
    }


    /**
     * Creates a single JavaScript for all elements that have the action tag "@SPACE-LEFT=".  The Step value is
     * how much each element will be indented.
     *
     * @param string $instrument instrument to look in
     * @throws Exception
     */
    private function setElementsJs($project_id, string $instrument): string
    {

        // Get the metadata with applied filters
        $q = REDCap::getDataDictionary($project_id, 'json', false, null, $instrument);
        $metadata = json_decode($q, true);
        $tag = '@SPACE-LEFT=';
        $js = "";
        foreach ($metadata as $field) {
            $field_annotations = $field['field_annotation'];
            $pos = strpos($field_annotations, $tag);
            if ($pos === false) {
            } else {
                $value = (int)substr($field_annotations, $pos + length($tag), 1);
                if (!$value || $value === 0) continue;
                $value = $value * $this->stepSize;
                echo $field['field_name'] . ': ' . $pos . $field_annotations . " value: " . $value . "<br>";
                $js .= PHP_EOL .
                    'document.getElementById("' .
                    $field['field_name'] .
                    '-tr").firstElementChild.style.paddingLeft="' .
                    $value .
                    'px";' .
                    PHP_EOL;
            }
        }

        return $js;
    }

    private function debugInfo()
    {
        echo 'Step Size: ' . $this->stepSize;
    }
}
