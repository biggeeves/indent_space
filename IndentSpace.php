<?php

namespace Dcc\IndentSpace;

use Exception;
use ExternalModules\ExternalModules;
use \REDCap as REDCap;

class IndentSpace extends \ExternalModules\AbstractExternalModule
{
    private $indentSize;
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
        $this->jsController($project_id, $instrument);
        echo $this->js;

    }

    public function redcap_survey_page(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        $this->jsController($project_id, $instrument);
        echo $this->js;
    }

    private function jsController($project_id, $instrument): void
    {

        $this->setIndentSize();
        $this->setJS($project_id, $instrument);
    }


    private function setIndentSize(): void
    {
        $this->indentSize = (int)$this->getSystemSetting('indent-size');
        if ($this->indentSize < 1 || $this->indentSize > 60 || is_null($this->indentSize)) {
            $this->indentSize = 30;
        }
    }

    private function setJS($project_id, $instrument): void
    {
        $this->js = '<script type="text/javascript">' . PHP_EOL .
            'var DCCIndentSpace = {};' . PHP_EOL .
            'DCCIndentSpace.initialize = ' .
            $this->setElementsJs($project_id, $instrument) .
            'DCCIndentSpace();' . PHP_EOL .
            '</script>' . PHP_EOL;
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
                continue;
            }
            $elementTarget = '';
            if ($field['matrix_group_name'] === "") {
                $elementTarget = $field['field_name'] . '-tr").firstElementChild';
            } else {
                $elementTarget = 'label-' . $field['field_name'] . '")';
                echo $field['field_name'] . ' is in a matrix';
            }
            $value = (int)substr($field_annotations, $pos + length($tag), 1);
            if (!$value || $value === 0) continue;
            $value = $value * $this->indentSize;
            echo $field['field_name'] . ': ' . $pos . $field_annotations . " value: " . $value . "<br>";
            $js .= PHP_EOL .
                'document.getElementById("' .
                $elementTarget .
                '.style.paddingLeft="' .
                $value .
                'px";' .
                PHP_EOL;
        }

        return $js;
    }


}
